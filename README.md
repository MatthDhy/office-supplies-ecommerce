# 🛒 Website bán văn phòng phẩm (Laravel 11)

Đồ án: website thương mại điện tử bán văn phòng phẩm + khu quản trị cho Admin.
**Stack:** Laravel 11 · PHP 8.3 · MySQL 8 · Blade · Bootstrap 5 · jQuery/AJAX · Chart.js · PayOS/VietQR

> 📌 **Đọc README này trước khi code.** Mất khoảng 15 phút, giúp cả nhóm đi cùng một đường và ít xung đột Git.

---

## 1. Leader đã dựng sẵn những gì?

Repo này là **khung sườn (skeleton)**. Phần nền đã xong để mọi người chỉ việc "điền" tính năng của mình:

| ✅ Đã xong | Ý nghĩa với bạn |
|---|---|
| **Database**: 11 migration + 10 model + quan hệ | Không cần tạo bảng, chỉ dùng `Product::...`, `$order->items`... |
| **Seeder**: admin, 2 user, 6 danh mục, 16 sản phẩm | Có dữ liệu thật để làm giao diện ngay |
| **Đăng ký / Đăng nhập / Đăng xuất / phân quyền** | `auth` (đã đăng nhập) và `admin` (chỉ admin) đã chạy |
| **Toàn bộ route + tên route** (`routes/web.php`, `routes/admin.php`) | Không cần tự khai báo URL, chỉ viết controller + view |
| **Layout Front + Admin, header, footer, product-card** | Trang mới chỉ cần `@extends` là có khung |
| **Trang chủ, giỏ hàng AJAX (CartService)** | Là **bài mẫu**: xem cách viết AJAX chuẩn |
| **`app.js` dùng chung**: CSRF, `toast()`, `ajaxCall()`, nút thêm giỏ | Gọi AJAX và thông báo không cần viết lại |
| **Controller/Service khung** cho mọi trang còn lại | Mỗi method có sẵn các bước `// 1. 2. 3.` cần làm |

Phần **còn trống** (có dòng `abort(501, 'TODO ...')`) là việc của từng người, xem mục 6.

---

## 2. Bức tranh lớn: webapp vận hành thế nào?

Mọi tính năng đều đi **cùng một đường**:

```
 Trình duyệt (Blade + jQuery)
      │ 1. bấm nút / submit form
      ▼
 routes/web.php | routes/admin.php        ← URL nào → controller nào; gắn middleware auth/admin
      ▼
 Middleware                               ← chưa đăng nhập? không phải admin? → chặn tại đây
      ▼
 FormRequest (validate)                   ← dữ liệu sai → trả lỗi, dừng
      ▼
 Controller  (MỎNG: chỉ điều phối)
      │ nghiệp vụ phức tạp (tiền, đơn, kho)?
      ├────────► Service (CartService / CouponService / OrderService / PaymentService)
      ▼
 Model (Eloquent) ───► MySQL
      ▼
 View Blade (trả HTML)     hoặc     JSON {success, message, data}   (AJAX)
```

### Ví dụ thật: bấm "Thêm vào giỏ"

```
product-card.blade.php   nút .btn-add-to-cart (data-id, data-url)
        │
public/assets/js/app.js  ajaxCall('POST', url, {product_id})   ← tự gắn CSRF token
        │
routes/web.php           POST /cart/add  →  CartController@add
        │
CartController@add       validate product_id, quantity
        │
CartService::add         kiểm tra: sản phẩm tồn tại? đang active? số lượng ≤ tồn kho?
        │                lưu vào SESSION:  cart = { product_id => quantity }
        ▼
JSON  { success:true, message:"Đã thêm vào giỏ hàng", data:{count:3} }
        │
app.js                   cập nhật số trên icon giỏ + hiện toast
```

### Ví dụ: đăng nhập admin

```
/login → AuthController@login → Auth::attempt(email, password, status=active)
       → role = admin ?  redirect /admin  :  redirect trang chủ
/admin → middleware: web → auth → admin (AdminMiddleware) → DashboardController
```

