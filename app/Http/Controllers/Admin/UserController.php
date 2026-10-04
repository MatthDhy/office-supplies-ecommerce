<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * [Thành viên C] Quản lý người dùng.
 *
 * BẢN KHUNG: mỗi method có sẵn ĐƯỜNG ĐI các bước cần làm (đọc theo thứ tự 1 -> n).
 * Làm xong thì xóa dòng abort(501) và viết code thật.
 */
class UserController extends Controller
{
    use ApiResponse;

    /** GET /admin/users */
    public function index(Request $request)
    {
        // 1. Tìm theo tên/email, lọc status
        // 2. User::where('role','user')->withCount('orders')->paginate(15)
        abort(501, 'TODO [Thành viên C]: UserController@index');
    }

    /** GET /admin/users/{user} */
    public function show(\App\Models\User $user)
    {
        // 1. $user->load('addresses')
        // 2. Lịch sử đơn: $user->orders()->latest()->paginate(10)
        abort(501, 'TODO [Thành viên C]: UserController@show');
    }

    /** PATCH /admin/users/{user}/toggle-status */
    public function toggleStatus(\App\Models\User $user)
    {
        // 1. Không cho tự khóa chính mình / khóa admin khác
        // 2. active <-> locked (user khóa không đăng nhập được: AuthController đã check status)
        abort(501, 'TODO [Thành viên C]: UserController@toggleStatus');
    }
}
