<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use App\Models\CylinderTypeModel;
use App\Models\GasRateModel;
use App\Models\ShopSettingsModel;
use App\Services\PermissionService;
use App\Services\SalesService;
use CodeIgniter\Controller;

class Sales extends Controller
{
    private function guard(string $permission='POS_SALE'): ?\CodeIgniter\HTTP\ResponseInterface
    {
        if(!PermissionService::allows($permission)) return $this->response->setStatusCode(403)->setBody('Forbidden');
        return null;
    }

    public function index()
    {
        if($r=$this->guard()) return $r;
        $types=(new CylinderTypeModel())->activeOrdered();
        $customers=(new CustomerModel())->activeDirectory();
        $service=new SalesService();
        $balances=[];$creditLimits=[];
        foreach($customers as $c){$balances[$c['id']]=$service->customerBalance((int)$c['id']);$creditLimits[$c['id']]=(float)$c['credit_limit'];}
        $rateModel=new GasRateModel();
        $cylinderRates=[];
        foreach($types as $type) $cylinderRates[$type['id']]=$rateModel->currentCylinderRate((int)$type['id']);
        $shopSettings=(new ShopSettingsModel())->forLocation((int)session()->get('location_id')); $defaultSaleMode=(string)($shopSettings['default_sale_mode']??'sell_gas_only');
        $cashSession=(new \App\Services\CashService())->openSessionForLocation((int)session()->get('location_id')); $inv=new \App\Services\InventoryService(); $cylinders=new \App\Services\CylinderUnitService(); $filledStock=[]; $filledUnits=[]; $emptyStock=[]; $gasStock=0.0; foreach($types as $type){$typeId=(int)$type['id']; $filledStock[$typeId]=$inv->stock((int)session()->get('location_id'),'filled_cylinder',$typeId); $filledUnits[$typeId]=$cylinders->availableForDisplay((int)session()->get('location_id'),$typeId,'filled'); foreach($filledUnits[$typeId] as $unit) $gasStock+=(float)$unit['gas_weight_kg']; $emptyStock[$typeId]=$inv->stock((int)session()->get('location_id'),'empty_cylinder',$typeId);}
        return view('sales/index',['title'=>'POS Sales','types'=>$types,'customers'=>$customers,'balances'=>$balances,'creditLimits'=>$creditLimits,'kgRate'=>$rateModel->currentKgRate(),'cylinderRates'=>$cylinderRates,'cashSession'=>$cashSession,'filledStock'=>$filledStock,'filledUnits'=>$filledUnits,'emptyStock'=>$emptyStock,'gasStock'=>$gasStock,'defaultSaleMode'=>$defaultSaleMode,'shopSettings'=>$shopSettings,'inventoryPolicy'=>(new \App\Services\InventoryControlService())->policy((int)session()->get('location_id'))]);
    }

    public function save()
    {
        if($r=$this->guard()) return $r;
        try{
            $lines=json_decode((string)$this->request->getPost('lines_json'),true);
            $payments=json_decode((string)$this->request->getPost('payments_json'),true);
            if(!is_array($lines)||!is_array($payments)) throw new \RuntimeException('Invalid POS line or payment data.');
            $result=(new SalesService())->post(['customer_id'=>$this->request->getPost('customer_id'),'transaction_at'=>$this->request->getPost('transaction_at'),'discount_amount'=>$this->request->getPost('discount_amount'),'lines'=>$lines,'payments'=>$payments,'notes'=>$this->request->getPost('notes'),'stock_override_confirmed'=>$this->request->getPost('stock_override_confirmed')],(int)session()->get('user_id'),(int)session()->get('location_id'));
            return redirect()->to('/sales')->with('success','Sale '.$result['sale_no'].' posted successfully. <a href="'.site_url('sales/receipt/'.$result['id']).'">Print receipt</a>');
        }catch(\Throwable $e){return redirect()->back()->withInput()->with('error',$e->getMessage());}
    }

    public function receipt(int $id)
    {
        if($r=$this->guard()) return $r;
        $db=\Config\Database::connect();
        $sale=$db->table('sales s')->select('s.*,c.code customer_code,c.name customer_name,c.phone customer_phone')->join('customers c','c.id=s.customer_id','left')->where('s.id',$id)->where('s.location_id',(int)session()->get('location_id'))->get()->getRowArray();
        if(!$sale) return $this->response->setStatusCode(404)->setBody('Sale not found');
        $items=$db->table('sale_items')->where('sale_id',$id)->orderBy('line_no')->get()->getResultArray();
        $payments=$db->table('sale_payments')->where('sale_id',$id)->orderBy('id')->get()->getResultArray();
        $shopSettings=(new \App\Models\ShopSettingsModel())->forLocation((int)session()->get('location_id')); $location=$db->table('locations')->where('id',(int)session()->get('location_id'))->get()->getRowArray() ?: [];
        return view('sales/receipt',['sale'=>$sale,'items'=>$items,'payments'=>$payments,'shopSettings'=>$shopSettings,'location'=>$location]);
    }

    public function void(int $id)
    {
        if($r=$this->guard('POS_VOID')) return $r;
        try{
            $reason=trim((string)$this->request->getPost('void_reason'));
            $no=(new SalesService())->void($id,(int)session()->get('user_id'),(int)session()->get('location_id'),$reason);
            return redirect()->to('/sales')->with('success','Sale '.$no.' voided successfully.');
        }catch(\Throwable $e){return redirect()->back()->with('error',$e->getMessage());}
    }
}