### Luồng mua hàng toàn hệ thống (để biết dữ liệu của mình đến từ đâu)

```
Duyệt SP → Chi tiết → Giỏ (session) → Coupon → Checkout → Tạo Order + OrderItems
   [B]        [B]        [A]            [A]       [A]            [A]
        → Thanh toán COD/PayOS [A] → Admin cập nhật trạng thái [C]
        → pending → confirmed → processing → shipping → completed
        → User xem đơn [B] → completed thì được Review [B]
```

---

## 3. Ba nguyên tắc KHÔNG được vi phạm

1. **Không tin dữ liệu từ client.** Giá, tổng tiền, giảm giá luôn **tính lại ở backend** từ database. Client chỉ gửi `product_id` và `quantity`.
2. **Tiền / đơn hàng / kho → bọc trong `DB::transaction`.** Để lỗi giữa chừng không làm lệch dữ liệu.
3. **`order_items` lưu snapshot** (`unit_price`, `product_name`). Admin đổi giá sau này, đơn cũ không đổi.

Hệ quả khi code: **luôn dùng `$product->final_price`** (đã tính sẵn giá sale), không tự lấy `price` hay `sale_price` rồi tính lại.

---

## 4. Cấu trúc thư mục: nên đọc / được sửa / đừng đụng

Ký hiệu: 👀 đọc để hiểu · ✏️ là chỗ bạn code · 🔒 chỉ Leader sửa (cần gì thì báo) · 🚫 không đụng

```
WebBanVanPhongPham/
│
├── app/                              ← TOÀN BỘ LOGIC NẰM Ở ĐÂY
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                 👀 AuthController (đăng nhập/ký)         🔒
│   │   │   ├── Front/                ← controller khu KHÁCH/USER              ✏️ [A][B]
│   │   │   └── Admin/                ← controller khu QUẢN TRỊ                ✏️ [C]
│   │   ├── Middleware/               🔒 AdminMiddleware (chặn người không phải admin)
│   │   ├── Requests/                 ✏️ nơi bạn tạo FormRequest (validate) của mình
│   │   └── Traits/ApiResponse.php    👀 chuẩn JSON chung: success() / fail()
│   ├── Models/                       🔒 Product, Order, User... (1 file = 1 bảng)
│   └── Services/                     🔒/✏️ nghiệp vụ phức tạp: Cart, Coupon, Order, Payment [A]
│
├── bootstrap/app.php                 🔒 đăng ký routes/admin.php + middleware 'admin' + CSRF webhook
│
├── database/
│   ├── migrations/                   🔒 định nghĩa cấu trúc bảng (11 file)
│   └── seeders/                      🔒 dữ liệu mẫu (chạy bằng --seed)
│
├── public/                           ← thư mục web gốc (trình duyệt truy cập được)
│   ├── assets/css/app.css            ⚠️ CSS chung – dễ xung đột, xem mục 7
│   ├── assets/js/app.js              ⚠️ JS chung – dễ xung đột, xem mục 7
│   └── storage/                      🚫 (liên kết tới ảnh upload, tạo bởi storage:link)
│
├── resources/views/                  ← GIAO DIỆN (Blade)
│   ├── layouts/                      🔒 app.blade.php (khách) · admin.blade.php (quản trị)
│   ├── partials/                     👀 header, footer, product-card (dùng lại khắp nơi)
│   ├── auth/                         🔒 login, register
│   ├── front/                        ✏️ view khu khách: home, cart, products/, orders/...
│   └── admin/                        ✏️ view khu admin: dashboard, products/, orders/...
│
├── routes/
│   ├── web.php                       🔒 route khách/user (đã khai báo SẴN hết)
│   └── admin.php                     🔒 route admin (tự có prefix /admin, tên admin.*)
│
├── storage/                          🚫 log, session, file upload (Laravel tự quản lý)
├── vendor/                           🚫 thư viện Composer (không commit)
├── config/  tests/                   🚫 cấu hình mặc định Laravel (ít khi cần sửa)
├── .env                              🚫 cấu hình RIÊNG từng máy (KHÔNG commit)
└── .env.example                      mẫu để copy thành .env
```

