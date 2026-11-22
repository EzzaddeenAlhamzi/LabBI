@if ($paginator->hasPages())
<style>
.labbi-pager{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:.9rem 1rem;font-family:"IBM Plex Sans Arabic",Tahoma,sans-serif}
.labbi-pager .lp-info{font-size:.8rem;color:#64748B}
.labbi-pager ul{display:flex;gap:.3rem;list-style:none;margin:0;padding:0;flex-wrap:wrap}
.labbi-pager li a,.labbi-pager li span{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 .6rem;border-radius:6px;border:1px solid #E2E8F0;background:#fff;color:#334155;font-size:.82rem;font-weight:500;text-decoration:none!important}
.labbi-pager li a:hover{background:#F8FAFC;border-color:#CBD5E1}
.labbi-pager li span[aria-current="page"],.labbi-pager li span[aria-disabled="true"]:first-child{background:#0D9488;border-color:#0D9488;color:#fff}
.labbi-pager li span[aria-disabled="true"]{color:#94A3B8}
</style>
<nav class="labbi-pager" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
    <div class="lp-info">
        {{ $paginator->firstItem() }} – {{ $paginator->lastItem() }} / {{ $paginator->total() }}
    </div>
    <ul>
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <li><span aria-disabled="true">‹ السابق</span></li>
        @else
            <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">‹</a></li>
        @endif

        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <li><span aria-disabled="true">{{ $element }}</span></li>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li><span aria-current="page">{{ $page }}</span></li>
                    @else
                        <li><a href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">›</a></li>
        @else
            <li><span aria-disabled="true">التالي ›</span></li>
        @endif
    </ul>
</nav>
@endif
