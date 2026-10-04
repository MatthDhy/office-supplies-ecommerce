<header class="site-header border-bottom bg-white sticky-top">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('home') }}">
                <i class="bi bi-pencil-fill"></i> {{ config('app.name', 'Văn Phòng Phẩm') }}
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                {{-- Ô tìm kiếm: GET /products?q=... (Thành viên B xử lý ở ProductController@index) --}}
                <form class="d-flex mx-lg-4 flex-grow-1" action="{{ route('products.index') }}" method="GET">
                    <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Tìm bút, vở, giấy in...">
                    <button class="btn btn-dark ms-2" type="submit"><i class="bi bi-search"></i></button>
                </form>

                <ul class="navbar-nav align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link" href="{{ route('products.index') }}">Sản phẩm</a></li>

                    @auth
                        <li class="nav-item"><a class="nav-link" href="{{ route('wishlist.index') }}"><i class="bi bi-heart"></i></a></li>
                    @endauth

                    <li class="nav-item">
                        <a class="nav-link position-relative" href="{{ route('cart.index') }}">
                            <i class="bi bi-bag"></i>
                            <span id="cart-count" class="badge rounded-pill bg-danger">{{ app(\App\Services\CartService::class)->count() }}</span>
                        </a>
                    </li>

                    @guest
                        <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Đăng nhập</a></li>
                        <li class="nav-item"><a class="btn btn-dark btn-sm" href="{{ route('register') }}">Đăng ký</a></li>
                    @else
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">{{ auth()->user()->full_name }}</a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @if (auth()->user()->isAdmin())
                                    <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">Trang quản trị</a></li>
                                @endif
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Tài khoản</a></li>
                                <li><a class="dropdown-item" href="{{ route('orders.index') }}">Đơn hàng của tôi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">@csrf
                                        <button class="dropdown-item" type="submit">Đăng xuất</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>
</header>
