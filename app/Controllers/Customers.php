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
        return view('customers/index',['title'=>'Customers / Parties','customers'=>$this->model->orderBy('name')->findAll()]);
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
        if($id) $this->model->update($id,$data); else $this->model->insert($data);
        return redirect()->to('/customers')->with('success',$id?'Customer updated.':'Customer created.');
    }
}