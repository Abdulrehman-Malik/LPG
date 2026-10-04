<?php

use App\Controllers\Audit;
use App\Controllers\Cash;
use App\Controllers\Customers;
use App\Controllers\Reports;
use App\Controllers\Sales;
use App\Controllers\Suppliers;
use App\Controllers\Users;
use App\Services\CashService;
use App\Services\CylinderUnitService;
use App\Services\SalesService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Config\Services;

final class AccountingAndAccessFlowTest extends CIUnitTestCase
{
    protected $db;
    private int $locationId = 1;
    private int $adminId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect();
        session()->set(['location_id' => $this->locationId, 'user_id' => $this->adminId]);
    }

    private function controller(string $class, array $post = []): object
    {
        $request = Services::request();
        $request->setGlobal('post', $post);
        $controller = new $class();
        $controller->initController($request, Services::response(), Services::logger());
        return $controller;
    }

    private function typeId(): int
    {
        return (int) $this->db->table('cylinder_types')->where('code', 'C11_8')->get()->getRow('id');
    }

    private function rateCards(): void
    {
        $this->db->table('rate_cards')->insert([
            'location_id' => $this->locationId,
            'rate_type' => 'gas_per_kg',
            'cylinder_type_id' => null,
            'rate_value' => 100,
            'effective_from' => '2020-01-01 00:00:00',
            'created_by' => $this->adminId,
        ]);
        $this->db->table('rate_cards')->insert([
            'location_id' => $this->locationId,
            'rate_type' => 'cylinder_package',
            'cylinder_type_id' => $this->typeId(),
            'rate_value' => 1000,
            'effective_from' => '2020-01-01 00:00:00',
            'created_by' => $this->adminId,
        ]);
    }

    public function testCashManualMovementAndCloseFlow(): void
    {
        $cash = new CashService();
        $sessionId = $cash->openSession($this->adminId, $this->locationId, 1000, 'Accounting test');

        $this->controller(Cash::class, [
            'amount' => 125,
            'direction' => 'in',
            'notes' => 'Manual cash in test',
        ])->move();

        $summary = $cash->summary($sessionId);
        $this->assertEqualsWithDelta(1125, $summary['expected'], 0.001);

        $this->controller(Cash::class, [
            'session_id' => $sessionId,
            'counted_cash' => 1120,
            'notes' => 'Close with five rupee shortage',
        ])->close();

        $closed = $this->db->table('cash_sessions')->where('id', $sessionId)->get()->getRowArray();
        $this->assertSame('closed', $closed['status']);
        $this->assertEqualsWithDelta(1120, (float) $closed['counted_cash'], 0.001);
    }

    public function testVoidingCashGasSaleRestoresInventoryAndReversesCash(): void
    {
        $this->rateCards();
        $unitId = (new CylinderUnitService())->createUnits($this->locationId, $this->typeId(), 1, 'filled', 11.8, $this->adminId, 'test', 0)[0];
        $cash = new CashService();
        $sessionId = $cash->openSession($this->adminId, $this->locationId, 1000, 'Void test');

        $sale = (new SalesService())->post([
            'transaction_type' => 'gas_sale',
            'lines' => [[
                'cylinder_type_id' => $this->typeId(),
                'source_cylinder_unit_id' => $unitId,
                'quantity' => 5,
            ]],
            'payments' => [['payment_mode' => 'cash', 'amount' => 500]],
        ], $this->adminId, $this->locationId);

        $this->controller(Sales::class, [
            'void_reason' => 'Automated void test',
        ])->void((int) $sale['id']);

        $row = $this->db->table('sales')->where('id', $sale['id'])->get()->getRowArray();
        $unit = $this->db->table('cylinder_units')->where('id', $unitId)->get()->getRowArray();
        $summary = $cash->summary($sessionId);

        $this->assertSame('void', $row['status']);
        $this->assertSame('filled', $unit['status']);
        $this->assertEqualsWithDelta(11.8, (float) $unit['gas_weight_kg'], 0.001);
        $this->assertEqualsWithDelta(1000, $summary['expected'], 0.001);

        $this->controller(Cash::class, [
            'session_id' => $sessionId,
            'counted_cash' => 1000,
            'notes' => 'Void test close',
        ])->close();
    }

    public function testReportsLedgersAndAuditPagesRenderForAdmin(): void
    {
        $pages = [
            Reports::class => ['method' => 'index', 'expected' => 'Reports'],
            Reports::class . ':inventory' => ['class' => Reports::class, 'method' => 'inventoryDetail', 'expected' => 'Inventory Detail'],
            Reports::class . ':custody' => ['class' => Reports::class, 'method' => 'custody', 'expected' => 'Cylinder Custody'],
            Audit::class => ['method' => 'index', 'expected' => 'Audit Log'],
        ];

        foreach ($pages as $key => $page) {
            $class = $page['class'] ?? $key;
            $response = $this->controller($class)->{$page['method']}();
            $this->assertIsString($response);
            $this->assertStringContainsString($page['expected'], $response);
        }

        $customer = $this->db->table('customers')->orderBy('id')->get()->getRowArray();
        $supplier = $this->db->table('suppliers')->orderBy('id')->get()->getRowArray();

        $customerPage = $this->controller(Customers::class)->ledger((int) $customer['id']);
        $supplierPage = $this->controller(Suppliers::class)->ledger((int) $supplier['id']);

        $this->assertIsString($customerPage);
        $this->assertIsString($supplierPage);
        $this->assertStringContainsString($customer['name'], $customerPage);
        $this->assertStringContainsString($supplier['name'], $supplierPage);
    }

    public function testCashierCannotOpenAdminFunctionsButCanViewReports(): void
    {
        $cashier = $this->db->table('users u')
            ->select('u.id')
            ->join('roles r', 'r.id=u.role_id')
            ->where('r.code', 'CASHIER')
            ->where('u.is_active', 1)
            ->orderBy('u.id')
            ->get()->getRowArray();

        $this->assertNotEmpty($cashier);
        session()->set('user_id', (int) $cashier['id']);

        $users = $this->controller(Users::class)->index();
        $cash = $this->controller(Cash::class)->index();
        $reports = $this->controller(Reports::class)->index();

        $this->assertSame(403, $users->getStatusCode());
        $this->assertSame(403, $cash->getStatusCode());
        $this->assertIsString($reports);
        $this->assertStringContainsString('Reports', $reports);

        session()->set('user_id', $this->adminId);
    }
}
