<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;

class Auth extends Controller
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        helper('form');
    }

    public function showLogin()
    {
        if(session()->get('isLoggedIn')) return redirect()->to('/dashboard');
        return view('auth/login',['title'=>'Login — Perfect LPG (Pvt.) LTD']);
    }

    public function attemptLogin(): RedirectResponse
    {
        $rules=['login'=>'required|min_length[3]','password'=>'required|min_length[4]'];
        if(!$this->validate($rules)){
            return redirect()->back()->withInput()->with('errors',$this->validator->getErrors());
        }

        $login=$this->request->getPost('login');
        $password=$this->request->getPost('password');
        $user=$this->userModel->findByLogin($login);

        if(!$user || !password_verify($password,$user['password_hash'])){
            return redirect()->back()->withInput()->with('error','Invalid username/email or password.');
        }
        if(!(int)$user['is_active']){
            return redirect()->back()->withInput()->with('error','This account has been disabled. Contact an administrator.');
        }

        session()->regenerate();
        session()->set([
            'user_id'=>(int)$user['id'],
            'location_id'=>$user['location_id'] !== null ? (int)$user['location_id'] : null,
            'full_name'=>$user['full_name'],
            'username'=>$user['username'],
            'role'=>$user['role'],
            'role_name'=>$user['role_name'],
            'isLoggedIn'=>true,
        ]);
        $this->userModel->touchLastLogin((int)$user['id']);
        return redirect()->to('/dashboard')->with('success','Welcome back, '.$user['full_name'].'.');
    }

    public function logout(): RedirectResponse
    {
        session()->destroy();
        return redirect()->to('/login')->with('success','You have been logged out.');
    }
}
