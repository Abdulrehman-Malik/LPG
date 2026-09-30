<?php

namespace App\Controllers;

use App\Models\SupplierModel;
use App\Services\PermissionService;
use CodeIgniter\Controller;

class Suppliers extends Controller
{
    protected SupplierModel $model;
    public function __construct(){ $this->model=new SupplierModel(); }
    private function guard(): ?\CodeIgniter\HTTP\ResponseInterface
    {
        if(!PermissionService::allows('SUPPLIER_MANAGE')) return $this->response->setStatusCode(403)->setBody('Forbidden');
        return null;
    }
    public function index(){ if($r=$this->guard()) return $r; $editId=(int)$this->request->getGet('edit'); return view('suppliers/index',['title'=>'Suppliers','suppliers'=>$this->model->orderBy('name')->findAll(),'edit'=>$editId?$this->model->find($editId):null]); }
    public function ledger(int $id)
    {
        if($r=$this->guard()) return $r;
        $supplier=$this->model->find($id);
        if(!$supplier) return $this->response->setStatusCode(404)->setBody('Supplier not found');
        $db=$this->model->db;
        $purchases=$db->table('purchases')->select('transaction_at,purchase_no,total_amount,credit_amount,status')->where('supplier_id',$id)->orderBy('transaction_at','DESC')->get()->getResultArray();
        $payments=$db->table('supplier_payments')->select('payment_at,payment_no,amount,payment_mode,status')->where('supplier_id',$id)->orderBy('payment_at','DESC')->get()->getResultArray();
        $credit=(float)$supplier['opening_balance']; foreach($purchases as $row) if($row['status']==='posted') $credit+=(float)$row['credit_amount']; foreach($payments as $row) if($row['status']==='posted') $credit-=(float)$row['amount'];
        $supplier['credit_due']=$credit;
        return view('suppliers/ledger',['title'=>'Supplier Ledger — '.$supplier['name'],'supplier'=>$supplier,'purchases'=>$purchases,'payments'=>$payments]);
    }

    public function save()
    {
        if($r=$this->guard()) return $r;
        $id=(int)$this->request->getPost('id');
        $data=[
            'code'=>trim((string)$this->request->getPost('code')) ?: null,
            'name'=>trim((string)$this->request->getPost('name')),
            'phone'=>trim((string)$this->request->getPost('phone')) ?: null,
            'city'=>trim((string)$this->request->getPost('city')) ?: null,
            'address'=>trim((string)$this->request->getPost('address')) ?: null,
            'credit_limit'=>(float)$this->request->getPost('credit_limit'),
            'opening_balance'=>(float)$this->request->getPost('opening_balance'),
            'is_active'=>$this->request->getPost('is_active') ? 1 : 0,
        ];
        if($data['name']==='') return redirect()->back()->withInput()->with('error','Supplier name is required.');
        try { if($id) $this->model->update($id,$data); else $this->model->insert($data); } catch (\Throwable $e) { return redirect()->back()->withInput()->with('error','Supplier could not be saved. Check the code for duplicates.'); }
        return redirect()->to('/suppliers')->with('success',$id?'Supplier updated.':'Supplier created.');
    }
}