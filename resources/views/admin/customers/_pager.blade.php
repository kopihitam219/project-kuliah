{{-- Navigasi halaman sederhana. Variabel: $paginator --}}
@if ($paginator->hasPages() || $paginator->total() > 0)
    <div class="pager">
        <span>
            Menampilkan {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}
            dari {{ $paginator->total() }}
        </span>

        @if ($paginator->hasPages())
            <div class="pager-links">
                @if ($paginator->onFirstPage())
                    <span>← Sebelumnya</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}">← Sebelumnya</a>
                @endif

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}">Berikutnya →</a>
                @else
                    <span>Berikutnya →</span>
                @endif
            </div>
        @endif
    </div>
@endif
