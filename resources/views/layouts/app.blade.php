<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    @include('partials.header')

    <main class="py-4">
        @yield('content')
    </main>

    @include('partials.footer')

    {{-- Toast dùng chung: gọi toast('nội dung', 'success'|'danger') từ JS --}}
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toast-container"></div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>

    {{-- Flash message từ redirect()->with('success'|'error', ...) --}}
    @if (session('success')) <script>toast(@json(session('success')), 'success');</script> @endif
    @if (session('error'))   <script>toast(@json(session('error')), 'danger');</script> @endif

    @stack('scripts')
</body>
</html>
