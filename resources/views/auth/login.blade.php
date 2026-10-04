@extends('layouts.app')
@section('title', 'Đăng nhập')

@section('content')
<div class="container" style="max-width:420px">
    <h1 class="h3 mb-4 text-center">Đăng nhập</h1>
    <form method="POST" action="{{ route('login.submit') }}" class="card card-body shadow-sm">
        @csrf
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Mật khẩu</label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
        </div>
        <button class="btn btn-dark w-100">Đăng nhập</button>
        <p class="text-center small mt-3 mb-0">Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký</a></p>
    </form>
</div>
@endsection
