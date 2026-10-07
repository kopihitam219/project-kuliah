/* ======================================================================
   Golf Booking Lesson - penyesuaian tampilan HP (otomatis)
   - Tabel dibungkus agar bisa digeser ke samping.
   - Grid / baris yang kepanjangan di layar HP dirapikan otomatis
     (grid jadi menumpuk, baris flex boleh turun ke bawah).
   Hanya aktif di layar <= 820px; saat layar dilebarkan lagi, kembali normal.
   Tambahkan atribut data-no-mobile pada elemen yang tidak boleh diubah.
   ====================================================================== */
(function () {
    'use strict';

    var BREAKPOINT = 820;
    var SKIP_CLASS = /(slider|carousel|swiper|marquee|ticker|track|scroll|tabs)/i;
    var touched = [];
    var running = false;

    function isSmall() {
        return window.innerWidth <= BREAKPOINT;
    }

    function wrapTables() {
        var tables = document.querySelectorAll('table');
        for (var i = 0; i < tables.length; i++) {
            var table = tables[i];
            var parent = table.parentElement;
            if (!parent || table.closest('.m-table-scroll, [data-no-mobile]')) continue;
            var ox = getComputedStyle(parent).overflowX;
            if (ox === 'auto' || ox === 'scroll') continue;
            var wrap = document.createElement('div');
            wrap.className = 'm-table-scroll';
            parent.insertBefore(wrap, table);
            wrap.appendChild(table);
        }
    }

    function shouldSkip(el) {
        if (el.closest('.m-table-scroll, table, svg, pre, [data-no-mobile]')) return true;
        var cls = typeof el.className === 'string' ? el.className : '';
        if (cls && SKIP_CLASS.test(cls)) return true;
        return false;
    }

    function remember(el, prop) {
        if (!el.__mOrig) {
            el.__mOrig = {};
            touched.push(el);
        }
        if (!(prop in el.__mOrig)) el.__mOrig[prop] = el.style[prop];
    }

    function fix() {
        if (running) return;
        running = true;
        try {
            if (!isSmall()) {
                restore();
                return;
            }
            wrapTables();

            var vw = document.documentElement.clientWidth;
            for (var pass = 0; pass < 3; pass++) {
                var changed = false;
                var all;
                try {
                    all = document.body.querySelectorAll('*:not(table *):not(svg *):not(select *):not(script):not(style)');
                } catch (e) {
                    all = document.body.getElementsByTagName('*');
                }
                for (var i = all.length - 1; i >= 0; i--) {
                    var el = all[i];
                    var cs = getComputedStyle(el);
                    if (cs.display === 'none' || cs.display === 'contents') continue;
                    if (cs.overflowX === 'auto' || cs.overflowX === 'scroll') continue;
                    if (cs.transform && cs.transform !== 'none') continue;

                    var isGrid = cs.display === 'grid' || cs.display === 'inline-grid';
                    var isRow = (cs.display === 'flex' || cs.display === 'inline-flex')
                        && cs.flexWrap === 'nowrap' && cs.flexDirection.indexOf('row') === 0;
                    var rect = el.getBoundingClientRect();
                    var tooWide = rect.width > vw + 1;
                    var overflowing = el.scrollWidth > el.clientWidth + 2;

                    if (!tooWide && !((isGrid || isRow) && overflowing)) continue;
                    if (shouldSkip(el)) continue;

                    if (isGrid) {
                        var stage = el.__mGrid || 0;
                        if (stage === 0) {
                            remember(el, 'gridTemplateColumns');
                            el.style.gridTemplateColumns = 'repeat(auto-fit, minmax(min(150px, 100%), 1fr))';
                            el.__mGrid = 1;
                            changed = true;
                        } else if (stage === 1) {
                            el.style.gridTemplateColumns = 'minmax(0, 1fr)';
                            el.__mGrid = 2;
                            changed = true;
                        }
                    } else if (isRow) {
                        if (!el.__mWrap) {
                            remember(el, 'flexWrap');
                            el.style.flexWrap = 'wrap';
                            el.__mWrap = true;
                            changed = true;
                        }
                    } else if (tooWide && cs.position !== 'fixed' && cs.position !== 'absolute' && !el.__mWidth) {
                        remember(el, 'maxWidth');
                        remember(el, 'minWidth');
                        remember(el, 'boxSizing');
                        el.style.boxSizing = 'border-box';
                        el.style.maxWidth = '100%';
                        el.style.minWidth = '0';
                        el.__mWidth = true;
                        changed = true;
                    }
                }
                if (!changed) break;
            }
        } finally {
            running = false;
        }
    }

    function restore() {
        for (var i = 0; i < touched.length; i++) {
            var el = touched[i];
            for (var prop in el.__mOrig) el.style[prop] = el.__mOrig[prop];
            el.__mOrig = null;
            el.__mGrid = 0;
            el.__mWrap = false;
            el.__mWidth = false;
        }
        touched = [];
    }

    var timer = null;
    function schedule(delay) {
        clearTimeout(timer);
        timer = setTimeout(fix, delay);
    }

    var lastWidth = window.innerWidth;
    function onResize() {
        if (window.innerWidth === lastWidth) return; // abaikan resize karena address bar HP
        lastWidth = window.innerWidth;
        restore();
        schedule(150);
    }

    function start() {
        fix();
        window.addEventListener('load', function () { schedule(50); });
        window.addEventListener('resize', onResize);
        window.addEventListener('orientationchange', function () { restore(); schedule(250); });

        if ('MutationObserver' in window) {
            new MutationObserver(function (records) {
                if (running || !isSmall()) return;
                for (var i = 0; i < records.length; i++) {
                    if (records[i].addedNodes.length) { schedule(250); return; }
                }
            }).observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
