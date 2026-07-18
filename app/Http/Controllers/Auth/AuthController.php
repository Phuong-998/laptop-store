<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{
    //
    public function formLogin()
    {
        return view('auth.login');
    }

    public function handleLogin(Request $request)
    {
        $validate = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
        $remember = $request->has('remember');
        if (Auth::attempt($validate, $remember)) {
            $request->session()->regenerate();
            return $this->redirectByRole();
        }
        return redirect()->back()->with([
            'error' => 'Email hoặc mật khẩu không chính xác. Vui lòng thử lại'
        ])->withInput($request->only('email'));
    }

    public function formRegister()
    {
        return view('auth.register');
    }

    public function handleRegister(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'email.unique' => 'Email này đã được đăng ký',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp',
            'password.min' => 'Mật khẩu tối thiểu 6 ký tự',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'status' => 1,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('shop.home')->with('success', 'Đăng ký thành công. Chào mừng ' . $user->name . '!');
    }

    /**
     * Điều hướng sau đăng nhập dựa theo vai trò.
     */
    private function redirectByRole()
    {
        $user = Auth::user();
        if ($user && $user->role === 'admin') {
            return redirect()->intended(route('dashboard'));
        }
        return redirect()->intended(route('shop.home'));
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function formForgotPassword()
    {
        return view('forgot-password');
    }

    public function handleForgotPassword(Request $request)
    {
        $validate = $request->validate([
            'email' => 'required|email|'
        ]);
        $respone = Password::sendResetLink($validate);
        if ($respone == Password::ResetLinkSent) {
            return back()->with('success', 'Link reset password đã được gửi về email của bạn');
        }
        return back()->with('error', 'Không tìm thấy tài khoản nào với email này');
    }

    public function formResetPassword($token)
    {
        $email = request()->query('email');

        return view('reset-password', compact('token', 'email'));
    }

    public function handleResetPassword(Request $request)
    {
        $validate = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'password_confirmation' => 'required|same:password'
        ], [
            'password_confirmation.same' => 'Mật khẩu xác nhận không khớp',
            'password_confirmation.required' => 'Vui lòng nhập lại mật khẩu',
        ]);
        $respone = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );
        if ($respone == Password::PASSWORD_RESET) {
            return redirect()->route('login');
        }
        return redirect()->back();
    }
}
