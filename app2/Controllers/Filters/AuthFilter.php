<?php

namespace App\Controllers\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Blocks access to any admin/dashboard route unless the session shows
 * an authenticated user. Registered as the "auth" filter alias in
 * app/Config/Filters.php and applied to the protected route group.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to('/login')
                ->with('error', 'Please log in to continue.');
        }

        // Optional role gate: pass e.g. ['admin'] as filter arguments in
        // Routes.php to restrict a route to specific roles.
        if (! empty($arguments)) {
            $role = session()->get('role');

            if (! in_array($role, $arguments, true)) {
                return redirect()->to('/dashboard')
                    ->with('error', 'You do not have permission to access that page.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed.
    }
}
