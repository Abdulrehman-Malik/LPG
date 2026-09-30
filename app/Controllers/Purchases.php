<?php
namespace App\Controllers;
use App\Services\PurchaseService;
use CodeIgniter\Controller;
use Config\Database;
class Purchases extends Controller{
 private function guard(){return \App\Services\PermissionService::allows('PURCHASE_MANAGE')?null:$this->response->setStatusCode(403)->setBody('Forbidden');}
 public function index(){if($r=$this->guard())return $r;$db=Database::connect();return view('purchases/index',['title'=>'Purchases','suppliers'=>$db->table('suppliers')->where('is_active',1)->orderBy('name')->get()->getResultArray(),'types'=>$db->table('cylinder_types')->where('is_active',1)->orderBy('sort_order')->get()->getResultArray()]);}
 public function save(){if($r=$this->guard())return $r;try{$lines=json_decode((string)$this->request->getPost('lines_json'),true);$payments=json_decode((string)$this->request->getPost('payments_json'),true);$x=(new PurchaseService())->post(['supplier_id'=>$this->request->getPost('supplier_id'),'discount_amount'=>$this->request->getPost('discount_amount'),'lines'=>$lines,'payments'=>$payments,'notes'=>$this->request->getPost('notes')],(int)session()->get('user_id'),(int)session()->get('location_id'));return redirect()->to('/purchases')->with('success','Purchase '.$x['purchase_no'].' posted successfully.');}catch(\Throwable $e){return redirect()->back()->withInput()->with('error',$e->getMessage());}}
}