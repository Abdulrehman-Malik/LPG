<?php
namespace App\Controllers;
use App\Services\InventoryService;
use CodeIgniter\Controller;
use Config\Database;
class Inventory extends Controller{
 private function guard(){return \App\Services\PermissionService::allows('INVENTORY_MANAGE')?null:$this->response->setStatusCode(403)->setBody('Forbidden');}
 public function index(){if($r=$this->guard())return $r;$db=Database::connect();$types=$db->table('cylinder_types')->where('is_active',1)->orderBy('sort_order')->get()->getResultArray();$inv=new InventoryService();$rows=[['type'=>'gas_kg','id'=>'','name'=>'Gas (KG)','stock'=>$inv->stock((int)session()->get('location_id'),'gas_kg')]];foreach($types as $t){$rows[]=['type'=>'filled_cylinder','id'=>$t['id'],'name'=>'Filled '.$t['name'],'stock'=>$inv->stock((int)session()->get('location_id'),'filled_cylinder',(int)$t['id'])];$rows[]=['type'=>'empty_cylinder','id'=>$t['id'],'name'=>'Empty '.$t['name'],'stock'=>$inv->stock((int)session()->get('location_id'),'empty_cylinder',(int)$t['id'])];}return view('inventory/index',['title'=>'Inventory','rows'=>$rows,'types'=>$types]);}
 public function adjust(){if($r=$this->guard())return $r;try{(new InventoryService())->adjust((int)session()->get('location_id'),(string)$this->request->getPost('inventory_type'),$this->request->getPost('cylinder_type_id')!==''?(int)$this->request->getPost('cylinder_type_id'):null,(float)$this->request->getPost('quantity'),(string)$this->request->getPost('direction'),(int)session()->get('user_id'),$this->request->getPost('notes'));return redirect()->to('/inventory')->with('success','Inventory adjustment posted.');}catch(\Throwable $e){return redirect()->back()->withInput()->with('error',$e->getMessage());}}
}