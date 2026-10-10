/* Golf Booking Lesson - tombol ganti tema terang / gelap */
(function () {
    'use strict';

    var KEY = 'gbl-theme';
    var MOON = '<svg class="gbl-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z"/></svg>';
    var SUN = '<svg class="gbl-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';

    function current() {
        return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        try { localStorage.setItem(KEY, theme); } catch (e) { /* abaikan */ }
        document.querySelectorAll('[data-gbl-theme-label]').forEach(function (el) {
            el.textContent = theme === 'dark' ? 'Mode terang' : 'Mode gelap';
        });
    }

    function toggle() { setTheme(current() === 'dark' ? 'light' : 'dark'); }

    function makeButton() {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'gbl-theme-btn';
        b.title = 'Ganti tema terang / gelap';
        b.setAttribute('aria-label', 'Ganti tema terang / gelap');
        b.innerHTML = MOON + SUN;
        b.addEventListener('click', toggle);
        return b;
    }

    function init() {
        // Tombol di navbar (laptop & HP)
        var spots = document.querySelectorAll('.sn-right, .admin-page .top-right, .navbar > .user-area');
        spots.forEach(function (spot) {
            if (spot.querySelector('.gbl-theme-btn')) return;
            spot.insertBefore(makeButton(), spot.firstChild);
        });

        // Ubin di menu bawah HP
        var grid = document.querySelector('.mt-grid');
        if (grid && !grid.querySelector('.gbl-theme-tile')) {
            var t = document.createElement('button');
            t.type = 'button';
            t.className = 'mt-tile gbl-theme-tile';
            t.innerHTML = MOON + SUN + '<span data-gbl-theme-label></span>';
            t.style.fontFamily = 'inherit';
            t.addEventListener('click', toggle);
            grid.appendChild(t);
        }

        setTheme(current());
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
