/**
 * JS DÙNG CHUNG CHO TOÀN SITE (cần jQuery + Bootstrap bundle đã load trước).
 * Thêm handler riêng của từng trang bằng @push('scripts') trong view, hoặc thêm vào cuối file này.
 */

// 1) Mọi request AJAX tự gắn CSRF token + yêu cầu JSON
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'Accept': 'application/json',
    },
});

// 2) Toast: toast('Đã thêm vào giỏ', 'success') | toast('Lỗi...', 'danger')
window.toast = function (message, type = 'success') {
    const el = $(`
        <div class="toast align-items-center text-bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`);
    el.find('.toast-body').text(message); // .text() => chống XSS
    $('#toast-container').append(el);
    const t = new bootstrap.Toast(el[0], { delay: 3000 });
    t.show();
    el[0].addEventListener('hidden.bs.toast', () => el.remove());
};

// 3) Hàm gọi API chuẩn: trả JSON {success, message, data}. Tự toast lỗi.
//    ajaxCall('POST', url, {product_id: 1}).done(res => {...})
window.ajaxCall = function (method, url, data = {}) {
    return $.ajax({ url, method, data })
        .fail(xhr => {
            let msg = xhr.responseJSON?.message || 'Có lỗi xảy ra, vui lòng thử lại.';
            if (xhr.status === 401) { window.location.href = '/login'; return; }
            toast(msg, 'danger');
        });
};

// 4) Cập nhật số trên icon giỏ hàng
window.setCartCount = n => $('#cart-count').text(n);

// 5) Nút "Thêm vào giỏ" (class .btn-add-to-cart, data-id, data-url) — dùng được ở mọi trang
$(document).on('click', '.btn-add-to-cart', function () {
    const $btn = $(this).prop('disabled', true);
    ajaxCall('POST', $btn.data('url'), { product_id: $btn.data('id'), quantity: $btn.data('qty') || 1 })
        .done(res => { setCartCount(res.data.count); toast(res.message); })
        .always(() => $btn.prop('disabled', false));
});

// 6) [B] TODO: handler .btn-wishlist (gọi wishlist.toggle, đổi icon bi-heart <-> bi-heart-fill)
