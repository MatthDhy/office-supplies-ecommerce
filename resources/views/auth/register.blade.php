@extends('layouts.app')
@section('title', 'Đăng ký')

@section('content')
<div class="container" style="max-width:480px">
    <h1 class="h3 mb-4 text-center">Đăng ký</h1>
    <form method="POST" action="{{ route('register.submit') }}" class="card card-body shadow-sm">
        @csrf
        @foreach ([['full_name','Họ tên','text'], ['email','Email','email'], ['phone','Số điện thoại','text'], ['password','Mật khẩu','password'], ['password_confirmation','Nhập lại mật khẩu','password']] as [$name, $label, $type])
            <div class="mb-3">
                <label class="form-label">{{ $label }}</label>
                <input type="{{ $type }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : old($name) }}"
                       class="form-control @error($name) is-invalid @enderror">
                @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        @endforeach
        <button class="btn btn-dark w-100">Tạo tài khoản</button>
        <p class="text-center small mt-3 mb-0">Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a></p>
    </form>
</div>
@endsection
