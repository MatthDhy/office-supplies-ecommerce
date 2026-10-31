<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Quản lý người dùng (chỉ role 'user'). Admin không tự tạo/sửa role ở đây.
 * View nhận: index -> $users (Paginator, có orders_count), $filters
 *            show  -> $user (load addresses), $orders (Paginator), $totalSpent (int, đơn đã thanh toán & chưa hủy)
 */
class UserController extends Controller
{
    use ApiResponse;

    /** GET /admin/users?q=&status= */
    public function index(Request $request)
    {
        $filters = [
            'q'      => is_string($request->query('q')) ? trim($request->query('q')) : '',
            'status' => in_array($request->query('status'), ['active', 'locked'], true) ? $request->query('status') : '',
        ];
        $like = '%' . addcslashes($filters['q'], '%_\\') . '%';

        $users = User::where('role', 'user')
            ->withCount('orders')
            ->when($filters['q'] !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('full_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like)))
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'filters'));
    }

    /** GET /admin/users/{user} */
    public function show(User $user)
    {
        abort_if($user->isAdmin(), 404);   // trang này chỉ dành cho khách hàng

        $user->load('addresses');

        return view('admin.users.show', [
            'user'       => $user,
            'orders'     => $user->orders()->latest('id')->paginate(10),
            'totalSpent' => (int) $user->orders()->where('payment_status', 'paid')->where('status', '!=', 'cancelled')->sum('total_amount'),
        ]);
    }

    /** PATCH /admin/users/{user}/toggle-status — active <-> locked. User bị khóa sẽ bị đăng xuất ở request kế tiếp (EnsureUserIsActive) */
    public function toggleStatus(Request $request, User $user)
    {
        if ($user->isAdmin() || (int) $user->id === (int) $request->user()->id) {
            return back()->with('error', 'Không thể khóa tài khoản quản trị hoặc chính bạn.');
        }

        $user->status = $user->isActive() ? 'locked' : 'active';   // 'status' không nằm trong $fillable nên gán tay
        $user->save();

        return back()->with('success', $user->isActive() ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.');
    }
}
