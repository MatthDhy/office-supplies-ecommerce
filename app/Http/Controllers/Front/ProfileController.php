<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * [Thành viên B] Thông tin cá nhân + sổ địa chỉ.
 *
 * BẢN KHUNG: mỗi method có sẵn ĐƯỜNG ĐI các bước cần làm (đọc theo thứ tự 1 -> n).
 * Làm xong thì xóa dòng abort(501) và viết code thật.
 */
class ProfileController extends Controller
{
    use ApiResponse;

    /** GET /profile */
    public function edit(Request $request)
    {
        // 1. Lấy user + addresses
        // 2. return view('front.profile')
        abort(501, 'TODO [Thành viên B]: ProfileController@edit');
    }

    /** PUT /profile */
    public function update(Request $request)
    {
        // 1. Tạo ProfileUpdateRequest: full_name, phone, avatar (image|max:2048), đổi mật khẩu (tùy chọn)
        // 2. Cập nhật $request->user()->update([...]) — KHÔNG cho sửa role/status/email tùy tiện
        // 3. redirect back with success
        abort(501, 'TODO [Thành viên B]: ProfileController@update');
    }

    /** POST /profile/addresses */
    public function storeAddress(Request $request)
    {
        // 1. validate receiver_name, receiver_phone, address
        // 2. Nếu is_default: bỏ default của các địa chỉ cũ
        // 3. $request->user()->addresses()->create([...])
        abort(501, 'TODO [Thành viên B]: ProfileController@storeAddress');
    }

    /** DELETE /profile/addresses/{address} */
    public function destroyAddress(\App\Models\UserAddress $address)
    {
        // 1. abort_unless($address->user_id === auth()->id(), 403)
        // 2. $address->delete()
        abort(501, 'TODO [Thành viên B]: ProfileController@destroyAddress');
    }

    /** PATCH /profile/addresses/{address}/default */
    public function setDefaultAddress(\App\Models\UserAddress $address)
    {
        // 1. abort_unless thuộc về user
        // 2. Bỏ default các địa chỉ khác, set cái này
        abort(501, 'TODO [Thành viên B]: ProfileController@setDefaultAddress');
    }
}
