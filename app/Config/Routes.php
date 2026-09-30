<?php
use CodeIgniter\Router\RouteCollection;
/** @var RouteCollection $routes */
$routes->get('/','Auth::showLogin'); $routes->get('login','Auth::showLogin'); $routes->post('login','Auth::attemptLogin'); $routes->get('logout','Auth::logout');
$routes->group('', ['filter'=>'auth'], static function(RouteCollection $routes){
    $routes->get('dashboard','Dashboard::index');
    $routes->get('sales','Sales::index'); $routes->post('sales/save','Sales::save'); $routes->get('sales/receipt/(:num)','Sales::receipt/$1'); $routes->post('sales/void/(:num)','Sales::void/$1');
    $routes->get('cash','Cash::index'); $routes->post('cash/open','Cash::open'); $routes->post('cash/close','Cash::close'); $routes->post('cash/move','Cash::move');\n    $routes->get('purchases','Purchases::index'); $routes->post('purchases/save','Purchases::save');\n    $routes->get('inventory','Inventory::index'); $routes->post('inventory/adjust','Inventory::adjust');\n    $routes->get('expenses','Expenses::index'); $routes->post('expenses/save','Expenses::save');\n    $routes->get('receipts','Receipts::index'); $routes->post('receipts/save','Receipts::save');\n    $routes->get('supplier-payments','SupplierPayments::index'); $routes->post('supplier-payments/save','SupplierPayments::save');\n    $routes->get('reports','Reports::index'); $routes->get('reports/ledger/(:num)','Reports::ledger/$1');\n    $routes->get('audit','Audit::index');
    $routes->get('customers','Customers::index'); $routes->get('customers/ledger/(:num)','Customers::ledger/$1'); $routes->post('customers/save','Customers::save');
    $routes->get('suppliers','Suppliers::index'); $routes->get('suppliers/ledger/(:num)','Suppliers::ledger/$1'); $routes->post('suppliers/save','Suppliers::save');
    $routes->get('cylinder-types','CylinderTypes::index'); $routes->post('cylinder-types/save','CylinderTypes::save');
    $routes->get('rates','Rates::index'); $routes->post('rates/save','Rates::save');
    $routes->get('users','Users::index'); $routes->post('users/save','Users::save'); $routes->post('users/role-permissions','Users::rolePermissions');
    $routes->get('inventory/opening','InventoryOpening::index'); $routes->post('inventory/opening/save','InventoryOpening::save');
});