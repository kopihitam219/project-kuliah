{{-- Style halaman notifikasi (admin & customer), tema Fairway --}}
@once
    <style>
        .nf-wrap { max-width: 860px; margin: 0 auto; color: var(--d-text, #17261d); }

        .nf-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
        .nf-head h1 { font-family: var(--fw-serif, "Playfair Display", Georgia, serif); font-size: clamp(26px, 3.6vw, 36px); font-weight: 600; letter-spacing: -.4px; }
        .nf-head p { margin-top: 6px; color: var(--d-muted, #77837b); font-size: 14px; }

        .nf-btn { height: 38px; padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid transparent; border-radius: 99px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; white-space: nowrap; font-family: inherit; }
        .nf-btn-primary { background: var(--d-btn, #1f4d33); color: #fff; }
        .nf-btn-primary:hover { background: var(--d-btn-hover, #2a6444); }
        .nf-btn-outline { border-color: rgba(var(--d-green-rgb, 31, 77, 51), .35); background: var(--d-surface, #fff); color: var(--d-ink-green, #1f4d33); }
        .nf-btn-outline:hover { background: var(--d-tint-2, #f0f5ef); }
        .nf-btn-ghost { border-color: rgba(var(--d-ink-rgb, 23, 46, 33), .14); background: var(--d-surface, #fff); color: var(--d-text-2, #3c4a42); }
        .nf-btn-ghost:hover { border-color: rgba(201, 65, 58, .45); color: var(--d-red-ink, #c9413a); }

        .nf-tabs { display: flex; gap: 8px; margin-bottom: 14px; }
        .nf-tab { height: 36px; padding: 0 16px; display: inline-flex; align-items: center; border-radius: 999px; border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .1); background: var(--d-surface, #fff); color: var(--d-text-2, #3c4a42); font-size: 13px; font-weight: 500; text-decoration: none; }
        .nf-tab span { margin-left: 6px; opacity: .6; }
        .nf-tab.active { background: var(--d-btn, #1f4d33); border-color: var(--d-ink-green, #1f4d33); color: #fff; }

        .nf-flash { margin-bottom: 14px; padding: 12px 16px; border-radius: 14px; border: 1px solid rgba(var(--d-green-rgb, 31, 77, 51), .2); background: var(--d-tint, #e5eee6); color: var(--d-ink-green, #1f4d33); font-size: 14px; }

        .nf-list { display: grid; gap: 10px; }
        .nf-card { display: grid; grid-template-columns: 44px minmax(0, 1fr) auto; gap: 14px; align-items: start; padding: 16px; border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .09); border-radius: 18px; background: var(--d-surface, #fff); box-shadow: 0 1px 2px rgba(var(--d-shadow-rgb, 23, 46, 33), .04), 0 8px 24px rgba(var(--d-shadow-rgb, 23, 46, 33), .05); }
        .nf-card.unread { border-color: rgba(var(--d-green-rgb, 31, 77, 51), .3); background: var(--d-tint-2, #f6faf4); }

        .nf-icon { width: 44px; height: 44px; display: grid; place-items: center; border-radius: 50%; background: var(--d-tint, #e5eee6); color: var(--d-ink-green, #1f4d33); font-size: 15px; font-weight: 700; }
        .nf-icon.created     { background: var(--d-blue-tint, #e6eef9); color: var(--d-blue-ink, #3b6fb6); }
        .nf-icon.rescheduled { background: var(--d-orange-tint, #fdf1de); color: var(--d-orange-ink, #a2650c); }
        .nf-icon.cancelled,
        .nf-icon.rejected    { background: var(--d-red-tint, #fbe7e5); color: var(--d-red-ink, #c9413a); }
        .nf-icon.approved, .nf-icon.paid { background: var(--d-tint, #e5eee6); color: var(--d-ink-green, #1f4d33); }
        .nf-icon.paid        { font-size: 12px; }
        .nf-icon.broadcast   { background: var(--d-orange-tint, #fdf1de); }

        .nf-title { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; color: var(--d-text, #17261d); font-size: 14.5px; font-weight: 600; }
        .nf-badge { padding: 3px 9px; border-radius: 999px; background: var(--d-bg-2, #ecebe3); color: var(--d-muted, #66716a); font-size: 11px; font-weight: 600; }
        .nf-new { padding: 3px 9px; border-radius: 999px; background: #e9a23b; color: #fff; font-size: 11px; font-weight: 600; }
        .nf-message { margin-top: 6px; color: var(--d-text-2, #3c4a42); font-size: 13.5px; line-height: 1.6; }
        .nf-time { margin-top: 8px; color: var(--d-muted, #9aa59e); font-size: 12px; }

        .nf-actions { display: flex; gap: 6px; }
        .nf-actions form { margin: 0; }

        .nf-empty { padding: 50px 20px; text-align: center; border: 1px dashed rgba(var(--d-ink-rgb, 23, 46, 33), .18); border-radius: 18px; background: var(--d-surface, #fff); color: var(--d-muted, #77837b); font-size: 14px; }

        .nf-pager { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 14px; color: var(--d-muted, #77837b); font-size: 12.5px; }
        .nf-pager div { display: flex; gap: 6px; }

        /* Detail */
        .nf-detail { padding: 24px; border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .09); border-radius: 22px; background: var(--d-surface, #fff); box-shadow: 0 8px 24px rgba(var(--d-shadow-rgb, 23, 46, 33), .06); }
        .nf-detail-head { display: flex; gap: 14px; align-items: center; padding-bottom: 18px; border-bottom: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .08); }
        .nf-detail-head .nf-icon { width: 54px; height: 54px; font-size: 20px; }
        .nf-detail-head h1 { font-family: var(--fw-serif, "Playfair Display", Georgia, serif); font-size: 24px; font-weight: 600; }
        .nf-detail-message { padding: 18px 0; color: var(--d-text-2, #3c4a42); font-size: 15px; line-height: 1.75; border-bottom: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .08); white-space: pre-line; }

        .nf-section-title { margin: 18px 0 10px; color: var(--d-ink-green-2, #2a6444); font-size: 11.5px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; }
        .nf-rows { display: grid; gap: 8px; }
        .nf-row { display: grid; grid-template-columns: 170px minmax(0, 1fr); gap: 10px; padding: 10px 12px; border-radius: 12px; background: var(--d-surface-2, #f8f7f2); font-size: 13.5px; }
        .nf-row span:first-child { color: var(--d-muted, #77837b); }
        .nf-row span:last-child { color: var(--d-text, #17261d); font-weight: 600; }

        .nf-detail-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 22px; padding-top: 18px; border-top: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .08); }

        @media (max-width: 640px) {
            .nf-card { grid-template-columns: 40px minmax(0, 1fr); padding: 14px; }
            .nf-icon { width: 40px; height: 40px; }
            .nf-actions { grid-column: 1 / -1; }
            .nf-row { grid-template-columns: 1fr; gap: 2px; }
            .nf-detail { padding: 18px; }
        }
    </style>
@endonce
