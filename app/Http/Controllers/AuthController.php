<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request with role-based authentication
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Debug logging
        \Log::info('Login attempt', [
            'email' => $credentials['email'],
            'password_length' => strlen($credentials['password'])
        ]);

        // Try to find the user first
        $user = \App\Models\User::where('email', $credentials['email'])->first();
        
        if ($user) {
            \Log::info('User found', [
                'email' => $user->email,
                'role' => $user->role,
                'has_password' => !empty($user->password)
            ]);
            
            // Check password manually
            $passwordMatch = \Hash::check($credentials['password'], $user->password);
            \Log::info('Password check', ['match' => $passwordMatch]);
            
            if (!$passwordMatch) {
                \Log::error('Password mismatch', [
                    'email' => $user->email,
                    'hash_prefix' => substr($user->password, 0, 10)
                ]);
            }
        } else {
            \Log::error('User not found', ['email' => $credentials['email']]);
        }

        // Archived or inactive users cannot sign in
        $attempt = $credentials + ['status' => 'active', 'archived_at' => null];

        if (Auth::attempt($attempt, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            
            \Log::info('Login successful', ['email' => $user->email, 'role' => $user->role]);
            
            // Redirect based on role
            return $this->redirectBasedOnRole($user);
        }

        \Log::error('Auth::attempt failed');

        throw ValidationException::withMessages([
            'email' => __('The provided credentials do not match our records.'),
        ]);
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Redirect user based on their role
     */
    protected function redirectBasedOnRole($user)
    {
        // Owner: redirect to dashboard
        if ($user->isOwner()) {
            return redirect()->route('dashboard');
        }

        // Manager: redirect to Point of Sale (their primary work area)
        if ($user->isManager()) {
            return redirect()->route('pos');
        }

        // Cashier: redirect to Point of Sale (their primary work area)
        if ($user->isCashier()) {
            return redirect()->route('pos');
        }

        // Default fallback
        return redirect()->route('pos');
    }
}