### Công dụng từng thư mục (giải thích nhanh)

| Thư mục | Công dụng | Ví dụ |
|---|---|---|
| `routes/` | "Bản đồ" URL → hàm xử lý | `GET /products` → `ProductController@index` |
| `Controllers/` | Nhận request, gọi Model/Service, trả view hoặc JSON. **Giữ mỏng.** | `WishlistController@toggle` |
| `Requests/` | Validate dữ liệu, thông báo lỗi tiếng Việt | `ReviewRequest`: rating từ 1–5 |
| `Services/` | Nghiệp vụ nhiều bước, đặc biệt liên quan tiền | `OrderService::createFromCart` |
| `Models/` | Đại diện bảng DB, quan hệ, scope, accessor | `Product::active()->featured()` |
| `Middleware/` | Chặn/cho phép request trước khi vào controller | `admin` |
| `migrations/` | Lịch sử tạo/sửa bảng | `create_products_table` |
| `seeders/` | Dữ liệu mẫu để dev | admin, sản phẩm mẫu |
| `views/` | HTML (Blade) | `front/home.blade.php` |
| `public/assets` | CSS/JS tĩnh | `app.js` |

### Những "công cụ" có sẵn, hãy dùng thay vì tự viết

| Muốn làm gì | Dùng cái này |
|---|---|
| Lấy sản phẩm đang bán | `Product::active()` (scope), `->featured()`, `->onSale()` |
| Giá khách phải trả | `$product->final_price` (số) · `->final_price_text` (chuỗi `12.000₫`) |
| Ảnh sản phẩm (có placeholder) | `$product->image_url` |
| Trả JSON cho AJAX | `$this->success($data, 'msg')` / `$this->fail('msg')` (trait `ApiResponse`) |
| Gọi AJAX từ JS | `ajaxCall('POST', url, data).done(res => ...)` |
| Thông báo | `toast('Đã lưu', 'success')` / `toast('Lỗi', 'danger')` |
| Thông báo sau redirect | `->with('success', '...')` (layout tự hiện toast) |
| Thẻ sản phẩm | `@include('partials.product-card', ['product' => $p])` |
| Giỏ hàng | `CartService` (`add/update/remove/detail/count`) |
| Trạng thái đơn hợp lệ | `$order->canChangeTo('shipping')`, `Order::LABELS` |
| Wishlist | `$user->wishlistProducts()->toggle($productId)` |

**JSON AJAX luôn có dạng:**
```json
{ "success": true, "message": "Đã thêm vào giỏ hàng", "data": { "count": 3 }, "errors": null }
```

---

## 5. Cài đặt máy mới (mỗi người làm 1 lần)

```bash
# 1. Clone repo (dùng Terminal của Laragon để có sẵn PHP/Composer)
git clone <URL-repo> WebBanVanPhongPham
cd WebBanVanPhongPham

# 2. Cài thư viện + tạo .env
composer install
copy .env.example .env          # macOS/Linux: cp .env.example .env
php artisan key:generate
```

**3. Tạo database** (HeidiSQL/phpMyAdmin):
```sql
CREATE DATABASE web_van_phong_pham CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**4. Sửa `.env`** (mỗi máy một file, không commit):
```ini
APP_NAME="Văn Phòng Phẩm"
APP_LOCALE=vi
APP_TIMEZONE=Asia/Ho_Chi_Minh
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=web_van_phong_pham
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file      # giỏ hàng nằm trong session, không dùng bảng sessions
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

```bash
# 5. Tạo bảng + dữ liệu mẫu + liên kết ảnh, rồi chạy
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve                 # mở http://127.0.0.1:8000
```

**Tài khoản mẫu:**

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | `admin@shop.test` | `Admin@123` |
| User | `user1@shop.test` | `User@123` |

**Kiểm tra OK:** trang chủ có sản phẩm → bấm "Thêm vào giỏ" thì số giỏ nhảy → đăng nhập admin vào được `/admin`.

