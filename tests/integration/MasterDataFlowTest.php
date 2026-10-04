<?php

use App\Controllers\Customers;
use App\Controllers\CylinderTypes;
use App\Controllers\Rates;
use App\Controllers\Suppliers;
use App\Controllers\Users;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Config\Services;

final class MasterDataFlowTest extends CIUnitTestCase
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

    private function controller(string $class, array $post): object
    {
        $request = Services::request();
        $request->setGlobal('post', $post);
        $controller = new $class();
        $controller->initController($request, Services::response(), Services::logger());
        return $controller;
    }

    public function testCustomerAndSupplierCrudCreatesMasterRecords(): void
    {
        $customerCode = 'QA-C-' . bin2hex(random_bytes(3));
        $supplierCode = 'QA-S-' . bin2hex(random_bytes(3));

        $this->controller(Customers::class, [
            'id' => '',
            'code' => $customerCode,
            'name' => 'QA Customer Master',
            'phone' => '03000000000',
            'city' => 'QA City',
            'address' => 'QA Address',
            'vehicle_no' => 'QA-001',
            'credit_limit' => 5000,
            'allow_credit_sale' => 1,
            'opening_balance' => 0,
            'is_active' => 1,
        ])->save();

        $this->assertSame(1, (int) Services::request()->getPost('allow_credit_sale'));

        $this->controller(Suppliers::class, [
            'id' => '',
            'code' => $supplierCode,
            'name' => 'QA Supplier Master',
            'phone' => '03111111111',
            'city' => 'QA City',
            'address' => 'QA Address',
            'credit_limit' => 5000,
            'opening_balance' => 0,
            'is_active' => 1,
        ])->save();

        $customer = $this->db->table('customers')->where('code', $customerCode)->get()->getRowArray();
        $supplier = $this->db->table('suppliers')->where('code', $supplierCode)->get()->getRowArray();

        $this->assertNotEmpty($customer);
        $this->assertSame(1, (int) $customer['allow_credit_sale']);
        $this->assertNotEmpty($supplier);
        $this->assertEqualsWithDelta(5000, (float) $supplier['credit_limit'], 0.001);
    }

    public function testRateCrudCreatesCurrentRateAndChangeLog(): void
    {
        $typeId = (int) $this->db->table('cylinder_types')->where('code', 'C11_8')->get()->getRow('id');
        $effective = date('Y-m-d\TH:i:s', time() - 60);

        $this->controller(Rates::class, [
            'rate_type' => 'gas_per_kg',
            'cylinder_type_id' => '',
            'rate_value' => 123.45,
            'effective_from' => $effective,
            'reason' => 'QA rate test',
        ])->save();

        $rate = $this->db->table('rate_cards')
            ->where(['location_id' => $this->locationId, 'rate_type' => 'gas_per_kg'])
            ->orderBy('id', 'DESC')->get()->getRowArray();
        $log = $this->db->table('rate_change_log')
            ->where('rate_card_id', $rate['id'])->orderBy('id', 'DESC')->get()->getRowArray();

        $this->assertEqualsWithDelta(123.45, (float) $rate['rate_value'], 0.001);
        $this->assertEqualsWithDelta(123.45, (float) $log['new_rate'], 0.001);
        $this->assertSame('QA rate test', $log['reason']);
    }

    public function testCylinderTypeCanBeCreatedToggledAndDeletedWhenUnused(): void
    {
        $code = 'QA_' . strtoupper(bin2hex(random_bytes(2)));

        $this->controller(CylinderTypes::class, [
            'id' => '',
            'code' => $code,
            'name' => 'QA Cylinder',
            'capacity_kg' => 20,
            'tare_weight_kg' => 10,
            'sort_order' => 99,
            'is_active' => 1,
        ])->save();

        $type = $this->db->table('cylinder_types')->where('code', $code)->get()->getRowArray();
        $this->assertNotEmpty($type);

        $this->controller(CylinderTypes::class, [
            'id' => $type['id'],
        ])->toggle();

        $inactive = $this->db->table('cylinder_types')->where('id', $type['id'])->get()->getRowArray();
        $this->assertSame(0, (int) $inactive['is_active']);

        $this->controller(CylinderTypes::class, [
            'id' => $type['id'],
        ])->toggle();

        $active = $this->db->table('cylinder_types')->where('id', $type['id'])->get()->getRowArray();
        $this->assertSame(1, (int) $active['is_active']);

        $this->controller(CylinderTypes::class, [
            'id' => $type['id'],
        ])->delete();

        $deleted = $this->db->table('cylinder_types')->where('id', $type['id'])->get()->getRowArray();
        $this->assertNull($deleted);
    }

    public function testUserCreationAndRolePermissionUpdateWork(): void
    {
        $roleId = (int) $this->db->table('roles')->where('code', 'MANAGER')->get()->getRow('id');
        $permissionId = (int) $this->db->table('permissions')->where('code', 'DASHBOARD_VIEW')->get()->getRow('id');
        $username = 'qa_user_' . bin2hex(random_bytes(3));

        $this->controller(Users::class, [
            'id' => '',
            'role_id' => $roleId,
            'full_name' => 'QA Manager User',
            'username' => $username,
            'email' => $username . '@example.test',
            'password' => 'QaPassword123!',
            'is_active' => 1,
        ])->save();

        $user = $this->db->table('users')->where('username', $username)->get()->getRowArray();
        $this->assertNotEmpty($user);
        $this->assertTrue(password_verify('QaPassword123!', $user['password_hash']));

        $this->controller(Users::class, [
            'role_id' => $roleId,
            'permissions' => [$permissionId],
        ])->rolePermissions();

        $permissions = $this->db->table('role_permissions')->where('role_id', $roleId)->get()->getResultArray();
        $this->assertCount(1, $permissions);
        $this->assertSame($permissionId, (int) $permissions[0]['permission_id']);
    }
}
