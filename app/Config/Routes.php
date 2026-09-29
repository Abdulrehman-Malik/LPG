<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ---------------------------------------------------------------------
// Public / guest routes
// ---------------------------------------------------------------------
$routes->get('/', 'Auth::showLogin');
$routes->get('login', 'Auth::showLogin');
$routes->post('login', 'Auth::attemptLogin');
$routes->get('logout', 'Auth::logout');

// ---------------------------------------------------------------------
// Authenticated routes (protected by the "auth" filter — see Filters.php)
// ---------------------------------------------------------------------
$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Dashboard::index');

    // Placeholders wired up for sidebar navigation; controllers for these
    // land in later steps per claude.md §3 (Remaining Backlog).
    // $routes->get('sales/new',            'Sales::newSale');
    // $routes->get('sales/search',         'Sales::search');
    // $routes->get('customers',            'Customers::index');
    // $routes->get('cylinder-stock',       'CylinderStock::index');
    // $routes->get('reports/daily-cash',   'Reports::dailyCash');
    // $routes->get('settings',             'Settings::index');
});
