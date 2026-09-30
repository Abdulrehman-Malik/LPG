<?php
namespace App\Controllers;
use App\Services\CashService;
use App\Services\PermissionService;
use CodeIgniter\Controller;
class Cash extends Controller {
 private function guard(): ?\CodeIgniter\HTTP\ResponseInterface { if(!PermissionService::allows('CASH_MANAGE')) return $this->response->setStatusCode(403)->setBody('Forbidden'); return null; }
 public function index(){if($r=$this->guard())return $r;$s=new CashService();$session=$s->openSessionForLocation((int)session()->get('location_id'));return view('cash/index',['title'=>'Counter Cash','session'=>$session,'summary'=>$session?$s->summary((int)$session['id']):null]);}
 public function open(){if($r=$this->guard())return $r;try{(new CashService())->openSession((int)session()->get('user_id'),(int)session()->get('location_id'),(float)$this->request->getPost('opening_cash'),(string)$this->request->getPost('notes'));return redirect()->to('/cash')->with('success','Cash session opened.');}catch(\Throwable $e){return redirect()->back()->withInput()->with('error',$e->getMessage());}}
 public function close(){if($r=$this->guard())return $r;try{$x=(new CashService())->closeSession((int)$this->request->getPost('session_id'),(int)session()->get('user_id'),(float)$this->request->getPost('counted_cash'),(string)$this->request->getPost('notes'));return redirect()->to('/cash')->with('success','Cash session closed. Difference: Rs. '.number_format($x['difference'],2));}catch(\Throwable $e){return redirect()->back()->withInput()->with('error',$e->getMessage());}}
}