**Mỗi lần `git pull` mà Leader có sửa DB** (migration/seeder), chạy lại: `php artisan migrate:fresh --seed` (xóa dữ liệu dev cũ, không sao).

---

## 6. Phân công

| Người | Vai trò | Phụ trách |
|---|---|---|
| **A: Leader** | Nền tảng + luồng tiền | Khung, DB, Auth, Trang chủ, **Giỏ hàng, Coupon, Checkout + Order, Payment (COD/PayOS)**, bảo mật, tích hợp |
| **B** | Storefront | **Danh sách SP (search/lọc/sort AJAX), Chi tiết SP, Wishlist, Review, Lịch sử đơn/hủy đơn, Profile + địa chỉ**, trang lỗi 403/404/500 |
| **C** | Admin | **Dashboard + Chart.js, CRUD Danh mục, CRUD Sản phẩm + upload ảnh, Quản lý Đơn hàng, Quản lý User, CRUD Coupon, Quản lý Review** |

File bạn sẽ sửa (tìm dòng `abort(501`):

| Người | Controller | Service | View tự tạo |
|---|---|---|---|
| **A** | `Front/Checkout`, `Coupon`, `Payment` | `Coupon`, `Order`, `Payment` | `front/cart`, `front/checkout` |
| **B** | `Front/Product`, `Wishlist`, `Review`, `Order`, `Profile` | — | `front/products/*`, `front/orders/*`, `front/wishlist`, `front/profile`, `errors/*` |
| **C** | toàn bộ `Admin/*` | — | `admin/categories/*`, `admin/products/*`, `admin/orders/*`... |

### Thứ tự làm gợi ý

| Giai đoạn | A | B | C |
|---|---|---|---|
| **0** | Khung (đã xong) | Cài máy, chạy được, đọc code | Cài máy, chạy được, đọc code |
| **1** | View giỏ hàng AJAX | Danh sách SP + lọc/sort/phân trang | CRUD Danh mục → CRUD Sản phẩm + ảnh |
| **2** | Checkout + OrderService + COD | Chi tiết SP + Wishlist | Quản lý Đơn + User |
| **3** | Coupon + PayOS/webhook | Lịch sử đơn + hủy + Profile | CRUD Coupon + Dashboard Chart.js |
| **4** | Bảo mật, review code, deploy | Review + trang lỗi | Quản lý Review + hoàn thiện UI |

> Chưa có đơn hàng thật để làm "Lịch sử đơn" (B) hoặc "Quản lý đơn" (C)? Tạo đơn giả bằng `php artisan tinker` hoặc seeder riêng trên máy mình, không cần chờ A.

---

## 7. Quy trình làm việc với Git

```
main  ← luôn chạy được, chỉ merge qua Pull Request
 ├── feature/b-product-list
 ├── feature/b-wishlist
 ├── feature/c-category-crud
 └── feature/a-checkout
```

```bash
git checkout main && git pull origin main          # 1. luôn cập nhật trước khi bắt đầu
git checkout -b feature/b-product-list             # 2. tạo nhánh cho 1 tính năng
# ... code, test trên trình duyệt ...
git add .
git commit -m "feat(product): lọc theo giá bằng AJAX"   # 3. commit nhỏ, rõ nghĩa
git push origin feature/b-product-list             # 4. đẩy nhánh, mở Pull Request trên GitHub
```
Leader review → merge vào `main` → mọi người `git pull origin main`.

**Quy ước commit:** `feat(...)` tính năng mới · `fix(...)` sửa lỗi · `refactor(...)` · `docs(...)` · `chore(...)`

### Tránh xung đột: các file "dễ đụng nhau"

| File | Cách làm |
|---|---|
| `public/assets/js/app.js`, `app.css` | Code riêng của trang → để trong view bằng `@push('scripts')` / `@push('styles')`. Chỉ sửa file chung khi thật sự dùng chung |
| `routes/web.php`, `admin.php` | Route đã khai báo sẵn. Cần route mới → thêm đúng nhóm của mình, commit riêng, nhắn Leader |
| `migrations/`, `Models/`, `seeders/` | **Chỉ Leader sửa.** Cần thêm cột/bảng → nhắn Leader (hoặc tạo PR để Leader review) |
| `layouts/*`, `partials/*` | Của chung. Sửa nhỏ OK nhưng phải nói trong PR |

