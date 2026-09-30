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
    public function index(){ if($r=$this->guard()) return $r; return view('suppliers/index',['title'=>'Suppliers','suppliers'=>$this->model->orderBy('name')->findAll()]); }
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
        if($id) $this->model->update($id,$data); else $this->model->insert($data);
        return redirect()->to('/suppliers')->with('success',$id?'Supplier updated.':'Supplier created.');
    }
}