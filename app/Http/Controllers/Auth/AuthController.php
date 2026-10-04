<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * LUỒNG ĐĂNG KÝ : Form -> RegisterRequest (validate) -> User::create (hash pass, role mặc định 'user') -> login -> home
 * LUỒNG ĐĂNG NHẬP: Form -> LoginRequest -> Auth::attempt (kèm status=active) -> admin? dashboard : trang trước đó / home
 */
class AuthController extends Controller
{
    public function showLogin()    { return view('auth.login'); }
    public function showRegister() { return view('auth.register'); }

    public function register(RegisterRequest $request)
    {
        $user = User::create($request->validated()); // role/status lấy default DB: user / active

        Auth::login($user);
        $request->session()->regenerate(); // giữ nguyên giỏ hàng session, đổi session id chống fixation

        return redirect()->route('home')->with('success', 'Đăng ký thành công!');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated() + ['status' => 'active']; // tài khoản bị khóa => attempt thất bại

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Sai email/mật khẩu hoặc tài khoản đã bị khóa.']);
        }

        $request->session()->regenerate();

        return Auth::user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