---

## 8. Công thức 7 bước cho mọi tính năng

Làm **trọn từng tính năng một** (vertical slice), không làm "hết controller rồi mới view":

```
① Route  →  ② Request  →  ③ Controller  →  ④ Service/Model  →  ⑤ View  →  ⑥ JS (nếu AJAX)  →  ⑦ Test
 (có sẵn)   (validate)     (điều phối)      (nghiệp vụ, DB)      (Blade)     (jQuery)            (trình duyệt)
```

1. **Route:** xem route + *tên route* đã có, dùng `route('ten.route')` trong view, không gõ URL cứng.
2. **Request:** `php artisan make:request TenRequest`, viết `rules()` + `messages()` tiếng Việt.
3. **Controller:** mở bản khung, đọc các bước `// 1. 2. 3.`, biến thành code, rồi **xóa dòng `abort(501)`**.
4. **Model/Service:** query bằng Eloquent; nhiều bước về tiền/kho thì `DB::transaction`.
5. **View:** `@extends('layouts.app')` (hoặc `layouts.admin`) + `@section('content')`.
6. **JS:** dùng `ajaxCall(...)`, server trả JSON qua `ApiResponse`.
7. **Test:** thử luồng đúng **và** luồng sai (thiếu dữ liệu, chưa đăng nhập, user khác truy cập).

### Thứ tự đọc code để hiểu khung (30 phút)

1. `routes/web.php`: biết URL nào → controller nào
2. `Controllers/Auth/AuthController.php`: luồng Form → Request → Model → redirect
3. `Services/CartService.php` + `Controllers/Front/CartController.php`: **mẫu AJAX chuẩn**
4. `views/layouts/app.blade.php` + `public/assets/js/app.js`: layout, toast, `ajaxCall`
5. `Models/Product.php`, `Models/Order.php`: scope, accessor, bảng chuyển trạng thái
6. Controller khung của chính bạn

---

## 9. Checklist trước khi tạo Pull Request

- [ ] Chạy được trên máy mình, không lỗi khi bấm thử cả luồng đúng lẫn sai
- [ ] Không lấy giá / tổng tiền / giảm giá từ request của client
- [ ] Chỉ chủ sở hữu mới xem/sửa được dữ liệu của mình (`abort_unless($x->user_id === auth()->id(), 403)`)
- [ ] Mọi form có `@csrf`; hiển thị dữ liệu người dùng bằng `{{ }}` (không dùng `{!! !!}`)
- [ ] Có thông báo lỗi tiếng Việt và trạng thái rỗng (danh sách trống thì báo gì)
- [ ] Không commit `.env`, `vendor/`, file rác; không để `dd()` / `console.log` thừa
- [ ] Đã xóa `abort(501)` ở phần mình làm xong

## 10. Lỗi thường gặp

| Thông báo | Nguyên nhân / cách xử lý |
|---|---|
| `Unknown database` | Chưa tạo database, hoặc `DB_DATABASE` sai tên |
| `Table 'sessions' doesn't exist` | Quên `SESSION_DRIVER=file` trong `.env` |
| `No application encryption key` | Chạy `php artisan key:generate` |
| Sửa `.env` không ăn | `php artisan config:clear` |
| Trang trắng / lỗi lạ sau khi pull | `composer install` rồi `php artisan optimize:clear` |
| Thiếu bảng/cột sau khi pull | `php artisan migrate:fresh --seed` |
| Ảnh upload không hiện | `php artisan storage:link` |
| Trang báo `501` + `TODO [...]` | Bình thường: đó là phần chưa làm, người phụ trách sẽ làm |
| Lỗi 419 (Page Expired) | Form thiếu `@csrf`, hoặc AJAX chưa gửi token (dùng `ajaxCall`) |

Gặp lỗi khác: chụp **nguyên thông báo lỗi** + nói bạn vừa làm gì, rồi nhắn vào nhóm.
