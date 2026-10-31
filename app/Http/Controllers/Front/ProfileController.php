<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Traits\ApiResponse;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Thông tin cá nhân + sổ địa chỉ. Sổ địa chỉ chỉ để chọn nhanh khi checkout; đơn cũ đã lưu snapshot nên xóa địa chỉ không ảnh hưởng. */
class ProfileController extends Controller
{
    use ApiResponse;

    private const MAX_ADDRESSES = 10;

    /** GET /profile — view front.profile nhận $user, $addresses (mặc định lên đầu) */
    public function edit(Request $request)
    {
        $user = $request->user();

        return view('front.profile', [
            'user'      => $user,
            'addresses' => $user->addresses()->orderByDesc('is_default')->latest('id')->get(),
        ]);
    }

    /** PUT /profile — multipart nếu có avatar (form cần enctype="multipart/form-data") */
    public function update(ProfileUpdateRequest $request)
    {
        $user = $request->user();
        $v    = $request->validated();

        $data = [
            'full_name' => $v['full_name'],
            'phone'     => $v['phone'] ?? null,
        ];

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }
        if (!empty($v['password'])) {
            $data['password'] = $v['password'];   // cast 'hashed' của User tự hash
        }

        $oldAvatar = $user->avatar;
        $user->update($data);                      // role/status/email không nằm trong $fillable/rules

        if (isset($data['avatar']) && $oldAvatar && !str_starts_with($oldAvatar, 'http')) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return redirect()->route('profile.edit')->with('success', 'Đã cập nhật thông tin cá nhân.');
    }

    /** POST /profile/addresses — body: receiver_name, receiver_phone, address, is_default? */
    public function storeAddress(Request $request)
    {
        $data = $request->validate([
            'receiver_name'  => ['required', 'string', 'max:100'],
            'receiver_phone' => ['required', 'regex:/^(0|\+84)[0-9]{9}$/'],
            'address'        => ['required', 'string', 'min:10', 'max:500'],
            'is_default'     => ['nullable', 'boolean'],
        ], [
            'receiver_name.required'  => 'Vui lòng nhập họ tên người nhận.',
            'receiver_phone.required' => 'Vui lòng nhập số điện thoại.',
            'receiver_phone.regex'    => 'Số điện thoại không hợp lệ (vd: 0901234567).',
            'address.required'        => 'Vui lòng nhập địa chỉ.',
            'address.min'             => 'Địa chỉ quá ngắn, vui lòng ghi đầy đủ.',
            'address.max'             => 'Địa chỉ tối đa 500 ký tự.',
        ]);

        $user = $request->user();

        if ($user->addresses()->count() >= self::MAX_ADDRESSES) {
            return back()->with('error', 'Bạn chỉ lưu được tối đa ' . self::MAX_ADDRESSES . ' địa chỉ.');
        }

        DB::transaction(function () use ($user, $data, $request) {
            // Địa chỉ đầu tiên luôn là mặc định; nếu chọn mặc định thì bỏ mặc định của các địa chỉ cũ
            $makeDefault = !$user->addresses()->exists() || $request->boolean('is_default');
            if ($makeDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            $user->addresses()->create(array_merge($data, ['is_default' => $makeDefault]));
        });

        return back()->with('success', 'Đã thêm địa chỉ mới.');
    }

    /** DELETE /profile/addresses/{address} */
    public function destroyAddress(UserAddress $address)
    {
        abort_unless((int) $address->user_id === (int) auth()->id(), 403);

        DB::transaction(function () use ($address) {
            $wasDefault = $address->is_default;
            $address->delete();

            // Xóa địa chỉ mặc định -> đẩy địa chỉ mới nhất còn lại lên làm mặc định
            if ($wasDefault) {
                auth()->user()->addresses()->latest('id')->first()?->update(['is_default' => true]);
            }
        });

        return back()->with('success', 'Đã xóa địa chỉ.');
    }

    /** PATCH /profile/addresses/{address}/default */
    public function setDefaultAddress(UserAddress $address)
    {
        abort_unless((int) $address->user_id === (int) auth()->id(), 403);

        DB::transaction(function () use ($address) {
            auth()->user()->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return back()->with('success', 'Đã đặt làm địa chỉ mặc định.');
    }
}
