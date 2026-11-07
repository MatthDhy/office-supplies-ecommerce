@extends('layouts.app')

@section('title', 'Tài khoản của tôi')

@push('styles')
<link href="{{ asset('assets/css/pages/catalog.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/pages/profile.css') }}" rel="stylesheet">
@endpush

{{-- ProfileController@edit truyền vào: $user, $addresses (địa chỉ mặc định lên đầu) --}}
@section('content')
@php
    $avatarUrl = $user->avatar
        ? (str_starts_with($user->avatar, 'http') ? $user->avatar : asset('storage/' . $user->avatar))
        : null;
    $initial   = mb_strtoupper(mb_substr($user->full_name ?? '?', 0, 1));
    $maxAddr   = 10;                                  // phải khớp ProfileController::MAX_ADDRESSES
    $atLimit   = $addresses->count() >= $maxAddr;
    $pwErrors  = $errors->hasAny(['current_password', 'password']);
    $addrErrors = $errors->hasAny(['receiver_name', 'receiver_phone', 'address', 'is_default']);
@endphp

<div class="pc-container cat">

    <nav class="cat-crumb" aria-label="Đường dẫn">
        <a href="{{ route('home') }}">Trang chủ</a> /
        <span aria-current="page">Tài khoản của tôi</span>
    </nav>

    <header class="cat-head">
        <div>
            <h1 class="cat-title">Tài khoản của tôi</h1>
            <p class="cat-summary">Quản lý thông tin cá nhân và sổ địa chỉ giao hàng.</p>
        </div>
    </header>

    <hr class="cat-rule">

    <div class="pf-layout">

        {{-- ============ THÔNG TIN CÁ NHÂN ============ --}}
        <section class="pf-box" aria-labelledby="pf-info-title">
            <h2 id="pf-info-title" class="pf-h2">Thông tin cá nhân</h2>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="pf-form" novalidate data-lock>
                @csrf
                @method('PUT')

                {{-- Avatar: bấm vào ảnh tròn HOẶC nút "Tải ảnh lên" đều mở hộp chọn file (cùng một input) --}}
                <div class="pf-avatar">
                    <input type="file" id="pf-avatar" name="avatar" accept="image/png,image/jpeg,image/webp" class="pf-file">

                    <label for="pf-avatar" class="pf-avatar__img" id="pf-avatar-box" title="Bấm để đổi ảnh đại diện">
                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="Ảnh đại diện" id="pf-avatar-preview">
                        @else
                            <span id="pf-avatar-initial" aria-hidden="true">{{ $initial }}</span>
                        @endif
                    </label>

                    <div class="pf-avatar__side">
                        <label for="pf-avatar" class="pc-btn pc-btn--outline pf-avatar__btn">Tải ảnh lên</label>
                        <p class="pf-hint" id="pf-avatar-hint">Chọn ảnh cho tài khoản của bạn. JPG, PNG hoặc WebP, tối đa 2MB.</p>
                        @error('avatar') <p class="pf-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="pf-field">
                    <label for="pf-name" class="pf-label">Họ và tên</label>
                    <input id="pf-name" type="text" name="full_name" class="pf-input" maxlength="100" required
                           value="{{ old('full_name', $user->full_name) }}" autocomplete="name">
                    @error('full_name') <p class="pf-error">{{ $message }}</p> @enderror
                </div>

                <div class="pf-field">
                    <label for="pf-phone" class="pf-label">Số điện thoại</label>
                    <input id="pf-phone" type="tel" name="phone" class="pf-input" inputmode="tel" placeholder="0901234567"
                           value="{{ old('phone', $user->phone) }}" autocomplete="tel">
                    @error('phone') <p class="pf-error">{{ $message }}</p> @enderror
                </div>

                <div class="pf-field">
                    <label for="pf-email" class="pf-label">Email · chỉ đọc</label>
                    <input id="pf-email" type="email" class="pf-input" value="{{ $user->email }}" disabled>
                    <p class="pf-hint">Email đăng nhập không thể thay đổi tại đây.</p>
                </div>

                {{-- Đổi mật khẩu (tùy chọn): tự mở nếu có lỗi --}}
                <details class="pf-details" @if ($pwErrors) open @endif>
                    <summary>Đổi mật khẩu</summary>

                    <div class="pf-field">
                        <label for="pf-cur" class="pf-label">Mật khẩu hiện tại</label>
                        <input id="pf-cur" type="password" name="current_password" class="pf-input" autocomplete="current-password">
                        @error('current_password') <p class="pf-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="pf-field">
                        <label for="pf-new" class="pf-label">Mật khẩu mới</label>
                        <input id="pf-new" type="password" name="password" class="pf-input" autocomplete="new-password">
                        <p class="pf-hint">Tối thiểu 6 ký tự. Để trống nếu không đổi.</p>
                        @error('password') <p class="pf-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="pf-field">
                        <label for="pf-new2" class="pf-label">Nhập lại mật khẩu mới</label>
                        <input id="pf-new2" type="password" name="password_confirmation" class="pf-input" autocomplete="new-password">
                    </div>
                </details>

                <button type="submit" class="pc-btn pc-btn--primary pf-submit">Lưu thay đổi</button>
            </form>
        </section>

        {{-- ============ SỔ ĐỊA CHỈ ============ --}}
        <section class="pf-box" aria-labelledby="pf-addr-title">
            <h2 id="pf-addr-title" class="pf-h2">Sổ địa chỉ <span class="pf-count">({{ $addresses->count() }}/{{ $maxAddr }})</span></h2>

            @forelse ($addresses as $addr)
                <article class="pf-addr {{ $addr->is_default ? 'is-default' : '' }}">
                    <div class="pf-addr__body">
                        <p class="pf-addr__name">
                            {{ $addr->receiver_name }}
                            @if ($addr->is_default) <span class="pf-badge">Mặc định</span> @endif
                        </p>
                        <p class="pf-addr__phone">{{ $addr->receiver_phone }}</p>
                        <p class="pf-addr__text">{{ $addr->address }}</p>
                    </div>

                    <div class="pf-addr__actions">
                        @unless ($addr->is_default)
                            <form method="POST" action="{{ route('profile.addresses.default', $addr) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="pf-link">Đặt làm mặc định</button>
                            </form>
                        @endunless

                        <form method="POST" action="{{ route('profile.addresses.destroy', $addr) }}"
                              onsubmit="return confirm('Xóa địa chỉ này? Các đơn hàng cũ không bị ảnh hưởng.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="pf-link pf-link--danger">Xóa</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="pf-empty">Bạn chưa lưu địa chỉ nào. Địa chỉ đã lưu giúp bạn chọn nhanh khi thanh toán.</p>
            @endforelse

            {{-- Thêm địa chỉ: tự mở nếu có lỗi --}}
            @if ($atLimit)
                <p class="pf-hint">Bạn đã lưu tối đa {{ $maxAddr }} địa chỉ. Hãy xóa bớt để thêm địa chỉ mới.</p>
            @else
                <details class="pf-details" @if ($addrErrors) open @endif>
                    <summary>Thêm địa chỉ mới</summary>

                    <form method="POST" action="{{ route('profile.addresses.store') }}" class="pf-form" novalidate data-lock>
                        @csrf

                        <div class="pf-field">
                            <label for="ad-name" class="pf-label">Họ tên người nhận</label>
                            <input id="ad-name" type="text" name="receiver_name" class="pf-input" maxlength="100"
                                   value="{{ old('receiver_name') }}" autocomplete="off">
                            @error('receiver_name') <p class="pf-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="pf-field">
                            <label for="ad-phone" class="pf-label">Số điện thoại</label>
                            <input id="ad-phone" type="tel" name="receiver_phone" class="pf-input" inputmode="tel"
                                   placeholder="0901234567" value="{{ old('receiver_phone') }}" autocomplete="off">
                            @error('receiver_phone') <p class="pf-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="pf-field">
                            <label for="ad-address" class="pf-label">Địa chỉ</label>
                            <textarea id="ad-address" name="address" rows="3" maxlength="500" class="pf-input"
                                      placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành">{{ old('address') }}</textarea>
                            @error('address') <p class="pf-error">{{ $message }}</p> @enderror
                        </div>

                        <label class="pf-check">
                            <input type="checkbox" name="is_default" value="1" @checked(old('is_default'))>
                            Đặt làm địa chỉ mặc định
                        </label>

                        <button type="submit" class="pc-btn pc-btn--outline pf-submit">Lưu địa chỉ</button>
                    </form>
                </details>
            @endif
        </section>

    </div>
