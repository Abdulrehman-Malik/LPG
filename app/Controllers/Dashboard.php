<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use Config\Database;

class Dashboard extends Controller
{
    public function index()
    {
        $db=Database::connect();
        $today=date('Y-m-d');
        $tomorrow=date('Y-m-d',strtotime($today.' +1 day'));
        $locationId=(int)(session()->get('location_id') ?? 0);

        $sales=$db->table('sales')->selectSum('total_amount','total')
            ->where('status','posted')->where('transaction_at >=',$today.' 00:00:00')
            ->where('transaction_at <',$tomorrow.' 00:00:00');
        if($locationId>0) $sales->where('location_id',$locationId);
        $todaysSales=(float)($sales->get()->getRowArray()['total'] ?? 0);

        $open=$db->table('inventory_opening_balances')->select('inventory_type,cylinder_type_id,quantity')->where('inventory_date <=',$today);
        if($locationId>0) $open->where('location_id',$locationId);
        $balances=[];
        foreach($open->get()->getResultArray() as $r){
            $key=$r['inventory_type'].'|'.($r['cylinder_type_id'] ?? 0);
            $balances[$key]=($balances[$key] ?? 0)+(float)$r['quantity'];
        }

        $mov=$db->table('inventory_movements')->select('inventory_type,cylinder_type_id,direction,quantity')->where('movement_at <',$tomorrow.' 00:00:00');
        if($locationId>0) $mov->where('location_id',$locationId);
        foreach($mov->get()->getResultArray() as $r){
            $key=$r['inventory_type'].'|'.($r['cylinder_type_id'] ?? 0);
            $q=(float)$r['quantity'];
            $balances[$key]=($balances[$key] ?? 0)+($r['direction']==='out' ? -$q : $q);
        }

        $cylinderStock=0;
        $totalGasStockKg=0.0;
        foreach($balances as $key=>$q){
            [$type]=explode('|',$key);
            if($type==='filled_cylinder') $cylinderStock+=(int)$q;
            if($type==='gas_kg') $totalGasStockKg+=$q;
        }

        return view('dashboard/index',[
            'title'=>'Dashboard','todaysSales'=>$todaysSales,
            'cylinderStock'=>$cylinderStock,'totalGasStockKg'=>$totalGasStockKg,
        ]);
    }
}
