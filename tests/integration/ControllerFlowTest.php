<?php

use App\Controllers\Expenses;
use App\Controllers\Inventory;
use App\Controllers\InventoryOpening;
use App\Controllers\Receipts;
use App\Controllers\ShopSettings;
use App\Controllers\SupplierPayments;
use App\Services\CashService;
use App\Services\CylinderUnitService;
use App\Services\PurchaseService;
use App\Services\SalesService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Config\Services;

final class ControllerFlowTest extends CIUnitTestCase
{
    protected $db;
    private int $locationId = 1;
    private int $userId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect();
        session()->set(['location_id' => $this->locationId, 'user_id' => $this->userId]);
    }

    private function unique(string $prefix): string
    {
        return $prefix . '-' . bin2hex(random_bytes(4));
    }

    private function customer(float $limit = 5000): int
    {
        $this->db->table('customers')->insert([
            'code' => $this->unique('CF-C'),
            'name' => $this->unique('Controller Customer'),
            'credit_limit' => $limit,
            'allow_credit_sale' => 1,
            'opening_balance' => 0,
            'is_active' => 1,
        ]);
        return (int) $this->db->insertID();
    }

    private function supplier(): int
    {
        $this->db->table('suppliers')->insert([
            'code' => $this->unique('CF-S'),
            'name' => $this->unique('Controller Supplier'),
            'credit_limit' => 5000,
            'opening_balance' => 0,
            'is_active' => 1,
        ]);
        return (int) $this->db->insertID();
    }

    private function typeId(): int
    {
        return (int) $this->db->table('cylinder_types')->where('code', 'C11_8')->get()->getRow('id');
    }

    private function rates(): void
    {
        $typeId = $this->typeId();
        $this->db->table('rate_cards')->insert([
            'location_id' => $this->locationId,
            'rate_type' => 'gas_per_kg',
            'cylinder_type_id' => null,
            'rate_value' => 100,
            'effective_from' => '2020-01-01 00:00:00',
            'created_by' => $this->userId,
        ]);
        $this->db->table('rate_cards')->insert([
            'location_id' => $this->locationId,
            'rate_type' => 'cylinder_package',
            'cylinder_type_id' => $typeId,
            'rate_value' => 1000,
            'effective_from' => '2020-01-01 00:00:00',
            'created_by' => $this->userId,
        ]);
    }

    private function controller(string $class, array $post): object
    {
        $request = Services::request();
        $request->setGlobal('post', $post);
        $controller = new $class();
        $controller->initController($request, Services::response(), Services::logger());
        return $controller;
    }

    private function openCash(): int
    {
        return (new CashService())->openSession($this->userId, $this->locationId, 1000, 'Controller flow test');
    }

    private function closeCash(int $id): void
    {
        $s = new CashService();
        $summary = $s->summary($id);
        $s->closeSession($id, $this->userId, $summary['expected'], 'Controller flow test close');
    }

    public function testCustomerReceiptControllerPostsOnlineReceiptAndReducesBalance(): void
    {
        $this->rates();
        $customerId = $this->customer();
        $unitId = (new CylinderUnitService())->createUnits($this->locationId, $this->typeId(), 1, 'filled', 11.8, $this->userId, 'test', 0)[0];

        (new SalesService())->post([
            'transaction_type' => 'gas_sale',
            'customer_id' => $customerId,
            'lines' => [[
                'cylinder_type_id' => $this->typeId(),
                'source_cylinder_unit_id' => $unitId,
                'quantity' => 5,
            ]],
            'payments' => [['payment_mode' => 'credit', 'amount' => 500]],
        ], $this->userId, $this->locationId);

        $this->controller(Receipts::class, [
            'customer_id' => $customerId,
            'amount' => 200,
            'payment_mode' => 'online',
            'reference_no' => 'ONLINE-TEST-1',
            'notes' => 'Controller integration test',
        ])->save();

        $receipt = $this->db->table('customer_receipts')->where('customer_id', $customerId)->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertSame('posted', $receipt['status']);
        $this->assertEqualsWithDelta(200, (float) $receipt['amount'], 0.001);
        $this->assertEqualsWithDelta(300, (new SalesService())->customerBalance($customerId), 0.001);
    }

    public function testSupplierPaymentControllerPostsOnlinePaymentAndReducesPayable(): void
    {
        $supplierId = $this->supplier();
        (new PurchaseService())->post([
            'supplier_id' => $supplierId,
            'lines' => [[
                'line_type' => 'gas_bulk',
                'cylinder_type_id' => null,
                'quantity' => 10,
                'actual_gas_weight_kg' => 0,
                'unit_rate' => 100,
            ]],
            'payments' => [],
        ], $this->userId, $this->locationId);

        $this->controller(SupplierPayments::class, [
            'supplier_id' => $supplierId,
            'amount' => 300,
            'payment_mode' => 'online',
            'reference_no' => 'SUP-ONLINE-1',
            'notes' => 'Controller integration test',
        ])->save();

        $payment = $this->db->table('supplier_payments')->where('supplier_id', $supplierId)->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertSame('posted', $payment['status']);
        $this->assertEqualsWithDelta(300, (float) $payment['amount'], 0.001);
        $this->assertEqualsWithDelta(700, (new PurchaseService())->supplierBalance($supplierId, $this->locationId), 0.001);
    }

    public function testExpenseControllerPostsCashExpenseAndChangesExpectedCash(): void
    {
        $categoryId = (int) $this->db->table('expense_categories')->where('is_active', 1)->orderBy('id')->get()->getRow('id');
        $sessionId = $this->openCash();

        $this->controller(Expenses::class, [
            'category_id' => $categoryId,
            'amount' => 150,
            'payment_mode' => 'cash',
            'reference_no' => 'EXP-TEST-1',
            'description' => 'Controller integration test',
        ])->save();

        $expense = $this->db->table('expenses')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertEqualsWithDelta(150, (float) $expense['amount'], 0.001);
        $this->assertSame('cash', $expense['payment_mode']);
        $this->assertEqualsWithDelta(850, (new CashService())->summary($sessionId)['expected'], 0.001);

        $this->closeCash($sessionId);
    }

    public function testInventoryAdjustmentControllerPostsGasIntoStock(): void
    {
        $before = (new \App\Services\InventoryService())->stock($this->locationId, 'gas_kg');

        $this->controller(Inventory::class, [
            'inventory_type' => 'gas_kg',
            'cylinder_type_id' => '',
            'quantity' => 2,
            'direction' => 'in',
            'actual_gas_weight_kg' => 0,
            'adjustment_scope' => 'bulk',
            'source_cylinder_unit_id' => '',
            'notes' => 'Controller integration test',
        ])->adjust();

        $after = (new \App\Services\InventoryService())->stock($this->locationId, 'gas_kg');
        $this->assertEqualsWithDelta($before + 2, $after, 0.001);

        $movement = $this->db->table('inventory_movements')
            ->where(['inventory_type' => 'gas_kg', 'direction' => 'in', 'source_type' => 'adjustment'])
            ->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertEqualsWithDelta(2, (float) $movement['quantity'], 0.001);
    }

    public function testOpeningInventoryControllerCreatesPhysicalCylinderAndGasMovement(): void
    {
        $typeId = $this->typeId();
        $date = date('Y-m-d');

        $this->controller(InventoryOpening::class, [
            'opening_id' => '',
            'inventory_date' => $date,
            'inventory_type' => 'filled_cylinder',
            'cylinder_type_id' => $typeId,
            'quantity' => 1,
            'actual_gas_weight_kg' => 11.8,
            'comments' => 'Controller opening test',
        ])->save();

        $opening = $this->db->table('inventory_opening_balances')
            ->where(['location_id' => $this->locationId, 'inventory_date' => $date, 'inventory_type' => 'filled_cylinder', 'cylinder_type_id' => $typeId])
            ->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertNotEmpty($opening);

        $unit = $this->db->table('cylinder_units')
            ->where(['source_type' => 'opening', 'source_id' => $opening['id'], 'status' => 'filled'])
            ->get()->getRowArray();
        $this->assertNotEmpty($unit);
        $this->assertEqualsWithDelta(11.8, (float) $unit['gas_weight_kg'], 0.001);

        $movement = $this->db->table('inventory_movements')
            ->where(['source_type' => 'opening_cylinder', 'source_id' => $opening['id'], 'inventory_type' => 'gas_kg'])
            ->get()->getRowArray();
        $this->assertNotEmpty($movement);
        $this->assertEqualsWithDelta(11.8, (float) $movement['quantity'], 0.001);
    }

    public function testShopSettingsControllerRejectsInvalidFontSizeAndAcceptsValidSettings(): void
    {
        $this->controller(ShopSettings::class, [
            'default_sale_mode' => 'sell_gas_only',
            'default_transaction_type' => 'gas_sale',
            'default_payment_mode' => 'cash',
            'individual_cylinder_tracking' => 1,
            'pos_font_size_px' => 30,
            'theme_mode' => 'light',
            'font_family' => 'system',
            'primary_color' => '#112233',
            'accent_color' => '#445566',
            'stock_validation_enabled' => 1,
            'allow_stock_override' => 1,
            'credit_limit_validation_mode' => 'none',
            'shop_credit_limit' => 0,
            'backup_enabled' => 0,
            'receipt_title' => 'TEST',
            'receipt_footer' => 'TEST',
            'show_address_on_receipt' => 1,
            'shop_name' => 'QA Shop',
            'shop_address' => 'QA Address',
            'shop_city' => 'QA City',
            'shop_phone' => '000',
        ])->save();

        $this->assertTrue(true);

        $this->controller(ShopSettings::class, [
            'default_sale_mode' => 'sell_gas_only',
            'default_transaction_type' => 'gas_sale',
            'default_payment_mode' => 'cash',
            'individual_cylinder_tracking' => 1,
            'pos_font_size_px' => 14,
            'theme_mode' => 'dark',
            'font_family' => 'system',
            'primary_color' => '#112233',
            'accent_color' => '#445566',
            'stock_validation_enabled' => 1,
            'allow_stock_override' => 1,
            'credit_limit_validation_mode' => 'none',
            'shop_credit_limit' => 0,
            'backup_enabled' => 0,
            'receipt_title' => 'TEST',
            'receipt_footer' => 'TEST',
            'show_address_on_receipt' => 1,
            'shop_name' => 'QA Shop',
            'shop_address' => 'QA Address',
            'shop_city' => 'QA City',
            'shop_phone' => '000',
        ])->save();

        $settings = $this->db->table('shop_settings')->where('location_id', $this->locationId)->get()->getRowArray();
        $this->assertSame('dark', $settings['theme_mode']);
        $this->assertEqualsWithDelta(14, (float) $settings['pos_font_size_px'], 0.001);
    }
}
