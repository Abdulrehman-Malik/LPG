<?php

namespace App\Controllers;

use App\Models\CylinderTypeModel;
use App\Models\RateCardModel;
use App\Models\RateChangeLogModel;
use App\Models\CustomerModel;
use App\Services\PermissionService;
use CodeIgniter\Controller;
use Config\Database;

class Rates extends Controller
{
    protected RateCardModel $rates;
    protected CylinderTypeModel $types;
    public function __construct(){ $this->rates=new RateCardModel(); $this->types=new CylinderTypeModel(); }

    private function guard(): ?\CodeIgniter\HTTP\ResponseInterface
    {
        if(!PermissionService::allows('RATE_MANAGE')) return $this->response->setStatusCode(403)->setBody('Forbidden');
        return null;
    }

    public function index()
    {
        if($r=$this->guard()) return $r;
        $locationId=(int)(session()->get('location_id') ?? 0);
        $customers=(new CustomerModel())->where('is_active',1)->orderBy('name')->findAll(); $allTypes=$this->types->orderBy('sort_order')->findAll(); $activeTypes=array_values(array_filter($allTypes,static fn($t)=>(int)$t['is_active']===1)); $rateCylinderTypes=[]; foreach($allTypes as $t) $rateCylinderTypes[(string)$t['id']]=$t; return view('rates/index',['title'=>'LPG Rates','types'=>$activeTypes,'customers'=>$customers,'rates'=>$this->rates->history($locationId),'rateCylinderTypes'=>$rateCylinderTypes]);
    }

    public function save()
    {
        if($r=$this->guard()) return $r;
        $locationId=(int)(session()->get('location_id') ?? 0);
        $type=$this->request->getPost('rate_type');
        $typeId=$this->request->getPost('cylinder_type_id')===''?null:(int)$this->request->getPost('cylinder_type_id');
        $customerId=$this->request->getPost('customer_id')===''?null:(int)$this->request->getPost('customer_id');
        $value=(float)$this->request->getPost('rate_value');
        $effective=str_replace('T',' ',trim((string)$this->request->getPost('effective_from')));
        $reason=trim((string)$this->request->getPost('reason')) ?: null;
        if(!in_array($type,['gas_per_kg','cylinder_package','filled_cylinder_price'],true)||$value<0||$effective==='') return redirect()->back()->withInput()->with('error','Valid rate type, value and effective date/time are required.');
        if($type==='filled_cylinder_price' && $value<=0) return redirect()->back()->withInput()->with('error','Filled-cylinder selling price must be greater than zero.');
        if($type==='gas_per_kg') { $typeId=null; $customerId=null; }
        if($type==='cylinder_package') $customerId=null;
        if($type==='filled_cylinder_price' && !$typeId) return redirect()->back()->withInput()->with('error','Select a cylinder type for filled-cylinder prices.');
        if($type==='cylinder_package' && !$typeId) return redirect()->back()->withInput()->with('error','Select a cylinder type for package rates.');
        if($customerId && (!$this->request->getPost('customer_id') || !(new CustomerModel())->where('is_active',1)->find($customerId))) return redirect()->back()->withInput()->with('error','Select an active customer for the customer-specific rate.');

        $db=Database::connect();
        $db->transStart();
        $current=$this->rates->where('location_id',$locationId)->where('rate_type',$type)->where('cylinder_type_id',$typeId)->where('customer_id',$customerId)->where('effective_from <=',date('Y-m-d H:i:s'))->orderBy('effective_from','DESC')->orderBy('id','DESC')->first();
        $row=[
            'location_id'=>$locationId,'rate_type'=>$type,'cylinder_type_id'=>$typeId,'customer_id'=>$customerId,'rate_value'=>$value,
            'effective_from'=>$effective,'created_by'=>(int)session()->get('user_id'),'created_at'=>date('Y-m-d H:i:s')
        ];
        $rateId=$this->rates->insert($row,true);
        (new RateChangeLogModel())->insert([
            'rate_card_id'=>$rateId,'location_id'=>$locationId,'rate_type'=>$type,'cylinder_type_id'=>$typeId,'customer_id'=>$customerId,
            'old_rate'=>$current['rate_value'] ?? null,'new_rate'=>$value,'changed_by'=>(int)session()->get('user_id'),
            'reason'=>$reason
        ]);
        $db->transComplete();
        return redirect()->to('/rates')->with('success','Rate saved and history recorded.');
    }
}