</div>
@endsection

@push('scripts')
<script>
// Xem trước avatar ngay khi chọn file (chưa upload cho tới khi bấm "Lưu thay đổi")
(function () {
    const input = document.getElementById('pf-avatar');
    const box   = document.getElementById('pf-avatar-box');
    if (!input || !box) return;

    input.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file || !file.type.startsWith('image/')) return;

        let img = document.getElementById('pf-avatar-preview');
        if (!img) {
            box.innerHTML = '';
            img = document.createElement('img');
            img.id = 'pf-avatar-preview';
            img.alt = 'Ảnh đại diện (xem trước)';
            box.appendChild(img);
        }
        img.src = URL.createObjectURL(file);

        const hint = document.getElementById('pf-avatar-hint');
        if (hint) hint.textContent = 'Đã chọn: ' + file.name + ' — bấm "Lưu thay đổi" để cập nhật.';
    });
})();

// Chống bấm lưu nhiều lần + cảnh báo khi rời trang mà chưa lưu (áp dụng cho form có data-lock)
(function () {
    const forms = document.querySelectorAll('form[data-lock]');
    let dirty = false;
    let submitting = false;

    forms.forEach(function (form) {
        // Có chỉnh sửa (gõ chữ, chọn file, tick checkbox...) -> coi là chưa lưu
        form.addEventListener('input',  () => { dirty = true; });
        form.addEventListener('change', () => { dirty = true; });

        form.addEventListener('submit', function (e) {
            if (submitting) { e.preventDefault(); return; }   // đã gửi rồi, bỏ qua lần bấm tiếp theo
            submitting = true;
            dirty = false;                                    // đang lưu: không hỏi khi trang chuyển đi

            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.dataset.label = btn.textContent;
                btn.textContent = 'Đang lưu…';
                btn.setAttribute('aria-disabled', 'true');
                btn.classList.add('is-busy');
            }
        });
    });

    window.addEventListener('beforeunload', function (e) {
        if (!dirty) return;
        e.preventDefault();
        e.returnValue = '';                                   // trình duyệt tự hiện hộp thoại chuẩn
    });

    // Bấm nút Back rồi quay lại trang (trình duyệt giữ trạng thái cũ): mở khóa nút
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        submitting = false;
        forms.forEach(function (form) {
            const btn = form.querySelector('button[type="submit"]');
            if (btn && btn.dataset.label) {
                btn.textContent = btn.dataset.label;
                btn.removeAttribute('aria-disabled');
                btn.classList.remove('is-busy');
            }
        });
    });
})();
</script>
@endpush