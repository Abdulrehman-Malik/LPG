<?php

use App\Services\CashService;
use App\Services\CylinderUnitService;
use App\Services\InventoryControlService;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use App\Services\SalesService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class BusinessFlowTest extends CIUnitTestCase
{
    protected $db;
    private int $locationId = 1;
    private int $userId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect();
    }

    private function unique(string $prefix): string
    {
        return $prefix . '-' . bin2hex(random_bytes(4));
    }

    private function addCustomer(float $creditLimit = 5000): int
    {
        $this->db->table('customers')->insert([
            'code' => $this->unique('T-C'),
            'name' => $this->unique('Test Customer'),
            'credit_limit' => $creditLimit,
            'opening_balance' => 0,
            'is_active' => 1,
        ]);
        return (int) $this->db->insertID();
    }

    private function addSupplier(float $creditLimit = 5000): int
    {
        $this->db->table('suppliers')->insert([
            'code' => $this->unique('T-S'),
            'name' => $this->unique('Test Supplier'),
            'credit_limit' => $creditLimit,
            'opening_balance' => 0,
            'is_active' => 1,
        ]);
        return (int) $this->db->insertID();
    }

    private function addRates(int $typeId, float $gasRate = 100, float $cylinderRate = 1000): void
    {
        $when = '2030-01-01 00:00:00';
        $this->db->table('rate_cards')->insert([
            'location_id' => $this->locationId,
            'rate_type' => 'gas_per_kg',
            'cylinder_type_id' => null,
            'rate_value' => $gasRate,
            'effective_from' => $when,
            'created_by' => $this->userId,
        ]);
        $this->db->table('rate_cards')->insert([
            'location_id' => $this->locationId,
            'rate_type' => 'cylinder_package',
            'cylinder_type_id' => $typeId,
            'rate_value' => $cylinderRate,
            'effective_from' => $when,
            'created_by' => $this->userId,
        ]);
    }

    private function typeId(): int
    {
        return (int) $this->db->table('cylinder_types')->where('code', 'C11_8')->get()->getRow('id');
    }

    private function cashSession(float $opening = 1000): int
    {
        return (new CashService())->openSession($this->userId, $this->locationId, $opening, 'Automated business-flow test');
    }

    private function closeCash(int $sessionId): void
    {
        $summary = (new CashService())->summary($sessionId);
        (new CashService())->closeSession($sessionId, $this->userId, $summary['expected'], 'Automated business-flow test close');
    }

    public function testPurchaseCreatesPhysicalFilledCylindersAndSupplierCredit(): void
    {
        $typeId = $this->typeId();
        $supplierId = $this->addSupplier(1000);

        $result = (new PurchaseService())->post([
            'supplier_id' => $supplierId,
            'lines' => [[
                'line_type' => 'filled_cylinder',
                'cylinder_type_id' => $typeId,
                'quantity' => 2,
                'actual_gas_weight_kg' => 11.8,
                'unit_rate' => 100,
            ]],
            'payments' => [],
        ], $this->userId, $this->locationId);

        $this->assertSame(200.0, round((float) $result['total'], 2));
        $this->assertSame(200.0, round((float) $result['balance_payable'], 2));
        $this->assertSame(2.0, (new InventoryService())->stock($this->locationId, 'filled_cylinder', $typeId));
        $this->assertEqualsWithDelta(23.6, (new InventoryService())->stock($this->locationId, 'gas_kg'), 0.001);
        $this->assertEqualsWithDelta(200.0, (new PurchaseService())->supplierBalance($supplierId, $this->locationId), 0.001);
    }

    public function testCashGasSaleReducesCylinderGasAndIncreasesCash(): void
    {
        $typeId = $this->typeId();
        $this->addRates($typeId, 100, 1000);
        $unitId = (new CylinderUnitService())->createUnits($this->locationId, $typeId, 1, 'filled', 11.8, $this->userId, 'test', 0)[0];
        $sessionId = $this->cashSession();

        $result = (new SalesService())->post([
            'transaction_type' => 'gas_sale',
            'lines' => [[
                'cylinder_type_id' => $typeId,
                'source_cylinder_unit_id' => $unitId,
                'quantity' => 5,
            ]],
            'payments' => [['payment_mode' => 'cash', 'amount' => 500]],
        ], $this->userId, $this->locationId);

        $unit = $this->db->table('cylinder_units')->where('id', $unitId)->get()->getRowArray();
        $sale = $this->db->table('sales')->where('id', $result['id'])->get()->getRowArray();
        $summary = (new CashService())->summary($sessionId);

        $this->assertSame('posted', $sale['status']);
        $this->assertEqualsWithDelta(500, (float) $sale['total_amount'], 0.001);
        $this->assertSame('filled', $unit['status']);
        $this->assertEqualsWithDelta(6.8, (float) $unit['gas_weight_kg'], 0.001);
        $this->assertEqualsWithDelta(1500, $summary['expected'], 0.001);

        $this->closeCash($sessionId);
    }

    public function testCustomerCreditSaleCreatesReceivableWithoutCashMovement(): void
    {
        $typeId = $this->typeId();
        $this->addRates($typeId, 100, 1000);
        $customerId = $this->addCustomer(1000);
        $unitId = (new CylinderUnitService())->createUnits($this->locationId, $typeId, 1, 'filled', 11.8, $this->userId, 'test', 0)[0];

        $result = (new SalesService())->post([
            'transaction_type' => 'gas_sale',
            'customer_id' => $customerId,
            'lines' => [[
                'cylinder_type_id' => $typeId,
                'source_cylinder_unit_id' => $unitId,
                'quantity' => 5,
            ]],
            'payments' => [['payment_mode' => 'credit', 'amount' => 500]],
        ], $this->userId, $this->locationId);

        $this->assertEqualsWithDelta(500, (new SalesService())->customerBalance($customerId), 0.001);
        $payment = $this->db->table('sale_payments')->where('sale_id', $result['id'])->get()->getRowArray();
        $this->assertSame('credit', $payment['payment_mode']);
        $this->assertEqualsWithDelta(500, (float) $payment['amount'], 0.001);
    }

    public function testFilledCylinderSaleMarksUnitSoldAndPostsCash(): void
    {
        $typeId = $this->typeId();
        $this->addRates($typeId, 100, 1000);
        $unitId = (new CylinderUnitService())->createUnits($this->locationId, $typeId, 1, 'filled', 11.8, $this->userId, 'test', 0)[0];
        $sessionId = $this->cashSession();

        $result = (new SalesService())->post([
            'transaction_type' => 'cylinder_sale',
            'lines' => [[
                'cylinder_type_id' => $typeId,
                'cylinder_status' => 'filled',
                'quantity' => 1,
            ]],
            'payments' => [['payment_mode' => 'cash', 'amount' => 2180]],
        ], $this->userId, $this->locationId);

        $unit = $this->db->table('cylinder_units')->where('id', $unitId)->get()->getRowArray();
        $this->assertSame('sold', $unit['status']);
        $this->assertEqualsWithDelta(2180, (float) $result['total'], 0.001);
        $this->assertSame(0, (new InventoryService())->stock($this->locationId, 'filled_cylinder', $typeId));

        $this->closeCash($sessionId);
    }

    public function testSecurityDepositIssueAndCylinderReturnRestoreCustodyInventory(): void
    {
        $typeId = $this->typeId();
        $customerId = $this->addCustomer();
        $unitId = (new CylinderUnitService())->createUnits($this->locationId, $typeId, 1, 'empty', 0, $this->userId, 'test', 0)[0];
        $sessionId = $this->cashSession();

        $deposit = (new SalesService())->post([
            'transaction_type' => 'security_deposit',
            'customer_id' => $customerId,
            'custody_unit_ids' => [$unitId],
            'security_deposit_amount' => 500,
            'payments' => [['payment_mode' => 'cash', 'amount' => 500]],
        ], $this->userId, $this->locationId);

        $issued = $this->db->table('cylinder_units')->where('id', $unitId)->get()->getRowArray();
        $this->assertSame('custody', $issued['status']);

        $held = $this->db->table('customer_security_deposits')->where(['sale_id' => $deposit['id'], 'entry_type' => 'hold'])->get()->getRowArray();
        $this->assertEqualsWithDelta(500, (float) $held['amount'], 0.001);

        $returned = (new SalesService())->post([
            'transaction_type' => 'cylinder_return',
            'customer_id' => $customerId,
            'return_unit_ids' => [$unitId],
        ], $this->userId, $this->locationId);

        $after = $this->db->table('cylinder_units')->where('id', $unitId)->get()->getRowArray();
        $refund = $this->db->table('customer_security_deposits')->where(['sale_id' => $returned['id'], 'entry_type' => 'refund'])->get()->getRowArray();
        $this->assertSame('empty', $after['status']);
        $this->assertEqualsWithDelta(500, (float) $refund['amount'], 0.001);

        $balance = $this->db->query(
            "SELECT COALESCE(SUM(CASE WHEN entry_type='hold' THEN amount ELSE -amount END),0) AS balance
             FROM customer_security_deposits WHERE customer_id=? AND location_id=?",
            [$customerId, $this->locationId]
        )->getRowArray();
        $this->assertEqualsWithDelta(0, (float) $balance['balance'], 0.001);

        $summary = (new CashService())->summary($sessionId);
        $this->assertEqualsWithDelta(1000, $summary['expected'], 0.001);
        $this->closeCash($sessionId);
    }

    public function testConfiguredWastageReducesPhysicalGasAndCreatesAuditRow(): void
    {
        $typeId = $this->typeId();
        $unitId = (new CylinderUnitService())->createUnits($this->locationId, $typeId, 1, 'filled', 11.8, $this->userId, 'test', 0)[0];
        (new InventoryControlService())->savePolicy($this->locationId, $typeId, true, 'fixed_kg', 0, 1, $this->userId);

        (new InventoryControlService())->recordWastage($this->locationId, $unitId, 1, 'Automated wastage test', $this->userId);

        $unit = $this->db->table('cylinder_units')->where('id', $unitId)->get()->getRowArray();
        $log = $this->db->table('inventory_wastage_logs')->where('cylinder_unit_id', $unitId)->orderBy('id', 'DESC')->get()->getRowArray();

        $this->assertSame('filled', $unit['status']);
        $this->assertEqualsWithDelta(10.8, (float) $unit['gas_weight_kg'], 0.001);
        $this->assertEqualsWithDelta(1, (float) $log['gas_weight_kg'], 0.001);
    }

    public function testOversizedGasSaleIsRejectedWithoutChangingCylinder(): void
    {
        $typeId = $this->typeId();
        $this->addRates($typeId, 100, 1000);
        $unitId = (new CylinderUnitService())->createUnits($this->locationId, $typeId, 1, 'filled', 11.8, $this->userId, 'test', 0)[0];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('exceeds selected source cylinder');

        try {
            (new SalesService())->post([
                'transaction_type' => 'gas_sale',
                'lines' => [[
                    'cylinder_type_id' => $typeId,
                    'source_cylinder_unit_id' => $unitId,
                    'quantity' => 12,
                ]],
                'payments' => [['payment_mode' => 'cash', 'amount' => 1200]],
            ], $this->userId, $this->locationId);
        } finally {
            $unit = $this->db->table('cylinder_units')->where('id', $unitId)->get()->getRowArray();
            $this->assertSame('filled', $unit['status']);
            $this->assertEqualsWithDelta(11.8, (float) $unit['gas_weight_kg'], 0.001);
        }
    }
}
