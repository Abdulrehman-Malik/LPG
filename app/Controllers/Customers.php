<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use App\Services\PermissionService;
use CodeIgniter\Controller;

class Customers extends Controller
{
    protected CustomerModel $model;
    public function __construct(){ $this->model=new CustomerModel(); }

    private function guard(): ?\CodeIgniter\HTTP\ResponseInterface
    {
        if(!PermissionService::allows('CUSTOMER_MANAGE')) return $this->response->setStatusCode(403)->setBody('Forbidden');
        return null;
    }

    public function index()
    {
        if($r=$this->guard()) return $r;
        $editId=(int)$this->request->getGet('edit'); return view('customers/index',['title'=>'Customers / Parties','customers'=>$this->model->orderBy('name')->findAll(),'edit'=>$editId?$this->model->find($editId):null]);
    }

    public function ledger(int $id)
    {
        if($r=$this->guard()) return $r;
        $locationId=(int)session()->get('location_id');
        $customer=$this->model->withLedgerTotals($id,$locationId);
        if(!$customer) return $this->response->setStatusCode(404)->setBody('Customer not found');
        $db=$this->model->db;
        $sales=$db->table('sales')->select('transaction_at,sale_no,transaction_type,total_amount,credit_amount,security_deposit_amount,security_deposit_refund_amount,status')->where('customer_id',$id)->where('location_id',$locationId)->orderBy('transaction_at','DESC')->get()->getResultArray();
        $receipts=$db->table('customer_receipts')->select('receipt_at,receipt_no,amount,payment_mode,status')->where('customer_id',$id)->where('location_id',$locationId)->orderBy('receipt_at','DESC')->get()->getResultArray();
        $deposits=$db->table('customer_security_deposits')->select('transaction_at,entry_type,amount,sale_id,custody_id,notes')->where('customer_id',$id)->where('location_id',$locationId)->orderBy('transaction_at','DESC')->get()->getResultArray();
        $custody=$db->table('cylinder_custody cc')->select('cc.*,cu.unit_code,cu.gas_weight_kg,ct.code cylinder_code,ct.name cylinder_name')->join('cylinder_units cu','cu.id=cc.cylinder_unit_id')->join('cylinder_types ct','ct.id=cu.cylinder_type_id')->where(['cc.customer_id'=>$id,'cc.status'=>'issued'])->orderBy('cc.id')->get()->getResultArray();
        return view('customers/ledger',['title'=>'Customer Ledger — '.$customer['name'],'customer'=>$customer,'sales'=>$sales,'receipts'=>$receipts,'deposits'=>$deposits,'custody'=>$custody]);
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
            'vehicle_no'=>trim((string)$this->request->getPost('vehicle_no')) ?: null,
            'credit_limit'=>(float)$this->request->getPost('credit_limit'),
            'opening_balance'=>(float)$this->request->getPost('opening_balance'),
            'is_active'=>$this->request->getPost('is_active') ? 1 : 0,
        ];
        if($data['name']==='') return redirect()->back()->withInput()->with('error','Customer name is required.');
        if($data['credit_limit']<0||$data['opening_balance']<0) return redirect()->back()->withInput()->with('error','Credit limit and opening balance cannot be negative.');
        try { if($id) $this->model->update($id,$data); else $this->model->insert($data); } catch (\Throwable $e) { return redirect()->back()->withInput()->with('error','Customer could not be saved. Check the code for duplicates.'); }
        return redirect()->to('/customers')->with('success',$id?'Customer updated.':'Customer created.');
    }
}