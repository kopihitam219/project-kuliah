{{-- Favicon & deskripsi dari menu Settings. Taruh di dalam <head>. --}}
@if ($brandFavicon = \App\Support\Brand::faviconUrl())
    <link rel="icon" type="image/png" href="{{ $brandFavicon }}">
@endif
<meta name="description" content="{{ \App\Support\Brand::name() }} - {{ \App\Support\Brand::tagline() }}">
