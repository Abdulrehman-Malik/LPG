<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Dashboard extends Controller
{
    public function index()
    {
        $db=$this->db;
        $today=date('Y-m-d');
        $locationId=(int)(session()->get('location_id') ?? 0);

        $sales=$db->table('sales')->selectSum('total_amount','total')
            ->where('status','posted')
            ->where('transaction_at >=',$today.' 00:00:00')
            ->where('transaction_at <',date('Y-m-d',strtotime($today.' +1 day')).' 00:00:00');
        if($locationId>0){$sales->where('location_id',$locationId);}
        $todaysSales=(float)($sales->get()->getRowArray()['total'] ?? 0);

        $open=$db->table('inventory_opening_balances')->select('inventory_type,cylinder_type_id,quantity')->where('inventory_date <=',$today);
        if($locationId>0){$open->where('location_id',$locationId);}
        $openRows=$open->get()->getResultArray();

        $mov=$db->table('inventory_movements')->select('inventory_type,cylinder_type_id,direction,quantity')
            ->where('movement_at <',date('Y-m-d',strtotime($today.' +1 day')).' 00:00:00');
        if($locationId>0){$mov->where('location_id',$locationId);}
        $movRows=$mov->get()->getResultArray();

        $cylinderStock=0;
        $totalGasStockKg=0.0;
        $balances=[];
        foreach($openRows as $r){
            $key=$r['inventory_type'].'|'.($r['cylinder_type_id'] ?? 0);
            $balances[$key]=($balances[$key] ?? 0)+(float)$r['quantity'];
        }
        foreach($movRows as $r){
            $key=$r['inventory_type'].'|'.($r['cylinder_type_id'] ?? 0);
            $q=(float)$r['quantity'];
            $balances[$key]=($balances[$key] ?? 0)+($r['direction']==='out' ? -$q : $q);
        }
        foreach($balances as $key=>$q){
            [$type,$cylinderId]=explode('|',$key);
            if($type==='filled_cylinder'){$cylinderStock+=(int)$q;}
            if($type==='gas_kg'){$totalGasStockKg+=$q;}
        }

        return view('dashboard/index',[
            'title'=>'Dashboard','todaysSales'=>$todaysSales,
            'cylinderStock'=>$cylinderStock,'totalGasStockKg'=>$totalGasStockKg,
        ]);
    }
}
