<?php

namespace App\Controllers;

use App\Models\CylinderTypeModel;
use App\Models\RateCardModel;
use App\Models\RateChangeLogModel;
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
        $allTypes=$this->types->orderBy('sort_order')->findAll(); $activeTypes=array_values(array_filter($allTypes,static fn($t)=>(int)$t['is_active']===1)); $rateCylinderTypes=[]; foreach($allTypes as $t) $rateCylinderTypes[(string)$t['id']]=$t; return view('rates/index',['title'=>'LPG Rates','types'=>$activeTypes,'rates'=>$this->rates->history($locationId),'rateCylinderTypes'=>$rateCylinderTypes]);
    }

    public function save()
    {
        if($r=$this->guard()) return $r;
        $locationId=(int)(session()->get('location_id') ?? 0);
        $type=$this->request->getPost('rate_type');
        $typeId=$this->request->getPost('cylinder_type_id')===''?null:(int)$this->request->getPost('cylinder_type_id');
        $value=(float)$this->request->getPost('rate_value');
        $effective=str_replace('T',' ',trim((string)$this->request->getPost('effective_from')));
        $reason=trim((string)$this->request->getPost('reason')) ?: null;
        if(!in_array($type,['gas_per_kg','cylinder_package','wholesale_cylinder'],true)||$value<0||$effective==='') return redirect()->back()->withInput()->with('error','Valid rate type, value and effective date/time are required.');
        if($type==='gas_per_kg') $typeId=null;
        if($type!=='gas_per_kg' && !$typeId) return redirect()->back()->withInput()->with('error','Select a cylinder type for cylinder rates.');

        $db=Database::connect();
        $db->transStart();
        $current=$this->rates->where('location_id',$locationId)->where('rate_type',$type)->where('cylinder_type_id',$typeId)->where('effective_from <=',date('Y-m-d H:i:s'))->orderBy('effective_from','DESC')->first();
        $row=[
            'location_id'=>$locationId,'rate_type'=>$type,'cylinder_type_id'=>$typeId,'rate_value'=>$value,
            'effective_from'=>$effective,'created_by'=>(int)session()->get('user_id'),'created_at'=>date('Y-m-d H:i:s')
        ];
        $rateId=$this->rates->insert($row,true);
        (new RateChangeLogModel())->insert([
            'rate_card_id'=>$rateId,'location_id'=>$locationId,'rate_type'=>$type,'cylinder_type_id'=>$typeId,
            'old_rate'=>$current['rate_value'] ?? null,'new_rate'=>$value,'changed_by'=>(int)session()->get('user_id'),
            'reason'=>$reason
        ]);
        $db->transComplete();
        return redirect()->to('/rates')->with('success','Rate saved and history recorded.');
    }

    /**
     * Correct the value / effective time of an existing rate. The rate type and cylinder type are fixed
     * (delete and re-add to change them). Posted sales keep the rate they were sold at, so this never
     * rewrites past transactions.
     */
    public function update(int $id)
    {
        if($r=$this->guard()) return $r;
        $locationId=(int)(session()->get('location_id') ?? 0);
        $row=$this->rates->where('id',$id)->where('location_id',$locationId)->first();
        if(!$row) return redirect()->to('/rates')->with('error','Rate not found.');

        $value=(float)$this->request->getPost('rate_value');
        $effectiveRaw=str_replace('T',' ',trim((string)$this->request->getPost('effective_from')));
        $reason=trim((string)$this->request->getPost('reason')) ?: null;
        $logReason=$reason;
        if($logReason===null) $logReason='Edited';
        $ts=$effectiveRaw===''?false:strtotime($effectiveRaw);
        if($value<0 || $ts===false) return redirect()->to('/rates')->with('error','A valid rate and effective date/time are required.');
        $effective=date('Y-m-d H:i:s',$ts);

        if(abs($value-(float)$row['rate_value'])<0.00005 && $effective===date('Y-m-d H:i:s',strtotime((string)$row['effective_from']))){
            return redirect()->to('/rates')->with('success','No changes to save.');
        }

        $db=Database::connect();
        $db->transStart();
        $this->rates->update($id,['rate_value'=>$value,'effective_from'=>$effective]);
        (new RateChangeLogModel())->insert([
            'rate_card_id'=>$id,'location_id'=>$locationId,'rate_type'=>$row['rate_type'],'cylinder_type_id'=>$row['cylinder_type_id'],
            'old_rate'=>$row['rate_value'],'new_rate'=>$value,'changed_by'=>(int)session()->get('user_id'),
            'reason'=>$logReason
        ]);
        \App\Services\AuditService::log('UPDATE','rate_card',$id,['rate_value'=>$row['rate_value'],'effective_from'=>$row['effective_from']],['rate_value'=>$value,'effective_from'=>$effective],(int)session()->get('user_id'),$locationId);
        $db->transComplete();
        if(!$db->transStatus()) return redirect()->to('/rates')->with('error','Rate could not be updated.');
        return redirect()->to('/rates')->with('success','Rate updated and history recorded.');
    }

    /** Removes a rate row. The delete is audit-logged; sales already posted keep their own saved rate. */
    public function delete(int $id)
    {
        if($r=$this->guard()) return $r;
        $locationId=(int)(session()->get('location_id') ?? 0);
        $row=$this->rates->where('id',$id)->where('location_id',$locationId)->first();
        if(!$row) return redirect()->to('/rates')->with('error','Rate not found.');

        $db=Database::connect();
        $db->transStart();
        \App\Services\AuditService::log('DELETE','rate_card',$id,$row,null,(int)session()->get('user_id'),$locationId);
        $this->rates->delete($id);
        $db->transComplete();
        if(!$db->transStatus()) return redirect()->to('/rates')->with('error','Rate could not be deleted.');
        return redirect()->to('/rates')->with('success','Rate deleted.');
    }
}