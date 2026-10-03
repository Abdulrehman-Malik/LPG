<?php

namespace App\Controllers;

use App\Models\CylinderTypeModel;
use App\Models\InventoryOpeningBalanceModel;
use App\Services\PermissionService;
use App\Services\CylinderUnitService;
use Config\Database;
use CodeIgniter\Controller;

class InventoryOpening extends Controller
{
    protected InventoryOpeningBalanceModel $model;
    protected CylinderTypeModel $types;
    protected CylinderUnitService $cylinders;
    public function __construct(){ $this->model=new InventoryOpeningBalanceModel(); $this->types=new CylinderTypeModel(); $this->cylinders=new CylinderUnitService(); }
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

        $gasByOpening=[];
        if($rows){
            $openingIds=array_map(static fn(array $row): int => (int) $row['id'], $rows);
            $units=Database::connect()->table('cylinder_units')
                ->select('source_id, SUM(gas_weight_kg) AS gas_stock')
                ->where('location_id',$locationId)
                ->where('source_type','opening')
                ->whereIn('source_id',$openingIds)
                ->groupBy('source_id')
                ->get()->getResultArray();
            foreach($units as $unit){
                $gasByOpening[(int)$unit['source_id']]=(float)$unit['gas_stock'];
            }
        }
        foreach($rows as &$row){
            $row['gas_stock']=$row['inventory_type']==='filled_cylinder'
                ? ($gasByOpening[(int)$row['id']] ?? 0)
                : ($row['inventory_type']==='gas_kg' ? (float)$row['quantity'] : 0);
        }
        unset($row);

        return view('inventory/opening',['title'=>'Opening Inventory','types'=>$this->types->where('is_active',1)->orderBy('sort_order')->findAll(),'rows'=>$rows]);
    }
    public function save()
    {
        if($r=$this->guard()) return $r;
        $locationId=(int)session()->get('location_id'); $userId=(int)session()->get('user_id');
        $date=(string)$this->request->getPost('inventory_date'); $kind=(string)$this->request->getPost('inventory_type');
        $typeId=$this->request->getPost('cylinder_type_id')===''?null:(int)$this->request->getPost('cylinder_type_id');
        $qty=(float)$this->request->getPost('quantity'); $actualRaw=$this->request->getPost('actual_gas_weight_kg'); $actual=$actualRaw!==null && $actualRaw!==''?(float)$actualRaw:0;
        if(!$date||!in_array($kind,['filled_cylinder','empty_cylinder'],true)||$qty<0) return redirect()->back()->withInput()->with('error','Valid date, inventory type and non-negative quantity are required.');
        if(!$typeId) return redirect()->back()->withInput()->with('error','Select a cylinder type.');
        if(floor($qty)!==$qty) return redirect()->back()->withInput()->with('error','Cylinder quantity must be a whole number.');
        if($kind==='filled_cylinder'){
            $ct=$this->types->find($typeId); if(!$ct) return redirect()->back()->withInput()->with('error','Cylinder type not found.');
            $actual=$actualRaw!==null && $actualRaw!==''?$actual:(float)$ct['capacity_kg'];
            if($actual<=0||$actual>(float)$ct['capacity_kg']) return redirect()->back()->withInput()->with('error','Actual gas weight must be greater than zero and cannot exceed cylinder capacity.');
        } else $actual=0;
        $existing=$this->model->where(['location_id'=>$locationId,'inventory_date'=>$date,'inventory_type'=>$kind,'cylinder_type_id'=>$typeId])->first();
        $db=Database::connect(); $db->transBegin();
        try{
            $data=['location_id'=>$locationId,'inventory_date'=>$date,'inventory_type'=>$kind,'cylinder_type_id'=>$typeId,'quantity'=>$qty,'created_by'=>$userId];
            if($existing) $this->model->update($existing['id'],$data); else { $this->model->insert($data); $existing=['id'=>$this->model->getInsertID()]; }
            $openingId=(int)$existing['id'];
            if($kind!=='gas_kg'){
                $units=$db->table('cylinder_units')->where(['location_id'=>$locationId,'source_type'=>'opening','source_id'=>$openingId])->whereIn('status',['filled','empty'])->get()->getResultArray();
                $activeCount=count($units);
                if($activeCount>$qty) throw new RuntimeException('Opening quantity cannot be reduced below cylinders already in active opening stock. Use stock transactions instead.');
                if($activeCount<$qty) $this->cylinders->createUnits($locationId,$typeId,(int)$qty-$activeCount,$kind==='filled_cylinder'?'filled':'empty',$actual,$userId,'opening',$openingId);
                if($kind==='filled_cylinder'){
                    $db->table('inventory_movements')->where(['source_type'=>'opening_cylinder','source_id'=>$openingId])->delete();
                    $current=$db->table('cylinder_units')->where(['location_id'=>$locationId,'source_type'=>'opening','source_id'=>$openingId,'status'=>'filled'])->get()->getResultArray();
                    foreach($current as $unit) $db->table('cylinder_units')->where('id',$unit['id'])->update(['gas_weight_kg'=>$actual]);
                    foreach($current as $unit) $db->table('inventory_movements')->insert(['location_id'=>$locationId,'inventory_type'=>'gas_kg','cylinder_type_id'=>null,'quantity'=>$actual,'direction'=>'in','movement_at'=>$date.' 00:00:00','source_type'=>'opening_cylinder','source_id'=>$openingId,'cylinder_unit_id'=>$unit['id'],'created_by'=>$userId,'notes'=>'Gas contained in opening filled cylinder']);
                }
            }
            if(!$db->transStatus()) throw new RuntimeException('Opening inventory could not be saved.');
            $db->transCommit();
            return redirect()->to('/inventory/opening')->with('success','Opening inventory saved with per-cylinder gas weights.');
        }catch(\Throwable $e){$db->transRollback();return redirect()->back()->withInput()->with('error',$e->getMessage());}
    }
}
