{{-- Mode terang/gelap: dipasang di <head> supaya tidak berkedip. Tombol: [data-theme-toggle]. --}}
@once
<script>
(function () {
    var KEY = 'gbl-theme', root = document.documentElement;
    function get() { try { return localStorage.getItem(KEY); } catch (e) { return null; } }
    function apply(t) {
        if (t === 'dark') root.setAttribute('data-theme', 'dark'); else root.removeAttribute('data-theme');
        var m = document.querySelectorAll('meta[name="theme-color"]');
        for (var i = 0; i < m.length; i++) m[i].setAttribute('content', t === 'dark' ? '#0f1612' : '#f3f1ea');
    }
    apply(get() === 'dark' ? 'dark' : 'light');
    window.gblToggleTheme = function () {
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem(KEY, next); } catch (e) {}
        apply(next);
        document.querySelectorAll('[data-theme-toggle]').forEach(function (b) {
            b.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');
        });
    };
    document.addEventListener('click', function (e) {
        var b = e.target.closest && e.target.closest('[data-theme-toggle]');
        if (b) { e.preventDefault(); window.gblToggleTheme(); }
    });
    document.addEventListener('DOMContentLoaded', function () { apply(root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'); });
})();
</script>
@endonce
