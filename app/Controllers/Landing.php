<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Landing extends Controller
{
    public function index()
    {
        $companyName = trim((string) env('app.companyName', 'Perfect LPG (Pvt.) LTD')) ?: 'Perfect LPG (Pvt.) LTD';

        return view('landing/index', [
            'title' => 'LPG POS & ERP',
            'companyName' => $companyName,
            'isLoggedIn' => (bool) session()->get('isLoggedIn'),
        ]);
    }
}
