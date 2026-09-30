<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use App\Models\CylinderTypeModel;
use App\Models\GasRateModel;
use App\Services\PermissionService;
use App\Services\SalesService;
use CodeIgniter\Controller;

class Sales extends Controller
{
    private function guard(): ?\CodeIgniter\HTTP\ResponseInterface
    {
        if(!PermissionService::allows('POS_SALE')) return $this->response->setStatusCode(403)->setBody('Forbidden');
        return null;
    }

    public function index()
    {
        if($r=$this->guard()) return $r;
        $types=(new CylinderTypeModel())->activeOrdered();
        $customers=(new CustomerModel())->activeDirectory();
        return view('sales/index',[
            'title'=>'POS Sales',
            'types'=>$types,
            'customers'=>$customers,
            'kgRate'=>(new GasRateModel())->currentKgRate(),
            'cylinderRates'=>array_reduce($types,static function($out,$type){$rate=(new GasRateModel())->currentCylinderRate((int)$type['id']);$out[$type['id']]=$rate;return $out;},[])
        ]);
    }

    public function save()
    {
        if($r=$this->guard()) return $r;
        try{
            $lines=json_decode((string)$this->request->getPost('lines_json'),true);
            $payments=json_decode((string)$this->request->getPost('payments_json'),true);
            $service=new SalesService();
            $id=$service->post([
                'customer_id'=>$this->request->getPost('customer_id'),
                'transaction_at'=>$this->request->getPost('transaction_at'),
                'discount_amount'=>$this->request->getPost('discount_amount'),
                'lines'=>$lines,
                'payments'=>$payments,
                'notes'=>$this->request->getPost('notes'),
            ],(int)session()->get('user_id'),(int)session()->get('location_id'));
            return redirect()->to('/sales')->with('success','Sale #'.$id.' posted successfully.');
        }catch(\Throwable $e){
            return redirect()->back()->withInput()->with('error',$e->getMessage());
        }
    }
}
