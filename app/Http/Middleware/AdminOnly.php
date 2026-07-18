<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminOnly
{
    /**
     * Chỉ cho phép người dùng đã đăng nhập và có role admin vào khu vực quản trị.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }
        if ($user->role !== 'admin') {
            return redirect()->route('shop.home');
        }
        return $next($request);
    }
}
