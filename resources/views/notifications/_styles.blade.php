{{-- Style halaman notifikasi (admin & customer) --}}
@once
    <style>
        .nf-wrap { max-width: 860px; }

        .nf-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
        .nf-head h1 { font-size: 28px; font-weight: 900; letter-spacing: -.8px; }
        .nf-head p { margin-top: 6px; color: rgba(255, 255, 255, .6); font-size: 13px; }

        .nf-btn {
            height: 38px;
            padding: 0 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
        }

        .nf-btn-primary { background: #9cff38; color: #07120c; }
        .nf-btn-primary:hover { background: #b4ff66; }
        .nf-btn-outline { border-color: rgba(156, 255, 0, .5); background: transparent; color: #9cff38; }
        .nf-btn-outline:hover { background: rgba(156, 255, 0, .1); }
        .nf-btn-ghost { border-color: rgba(255, 255, 255, .14); background: transparent; color: rgba(255, 255, 255, .78); }
        .nf-btn-ghost:hover { border-color: rgba(255, 90, 90, .6); color: #ff8a96; }

        .nf-tabs { display: flex; gap: 8px; margin-bottom: 14px; }
        .nf-tab {
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid rgba(156, 255, 0, .18);
            color: rgba(255, 255, 255, .78);
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
        }
        .nf-tab span { margin-left: 6px; opacity: .6; }
        .nf-tab.active { background: #9cff38; border-color: #9cff38; color: #07120c; }

        .nf-flash { margin-bottom: 14px; padding: 11px 14px; border-radius: 9px; border: 1px solid rgba(156, 255, 0, .3); background: rgba(156, 255, 0, .07); color: #c9ff8a; font-size: 13px; }

        .nf-list { display: grid; gap: 10px; }

        .nf-card {
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr) auto;
            gap: 14px;
            align-items: start;
            padding: 16px;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 12px;
            background: rgba(1, 20, 13, .82);
        }

        .nf-card.unread { border-color: rgba(156, 255, 0, .35); background: rgba(156, 255, 0, .05); }

        .nf-icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            font-size: 15px;
            font-weight: 900;
        }

        .nf-icon.created     { background: rgba(92, 168, 255, .16); color: #8ec7ff; }
        .nf-icon.rescheduled { background: rgba(245, 174, 0, .16);  color: #ffc62d; }
        .nf-icon.cancelled,
        .nf-icon.rejected    { background: rgba(255, 77, 94, .16);  color: #ff8a96; }
        .nf-icon.approved    { background: rgba(67, 190, 77, .18);  color: #68ed62; }
        .nf-icon.paid        { background: rgba(184, 255, 0, .16);  color: #b8ff00; font-size: 12px; }

        .nf-title { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; color: #fff; font-size: 14px; font-weight: 800; }
        .nf-badge { padding: 3px 8px; border-radius: 999px; background: rgba(255, 255, 255, .08); color: rgba(255, 255, 255, .7); font-size: 10px; font-weight: 800; }
        .nf-new { padding: 3px 8px; border-radius: 999px; background: #9cff38; color: #07120c; font-size: 10px; font-weight: 900; }
        .nf-message { margin-top: 6px; color: rgba(255, 255, 255, .8); font-size: 13px; line-height: 1.6; }
        .nf-time { margin-top: 8px; color: rgba(255, 255, 255, .42); font-size: 11px; }

        .nf-actions { display: flex; gap: 6px; }
        .nf-actions form { margin: 0; }

        .nf-empty { padding: 50px 20px; text-align: center; border: 1px dashed rgba(156, 255, 0, .25); border-radius: 12px; color: rgba(255, 255, 255, .55); font-size: 13px; }

        .nf-pager { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 14px; color: rgba(255, 255, 255, .5); font-size: 12px; }
        .nf-pager div { display: flex; gap: 6px; }

        /* Detail */
        .nf-detail { padding: 24px; border: 1px solid rgba(156, 255, 0, .2); border-radius: 14px; background: rgba(1, 20, 13, .86); }
        .nf-detail-head { display: flex; gap: 14px; align-items: center; padding-bottom: 18px; border-bottom: 1px solid rgba(255, 255, 255, .08); }
        .nf-detail-head .nf-icon { width: 52px; height: 52px; font-size: 20px; }
        .nf-detail-head h1 { font-size: 22px; font-weight: 900; }
        .nf-detail-message { padding: 18px 0; color: rgba(255, 255, 255, .88); font-size: 15px; line-height: 1.7; border-bottom: 1px solid rgba(255, 255, 255, .08); }

        .nf-section-title { margin: 18px 0 10px; color: #9cff38; font-size: 11px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; }
        .nf-rows { display: grid; gap: 8px; }
        .nf-row { display: grid; grid-template-columns: 150px minmax(0, 1fr); gap: 10px; font-size: 13px; }
        .nf-row span:first-child { color: rgba(255, 255, 255, .5); }
        .nf-row span:last-child { color: #fff; font-weight: 700; }

        .nf-detail-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 22px; padding-top: 18px; border-top: 1px solid rgba(255, 255, 255, .08); }

        @media (max-width: 640px) {
            .nf-card { grid-template-columns: 36px minmax(0, 1fr); }
            .nf-actions { grid-column: 1 / -1; }
            .nf-row { grid-template-columns: 1fr; gap: 2px; }
        }
    </style>
@endonce
