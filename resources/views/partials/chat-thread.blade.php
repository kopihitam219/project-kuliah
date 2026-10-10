{{--
    Kotak percakapan chat (dipakai halaman member & admin).
    Variabel: $messages (array), $pollUrl, $sendUrl, $viewer ('member'|'admin'),
              $peerName (nama lawan bicara), $emptyText
--}}
@once
<style>
    .ct { display: flex; flex-direction: column; min-height: 0; height: 100%; border: 1px solid rgba(156, 255, 0, .14); border-radius: 18px; background: rgba(2, 18, 12, .82); overflow: hidden; font-family: Arial, Helvetica, sans-serif; color: #f2f7f3; }
    .ct-head { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-bottom: 1px solid rgba(255, 255, 255, .07); background: rgba(4, 22, 15, .9); }
    .ct-avatar { width: 40px; height: 40px; flex: 0 0 40px; display: grid; place-items: center; border-radius: 14px; background: #9cff38; color: #062010; font-weight: 900; font-size: 17px; }
    .ct-head strong { display: block; font-size: 15px; }
    .ct-head small { display: flex; align-items: center; gap: 6px; color: rgba(242, 247, 243, .55); font-size: 11.5px; }
    .ct-head small i { width: 7px; height: 7px; border-radius: 50%; background: #65ec65; }
    .ct-body { flex: 1; min-height: 0; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 8px; scroll-behavior: smooth; overscroll-behavior: contain; }
    .ct-body::-webkit-scrollbar { width: 6px; } .ct-body::-webkit-scrollbar-thumb { background: rgba(156, 255, 0, .2); border-radius: 9px; }
    .ct-date { align-self: center; margin: 8px 0 4px; padding: 4px 10px; border-radius: 99px; background: rgba(255, 255, 255, .06); color: rgba(242, 247, 243, .55); font-size: 11px; font-weight: 700; }
    .ct-msg { max-width: min(78%, 520px); padding: 9px 12px 6px; border-radius: 16px; font-size: 14px; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; }
    .ct-msg.them { align-self: flex-start; background: #10261a; border: 1px solid rgba(255, 255, 255, .06); border-bottom-left-radius: 5px; }
    .ct-msg.mine { align-self: flex-end; background: #9cff38; color: #062010; border-bottom-right-radius: 5px; }
    .ct-meta { display: block; margin-top: 3px; font-size: 10.5px; text-align: right; opacity: .6; }
    .ct-bc { align-self: stretch; max-width: none; padding: 12px 14px; border-radius: 16px; background: linear-gradient(120deg, rgba(156, 255, 56, .14), rgba(156, 255, 56, .04)); border: 1px solid rgba(156, 255, 56, .35); color: #f2f7f3; }
    .ct-bc b { display: flex; align-items: center; gap: 7px; margin-bottom: 4px; color: #9cff38; font-size: 11px; letter-spacing: 1.4px; text-transform: uppercase; }
    .ct-bc strong { display: block; margin-bottom: 4px; font-size: 15px; }
    .ct-empty { margin: auto; max-width: 300px; text-align: center; color: rgba(242, 247, 243, .55); font-size: 13.5px; line-height: 1.6; }
    .ct-empty span { display: block; margin-bottom: 8px; font-size: 34px; }
    .ct-form { display: flex; align-items: flex-end; gap: 8px; padding: 10px; border-top: 1px solid rgba(255, 255, 255, .07); background: rgba(4, 22, 15, .95); }
    .ct-form textarea { flex: 1; min-height: 46px; max-height: 140px; resize: none; padding: 12px 14px; border: 1px solid rgba(255, 255, 255, .1); border-radius: 14px; background: rgba(255, 255, 255, .05); color: #f2f7f3; font: 16px/1.4 Arial, Helvetica, sans-serif; outline: none; }
    .ct-form textarea:focus { border-color: rgba(156, 255, 0, .55); }
    .ct-form button { width: 46px; height: 46px; flex: 0 0 46px; display: grid; place-items: center; border: 0; border-radius: 14px; background: #9cff38; color: #062010; cursor: pointer; }
    .ct-form button:disabled { opacity: .5; cursor: wait; }
    .ct-form button svg { width: 20px; height: 20px; }
    .ct-error { padding: 6px 14px 0; color: #ff8a8a; font-size: 12px; }
</style>
@endonce

<div class="ct" id="chatThread"
     data-poll="{{ $pollUrl }}" data-send="{{ $sendUrl }}" data-viewer="{{ $viewer }}">
    <div class="ct-head">
        @if (! empty($peerPhoto))
            <div class="ct-avatar" style="background:url('{{ $peerPhoto }}') center top / cover;color:transparent">{{ mb_strtoupper(mb_substr($peerName, 0, 1)) }}</div>
        @else
            <div class="ct-avatar">{{ mb_strtoupper(mb_substr($peerName, 0, 1)) }}</div>
        @endif
        <div>
            <strong>{{ $peerName }}</strong>
            <small><i></i>{{ $peerSub ?? ($viewer === 'member' ? 'Coach biasanya membalas di jam operasional' : 'Member') }}</small>
        </div>
    </div>

    <div class="ct-body" id="chatBody" aria-live="polite">
        <div class="ct-empty" id="chatEmpty" @if (count($messages)) hidden @endif>
            <span>💬</span>{{ $emptyText }}
        </div>
    </div>

    <p class="ct-error" id="chatError" hidden></p>

    <form class="ct-form" id="chatForm">
        <label for="chatInput" class="sr-only" style="position:absolute;left:-9999px">Tulis pesan</label>
        <textarea id="chatInput" rows="1" maxlength="2000" placeholder="Tulis pesan..." required></textarea>
        <button type="submit" id="chatSend" aria-label="Kirim">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
        </button>
    </form>
</div>

<script>
(function () {
    'use strict';
    var root = document.getElementById('chatThread');
    if (!root) return;
    var body = document.getElementById('chatBody');
    var empty = document.getElementById('chatEmpty');
    var form = document.getElementById('chatForm');
    var input = document.getElementById('chatInput');
    var sendBtn = document.getElementById('chatSend');
    var errorEl = document.getElementById('chatError');
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '{{ csrf_token() }}';
    var lastId = 0, lastDate = null, polling = false;
    var initial = @json($messages);

    function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; }

    function add(m) {
        if (m.id <= lastId) return;
        lastId = m.id;
        empty.hidden = true;
        if (m.date !== lastDate) { body.appendChild(el('div', 'ct-date', m.date)); lastDate = m.date; }
        var node;
        if (m.broadcast) {
            node = el('div', 'ct-msg ct-bc');
            node.appendChild(el('b', null, '📢 Pengumuman'));
            if (m.title) node.appendChild(el('strong', null, m.title));
            node.appendChild(document.createTextNode(m.body));
        } else {
            node = el('div', 'ct-msg ' + (m.mine ? 'mine' : 'them'));
            node.appendChild(document.createTextNode(m.body));
        }
        node.appendChild(el('span', 'ct-meta', m.time + (m.mine ? (m.read ? ' · dibaca' : ' · terkirim') : '')));
        body.appendChild(node);
    }

    function toBottom() { body.scrollTop = body.scrollHeight; }

    initial.forEach(add);
    toBottom();

    function poll() {
        if (polling) return;
        polling = true;
        fetch(root.dataset.poll + '?after=' + lastId, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d || !d.messages || !d.messages.length) return;
                var near = body.scrollHeight - body.scrollTop - body.clientHeight < 120;
                d.messages.forEach(add);
                if (near) toBottom();
            })
            .catch(function () {})
            .finally(function () { polling = false; });
    }

    var timer = setInterval(poll, 5000);
    document.addEventListener('visibilitychange', function () {
        clearInterval(timer);
        timer = setInterval(poll, document.hidden ? 20000 : 5000);
        if (!document.hidden) poll();
    });

    function grow() { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 140) + 'px'; }
    input.addEventListener('input', grow);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey && window.matchMedia('(min-width: 821px)').matches) { e.preventDefault(); form.requestSubmit(); }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = input.value.trim();
        if (!text) return;
        sendBtn.disabled = true; errorEl.hidden = true;
        fetch(root.dataset.send, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ body: text })
        })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                if (!res.ok) throw new Error((res.d && res.d.message) || 'Pesan gagal dikirim.');
                input.value = ''; grow();
                add(res.d.message); toBottom();
            })
            .catch(function (err) { errorEl.textContent = err.message || 'Pesan gagal dikirim. Coba lagi.'; errorEl.hidden = false; })
            .finally(function () { sendBtn.disabled = false; input.focus(); });
    });
})();
</script>
