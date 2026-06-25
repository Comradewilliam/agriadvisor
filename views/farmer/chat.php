<?php
use App\Helpers\Markdown;

$farmerId = (int)$_SESSION['farmer_id'];
$db = \App\Core\Database::getInstance()->getConnection();
$activeThreadId = $activeThreadId ?? null;
$threads = $threads ?? [];

$displayMessages = [];
if ($activeThreadId) {
    $stmt = $db->prepare("
        SELECT id, direction, channel, content, sent_at, 'ai' AS source
        FROM ai_messages
        WHERE farmer_id = :fid AND thread_id = :tid
        ORDER BY sent_at ASC
    ");
    $stmt->execute([':fid' => $farmerId, ':tid' => $activeThreadId]);
    $displayMessages = $stmt->fetchAll();
}

$isNewConversation = !$activeThreadId;

$suggestedQuestions = [
    'Jinsi ya kupanda mahindi?',
    'Mbolea gani ni bora msimu huu?',
    'Magonjwa ya mimea',
    'Utabiri wa mvua',
    'Karanga na ukungu',
];
?>


<div class="farmer-chat-shell flex flex-1 min-h-0 overflow-hidden w-full bg-surface">

  <!-- Conversation History Sidebar (collapsible) -->
  <aside id="chatHistorySidebar" class="hidden md:flex flex-col min-h-0 border-r border-outline-variant bg-surface-container-low shrink-0 transition-all duration-300 chat-history-sidebar chat-history-open">
    <div class="p-4 border-b border-outline-variant flex items-center justify-between gap-2 chat-history-header">
      <h3 class="font-bold text-on-surface truncate chat-history-title">Maongezi Yaliyopita</h3>
      <div class="flex items-center gap-1 shrink-0 chat-history-actions">
        <button type="button" id="toggleChatHistory" class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center hover:bg-surface-container-high transition-colors" title="Ficha/Onyesha" aria-label="Ficha au onyesha historia">
          <span class="material-symbols-outlined text-base" id="toggleChatHistoryIcon">chevron_left</span>
        </button>
        <a href="/farmer/chat/new" class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center hover:bg-primary/20 transition-colors" title="Anza mazungumzo mapya" aria-label="Anza mazungumzo mapya">
          <span class="material-symbols-outlined text-base">add_comment</span>
        </a>
      </div>
    </div>
    <div class="flex-1 overflow-y-auto chat-history-list">
      <?php if (empty($threads)): ?>
        <div class="p-6 text-center text-outline text-sm">
          <span class="material-symbols-outlined text-3xl mb-2 block">chat_bubble_outline</span>
          Hakuna mazungumzo bado.
        </div>
      <?php else:
          foreach ($threads as $t):
              $isActive = ($activeThreadId === $t['id']);
              $activeCls = $isActive ? 'bg-secondary-fixed border-l-4 border-secondary' : 'hover:bg-surface-container';
      ?>
        <a href="/farmer/chat?thread=<?php echo (int)$t['id']; ?>"
           class="<?php echo $activeCls; ?> block p-4 transition-colors no-underline">
          <p class="font-bold text-on-surface text-sm truncate"><?php echo htmlspecialchars($t['preview']); ?></p>
          <p class="text-xs text-on-surface-variant mt-0.5"><?php echo htmlspecialchars($t['label']); ?></p>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </aside>

  <!-- Main Chat Area -->
  <div class="flex-1 flex flex-col min-w-0 min-h-0">

    <!-- Chat Top Bar -->
    <div class="flex items-center justify-between px-4 md:px-6 py-3 bg-white border-b border-outline-variant shrink-0">
      <div class="flex items-center gap-3 min-w-0">
        <button type="button" id="toggleChatHistoryMobile" class="md:hidden w-9 h-9 rounded-xl bg-surface-container flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-base">history</span>
        </button>
        <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse shrink-0"></div>
        <span class="text-sm font-medium text-on-surface-variant truncate">BwanaShamba yupo hewani</span>
      </div>
      <div class="flex items-center gap-2 md:gap-3 shrink-0">
        <button type="button" class="hidden sm:flex items-center gap-2 bg-tertiary text-white px-3 md:px-4 py-2 rounded-xl text-sm font-bold hover:opacity-90 transition-opacity">
          <span class="material-symbols-outlined text-base">support_agent</span> <span class="hidden md:inline">Ongea na Afisa</span>
        </button>
        <button type="button" class="w-9 h-9 rounded-xl bg-surface-container flex items-center justify-center hover:bg-surface-container-high transition-colors" title="Tafsiri">
          <span class="material-symbols-outlined text-base">translate</span>
        </button>
      </div>
    </div>

    <!-- Messages Area -->
    <div class="flex-1 min-h-0 overflow-y-auto p-4 md:p-6 space-y-4" id="chatMessages">

      <?php if ($isNewConversation && empty($displayMessages)): ?>
        <!-- Welcome State — new conversation -->
        <div class="flex flex-col items-center justify-center flex-1 min-h-[12rem] text-center py-12" id="chatWelcome">
          <div class="w-20 h-20 rounded-3xl bg-primary flex items-center justify-center mb-5 shadow-lg">
            <span class="material-symbols-outlined text-white text-4xl">eco</span>
          </div>
          <h3 class="text-2xl font-extrabold text-on-surface mb-2">Mazungumzo Mapya</h3>
          <p class="text-on-surface-variant max-w-md leading-relaxed">
            Unaanzisha mazungumzo mapya. Andika swali lako hapa chini — au chagua mazungumzo ya zamani upande wa kushoto ukiendelee ulipoishia.
          </p>
        </div>
      <?php elseif (empty($displayMessages)): ?>
        <div class="text-center py-12 text-on-surface-variant">
          <span class="material-symbols-outlined text-4xl mb-2 block text-outline">chat_bubble_outline</span>
          <p class="text-sm">Hakuna ujumbe katika mazungumzo haya bado.</p>
        </div>
      <?php else: ?>
        <?php foreach ($displayMessages as $msg): ?>
          <?php if ($msg['direction'] === 'in'): ?>
            <!-- Farmer message (right) -->
            <div class="flex justify-end">
              <div class="bg-primary text-white rounded-2xl rounded-tr-sm px-4 py-3 max-w-md">
                <p class="text-sm leading-relaxed"><?php echo htmlspecialchars($msg['content']); ?></p>
                <p class="text-xs mt-1 text-white/60 text-right"><?php echo date('H:i', strtotime($msg['sent_at'])); ?></p>
              </div>
            </div>
          <?php else: ?>
            <!-- AI/Officer message (left) -->
            <div class="flex gap-3">
              <div class="w-9 h-9 rounded-2xl flex items-center justify-center shrink-0 <?php echo $msg['source'] === 'officer' ? 'bg-amber-500' : ($msg['source'] === 'officer_reply' ? 'bg-blue-500' : 'bg-primary'); ?>">
                <span class="material-symbols-outlined text-white text-base">
                  <?php echo $msg['source'] === 'officer' ? 'campaign' : ($msg['source'] === 'officer_reply' ? 'support_agent' : 'eco'); ?>
                </span>
              </div>
              <div class="border rounded-2xl rounded-tl-sm px-4 py-3 max-w-lg shadow-sm <?php echo $msg['source'] === 'officer' ? 'bg-amber-50 border-amber-200' : ($msg['source'] === 'officer_reply' ? 'bg-blue-50 border-blue-200' : 'bg-white border-outline-variant'); ?>">
                <div class="text-sm leading-relaxed text-on-surface chat-md"><?php echo $msg['source'] === 'ai' ? Markdown::toHtml($msg['content']) : nl2br(htmlspecialchars($msg['content'])); ?></div>
                <p class="text-xs mt-1 text-on-surface-variant">
                  <?php echo date('H:i', strtotime($msg['sent_at'])); ?>
                  <?php if ($msg['source'] === 'ai'): ?>
                    &bull; <span class="text-primary font-medium">BwanaShamba AI</span>
                  <?php elseif ($msg['source'] === 'officer'): ?>
                    &bull; <span class="text-amber-600 font-medium">Broadcast</span>
                  <?php elseif ($msg['source'] === 'officer_reply'): ?>
                    &bull; <span class="text-blue-600 font-medium">Majibu ya Afisa</span>
                  <?php endif; ?>
                </p>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Suggested Questions Chips -->
    <div class="px-6 py-2 flex gap-2 overflow-x-auto scrollbar-hide shrink-0 bg-white border-t border-outline-variant/50">
      <?php foreach ($suggestedQuestions as $q): ?>
        <button type="button" data-suggest="<?php echo htmlspecialchars($q, ENT_QUOTES); ?>"
                class="whitespace-nowrap text-sm bg-secondary-fixed text-secondary font-medium px-4 py-2 rounded-full border border-secondary-fixed-dim hover:bg-secondary hover:text-white transition-colors shrink-0">
          <?php echo htmlspecialchars($q); ?>
        </button>
      <?php endforeach; ?>
    </div>

    <!-- Input Area -->
    <div class="px-6 py-4 bg-white border-t border-outline-variant shrink-0">
      <div class="flex items-center gap-3 bg-surface-container border border-outline rounded-2xl px-4 py-3 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 transition-all">
        <button class="text-on-surface-variant hover:text-primary transition-colors" title="Kwasasa haifanyikazi ipo kwenye maboresho">
          <span class="material-symbols-outlined">attach_file</span>
        </button>
        <input id="chatInput" type="text" placeholder="Andika ujumbe wako hapa..."
               class="flex-1 bg-transparent outline-none text-on-surface placeholder-on-surface-variant/60 text-sm">
        <button type="button" id="sendBtn" class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white hover:bg-primary-container transition-colors shrink-0">
          <span class="material-symbols-outlined text-base">send</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/markdown-lite.js"></script>
<script>
(function () {
  'use strict';

  const CHAT_STATUS_INTERVAL_MS = 10000;

  const CHAT_STATUS_MSGS = [
    'Anafikiria…',
    'Anaandika…',
    'Namalizia kukusanya taarifa sahihi kwa ajili yako…',
    'Sitaki kubahatisha, nipe sekunde chache nichunguze kwa wataalamu wa kilimo…',
    'Samahani, imechukua muda mrefu kuchakata, bado nafanyia kazi…',
  ];

  let chatStatusTimer = null;
  let chatStatusIdx = 0;
  let chatEl = null;

  function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
  }

  function showTypingIndicator() {
    removeTypingIndicator();
    if (!chatEl) return;
    chatStatusIdx = 0;
    const wrap = document.createElement('div');
    wrap.id = 'typingIndicator';
    wrap.className = 'flex gap-3';
    wrap.innerHTML = '<div class="w-9 h-9 rounded-2xl bg-primary flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-white text-base">eco</span></div><div class="bg-white border border-outline-variant rounded-2xl rounded-tl-sm px-4 py-3 max-w-lg shadow-sm"><p class="text-sm text-on-surface-variant flex items-center gap-2"><span id="chatStatusText">' + CHAT_STATUS_MSGS[0] + '</span><span class="typing-dots" aria-hidden="true"><span></span><span></span><span></span></span></p></div>';
    chatEl.appendChild(wrap);
    chatEl.scrollTop = chatEl.scrollHeight;
    chatStatusTimer = setInterval(function () {
      chatStatusIdx = (chatStatusIdx + 1) % CHAT_STATUS_MSGS.length;
      const el = document.getElementById('chatStatusText');
      if (el) el.textContent = CHAT_STATUS_MSGS[chatStatusIdx];
    }, CHAT_STATUS_INTERVAL_MS);
  }

  function removeTypingIndicator() {
    if (chatStatusTimer) { clearInterval(chatStatusTimer); chatStatusTimer = null; }
    document.getElementById('typingIndicator')?.remove();
  }

  function upsertSidebarThread(threadId, preview) {
    const list = document.querySelector('.chat-history-list');
    if (!list || !threadId) return;

    const empty = list.querySelector('.text-center');
    if (empty) empty.remove();

    const href = '/farmer/chat?thread=' + threadId;
    let link = list.querySelector('a[href="' + href + '"]');
    const label = 'Leo · ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const text = preview || 'Mazungumzo';

    list.querySelectorAll('a').forEach(function (a) {
      a.classList.remove('bg-secondary-fixed', 'border-l-4', 'border-secondary');
      a.classList.add('hover:bg-surface-container');
    });

    if (!link) {
      link = document.createElement('a');
      link.href = href;
      link.className = 'bg-secondary-fixed border-l-4 border-secondary block p-4 transition-colors no-underline';
      link.innerHTML = '<p class="font-bold text-on-surface text-sm truncate"></p><p class="text-xs text-on-surface-variant mt-0.5"></p>';
      list.insertBefore(link, list.firstChild);
    } else {
      link.className = 'bg-secondary-fixed border-l-4 border-secondary block p-4 transition-colors no-underline';
      list.insertBefore(link, list.firstChild);
    }

    link.querySelector('p.font-bold').textContent = text;
    link.querySelector('p.text-xs').textContent = label;
  }

  function initChatSidebar() {
    const sidebar = document.getElementById('chatHistorySidebar');
    const toggleBtn = document.getElementById('toggleChatHistory');
    const toggleIcon = document.getElementById('toggleChatHistoryIcon');
    const mobileBtn = document.getElementById('toggleChatHistoryMobile');
    if (!sidebar || !toggleBtn) return;

    const storageKey = 'agriad_chat_history_open';

    function setOpen(open) {
      sidebar.classList.toggle('chat-history-open', open);
      sidebar.classList.toggle('chat-history-collapsed', !open);
      if (toggleIcon) toggleIcon.textContent = open ? 'chevron_left' : 'chevron_right';
      try { localStorage.setItem(storageKey, open ? '1' : '0'); } catch (e) {}
    }

    if (localStorage.getItem(storageKey) === '0') setOpen(false);

    toggleBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      setOpen(!sidebar.classList.contains('chat-history-open'));
    });

    if (mobileBtn) {
      mobileBtn.addEventListener('click', function (e) {
        e.preventDefault();
        sidebar.classList.add('chat-history-mobile-visible');
        sidebar.classList.remove('hidden');
        sidebar.style.display = 'flex';
      });
    }
  }

  async function sendMessage() {
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');
    if (!input || !chatEl) return;

    const text = input.value.trim();
    if (!text) return;

    document.getElementById('chatWelcome')?.remove();

    input.value = '';
    input.disabled = true;
    if (sendBtn) sendBtn.disabled = true;

    const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    chatEl.insertAdjacentHTML('beforeend',
      '<div class="flex justify-end"><div class="bg-primary text-white rounded-2xl rounded-tr-sm px-4 py-3 max-w-md"><p class="text-sm leading-relaxed">' + escapeHtml(text) + '</p><p class="text-xs mt-1 text-white/60 text-right">' + time + '</p></div></div>'
    );
    chatEl.scrollTop = chatEl.scrollHeight;
    showTypingIndicator();

    try {
      const fd = new FormData();
      fd.append('text', text);
      const controller = new AbortController();
      const timeoutId = setTimeout(function () { controller.abort(); }, 120000);
      const res = await fetch('/farmer/chat/send', { method: 'POST', body: fd, signal: controller.signal });
      clearTimeout(timeoutId);
      let data;
      try {
        data = await res.json();
      } catch (parseErr) {
        throw new Error('Majibu hayakupokelewa vizuri kutoka kwa seva. Jaribu tena.');
      }
      removeTypingIndicator();

      if (data.ok && data.reply) {
        if (data.thread_id) {
          history.replaceState(null, '', '/farmer/chat?thread=' + data.thread_id);
          upsertSidebarThread(data.thread_id, data.thread_preview || text);
        }
        chatEl.insertAdjacentHTML('beforeend',
          '<div class="flex gap-3"><div class="w-9 h-9 rounded-2xl bg-primary flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-white text-base">eco</span></div><div class="bg-white border border-outline-variant rounded-2xl rounded-tl-sm px-4 py-3 max-w-lg shadow-sm"><div class="text-sm leading-relaxed text-on-surface chat-md">' + renderMarkdown(data.reply) + '</div><p class="text-xs mt-1 text-on-surface-variant">' + time + ' &bull; <span class="text-primary font-medium">BwanaShamba AI</span></p></div></div>'
        );
        chatEl.scrollTop = chatEl.scrollHeight;
      } else {
        chatEl.insertAdjacentHTML('beforeend',
          '<div class="flex gap-3"><div class="w-9 h-9 rounded-2xl bg-primary flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-white text-base">eco</span></div><div class="bg-white border border-error/30 rounded-2xl rounded-tl-sm px-4 py-3 max-w-lg shadow-sm"><p class="text-sm text-error">' + escapeHtml(data.error || 'Hitilafu imetokea. Jaribu tena.') + '</p></div></div>'
        );
      }
    } catch (err) {
      removeTypingIndicator();
      const msg = err.name === 'AbortError'
        ? 'Muda umekwisha — AI bado inafanya kazi. Onyesha ukurasa upya au jaribu tena baada ya dakika moja.'
        : (err.message || 'Hitilafu ya mtandao. Jaribu tena.');
      chatEl.insertAdjacentHTML('beforeend',
        '<div class="flex gap-3"><div class="w-9 h-9 rounded-2xl bg-primary flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-white text-base">eco</span></div><div class="bg-white border border-error/30 rounded-2xl rounded-tl-sm px-4 py-3 max-w-lg shadow-sm"><p class="text-sm text-error">' + escapeHtml(msg) + '</p></div></div>'
      );
      chatEl.scrollTop = chatEl.scrollHeight;
      console.error(err);
    } finally {
      input.disabled = false;
      if (sendBtn) sendBtn.disabled = false;
      input.focus();
    }
  }

  window.sendMessage = sendMessage;

  document.addEventListener('DOMContentLoaded', function () {
    chatEl = document.getElementById('chatMessages');
    if (chatEl) chatEl.scrollTop = chatEl.scrollHeight;

    initChatSidebar();

    document.getElementById('sendBtn')?.addEventListener('click', sendMessage);
    document.getElementById('chatInput')?.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); sendMessage(); }
    });

    document.querySelectorAll('[data-suggest]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const input = document.getElementById('chatInput');
        if (input) input.value = btn.getAttribute('data-suggest') || '';
      });
    });

    const params = new URLSearchParams(window.location.search);
    const q = params.get('q');
    if (q) {
      const input = document.getElementById('chatInput');
      if (input) input.value = decodeURIComponent(q.replace(/\+/g, ' '));
    }
  });
})();
</script>
