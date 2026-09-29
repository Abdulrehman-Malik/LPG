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

        // The login view uses csrf_field() and old() — both live in the Form
        // helper, which CI4 does NOT autoload by default (only 'url' is).
        // Load it explicitly rather than relying on BaseController::$helpers,
        // since this app's BaseController.php may not already list it.
        helper('form');
    }

    /**
     * GET /login
     */
    public function showLogin()
    {
        // Already logged in? Go straight to dashboard.
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login', [
            'title' => 'Login — Perfect LPG (Pvt.) LTD',
        ]);
    }

    /**
     * POST /login
     */
    public function attemptLogin(): RedirectResponse
    {
        $rules = [
            'login'    => 'required|min_length[3]',       // username OR email
            'password' => 'required|min_length[4]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $login    = $this->request->getPost('login');
        $password = $this->request->getPost('password');
        $remember = (bool) $this->request->getPost('remember');

        $user = $this->userModel->findByLogin($login);

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid username/email or password.');
        }

        if (! (int) $user['is_active']) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This account has been disabled. Contact an administrator.');
        }

        // Regenerate the session ID on privilege change to prevent fixation.
        session()->regenerate();

        session()->set([
            'user_id'     => $user['id'],
            'full_name'   => $user['full_name'],
            'username'    => $user['username'],
            'role'        => $user['role'],
            'isLoggedIn'  => true,
        ]);

        $this->userModel->touchLastLogin($user['id']);

        if ($remember) {
            // 30-day persistent cookie token would be issued here via a
            // dedicated remember-tokens table in a future pass.
        }

        return redirect()->to('/dashboard')->with('success', 'Welcome back, ' . $user['full_name'] . '.');
    }

    /**
     * GET /logout
     */
    public function logout(): RedirectResponse
    {
        session()->destroy();

        return redirect()->to('/login')->with('success', 'You have been logged out.');
    }
}
