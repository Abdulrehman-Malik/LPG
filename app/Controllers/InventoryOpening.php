<?php

namespace App\Controllers;

use App\Models\CylinderTypeModel;
use App\Models\InventoryOpeningBalanceModel;
use App\Services\PermissionService;
use CodeIgniter\Controller;

class InventoryOpening extends Controller
{
    protected InventoryOpeningBalanceModel $model;
    protected CylinderTypeModel $types;
    public function __construct(){ $this->model=new InventoryOpeningBalanceModel(); $this->types=new CylinderTypeModel(); }
    private function guard(): ?\CodeIgniter\HTTP\ResponseInterface
    {
        if(!PermissionService::allows('INVENTORY_MANAGE')) return $this->response->setStatusCode(403)->setBody('Forbidden');
        return null;
    }
    public function index()
    {
        if($r=$this->guard()) return $r;
        $locationId=(int)session()->get('location_id');
        $rows=$this->model->select('inventory_opening_balances.*, cylinder_types.code AS cylinder_code, cylinder_types.name AS cylinder_name')->join('cylinder_types','cylinder_types.id=inventory_opening_balances.cylinder_type_id','left')->where('location_id',$locationId)->orderBy('inventory_date','DESC')->orderBy('inventory_type')->findAll();
        return view('inventory/opening',['title'=>'Opening Inventory','types'=>$this->types->where('is_active',1)->orderBy('sort_order')->findAll(),'rows'=>$rows]);
    }
    public function save()
    {
        if($r=$this->guard()) return $r;
        $locationId=(int)session()->get('location_id');
        $date=(string)$this->request->getPost('inventory_date');
        $kind=(string)$this->request->getPost('inventory_type');
        $typeId=$this->request->getPost('cylinder_type_id')===''?null:(int)$this->request->getPost('cylinder_type_id');
        $qty=(float)$this->request->getPost('quantity');
        if(!$date||!in_array($kind,['gas_kg','filled_cylinder','empty_cylinder'],true)||$qty<0) return redirect()->back()->withInput()->with('error','Valid date, inventory type and non-negative quantity are required.');
        if($kind==='gas_kg' && $typeId!==null) return redirect()->back()->withInput()->with('error','Gas opening balance does not use a cylinder type.');
        if($kind!=='gas_kg' && !$typeId) return redirect()->back()->withInput()->with('error','Select a cylinder type.');
        $existing=$this->model->where(['location_id'=>$locationId,'inventory_date'=>$date,'inventory_type'=>$kind,'cylinder_type_id'=>$typeId])->first();
        $data=['location_id'=>$locationId,'inventory_date'=>$date,'inventory_type'=>$kind,'cylinder_type_id'=>$typeId,'quantity'=>$qty,'created_by'=>(int)session()->get('user_id')];
        if($existing) $this->model->update($existing['id'],$data); else $this->model->insert($data);
        return redirect()->to('/inventory/opening')->with('success','Opening inventory saved.');
    }
}