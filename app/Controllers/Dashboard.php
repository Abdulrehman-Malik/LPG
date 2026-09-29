<?php

namespace App\Controllers;

use App\Models\DailyFilledStockModel;
use App\Models\SaleModel;
use CodeIgniter\Controller;

class Dashboard extends Controller
{
    /**
     * GET /dashboard
     *
     * KPIs are now live (see claude.md — this replaces the earlier placeholder
     * pass):
     *   - todaysSales     => SaleModel::totalAmountForDate() for today
     *   - cylinderStock   => DailyFilledStockModel::totalUnitsForDate() for today
     *   - totalGasStockKg => DailyFilledStockModel::totalWeightKgForDate() for today
     *
     * NOTE: if no daily_filled_stock rows exist yet for today (fresh install,
     * before the Cylinder Stock module's "open the day" action has run), these
     * simply resolve to 0 — that is expected, not a bug.
     */
    public function index()
    {
        $saleModel        = new SaleModel();
        $filledStockModel = new DailyFilledStockModel();

        $today = date('Y-m-d');

        $data = [
            'title'           => 'Dashboard',
            'todaysSales'     => $saleModel->totalAmountForDate($today),
            'cylinderStock'   => $filledStockModel->totalUnitsForDate($today),
            'totalGasStockKg' => $filledStockModel->totalWeightKgForDate($today),
        ];

        return view('dashboard/index', $data);
    }
}
