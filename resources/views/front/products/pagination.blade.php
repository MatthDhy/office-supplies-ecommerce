{{-- Phân trang kiểu Figma: nút ← 1 2 →, trang hiện tại nền ink. Dùng: $paginator->links('front.products._pagination') --}}
@if ($paginator->hasPages())
    <nav aria-label="Phân trang">
        <ul class="cat-pages">
            @if ($paginator->onFirstPage())
                <li><span class="cat-page is-disabled" aria-label="Trang trước" aria-disabled="true">←</span></li>
            @else
                <li><a class="cat-page" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Trang trước">←</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="cat-page is-disabled">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="cat-page is-active" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a class="cat-page" href="{{ $url }}" aria-label="Trang {{ $page }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a class="cat-page" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Trang sau">→</a></li>
            @else
                <li><span class="cat-page is-disabled" aria-label="Trang sau" aria-disabled="true">→</span></li>
            @endif
        </ul>
    </nav>
@endif