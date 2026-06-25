/**
 * Lightweight markdown → HTML for chat (mirrors App\Helpers\Markdown).
 */
function renderMarkdown(text) {
  if (!text) return '';

  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  function normalizeInput(str) {
    return str
      .replace(/\r\n/g, '\n')
      .replace(/\r/g, '\n')
      .replace(/<br\s*\/?>/gi, '\n')
      .replace(/&lt;br\s*\/?&gt;/gi, '\n')
      .trim();
  }

  function inline(s) {
    s = s.replace(/<br\s*\/?>/gi, '\n').replace(/&lt;br\s*\/?&gt;/gi, '\n');
    s = escapeHtml(s);
    s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    s = s.replace(/__(.+?)__/g, '<strong>$1</strong>');
    s = s.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
    s = s.replace(/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/g, '<em>$1</em>');
    return s.replace(/\n/g, '<br>');
  }

  const lines = normalizeInput(text).split('\n');
  const html = [];
  let inUl = false;
  let inOl = false;
  let tableRows = [];

  function closeList() {
    if (inUl) { html.push('</ul>'); inUl = false; }
    if (inOl) { html.push('</ol>'); inOl = false; }
  }

  function flushTable() {
    if (!tableRows.length) return;
    let t = '<div class="chat-md-table-wrap"><table class="chat-md-table"><tbody>';
    tableRows.forEach(function (cells, i) {
      t += '<tr>';
      cells.forEach(function (cell) {
        const tag = i === 0 ? 'th' : 'td';
        t += '<' + tag + '>' + inline(cell) + '</' + tag + '>';
      });
      t += '</tr>';
    });
    t += '</tbody></table></div>';
    html.push(t);
    tableRows = [];
  }

  lines.forEach(function (line) {
    const trimmed = line.trim();
    if (!trimmed) {
      closeList();
      flushTable();
      html.push('<br class="chat-md-break" aria-hidden="true">');
      return;
    }

    if (trimmed.indexOf('|') !== -1 && /^(\|?.+\|.+|\|.+\|)$/.test(trimmed)) {
      if (/^[\|\s:\-]+$/.test(trimmed)) return;
      closeList();
      const cells = trimmed.replace(/^\||\|$/g, '').split('|').map(function (c) { return c.trim(); }).filter(Boolean);
      if (cells.length) tableRows.push(cells);
      return;
    }

    if (tableRows.length) flushTable();

    const hm = trimmed.match(/^(#{1,3})\s+(.+)$/);
    if (hm) {
      closeList();
      const tag = hm[1].length === 1 ? 'h3' : (hm[1].length === 2 ? 'h4' : 'h5');
      html.push('<' + tag + ' class="chat-md-heading">' + inline(hm[2]) + '</' + tag + '>');
      return;
    }

    const ulm = trimmed.match(/^[\*\-]\s+(.+)$/);
    if (ulm) {
      if (inOl) { html.push('</ol>'); inOl = false; }
      if (!inUl) { html.push('<ul class="chat-md-list">'); inUl = true; }
      html.push('<li>' + inline(ulm[1]) + '</li>');
      return;
    }

    const olm = trimmed.match(/^\d+\.\s+(.+)$/);
    if (olm) {
      if (inUl) { html.push('</ul>'); inUl = false; }
      if (!inOl) { html.push('<ol class="chat-md-list">'); inOl = true; }
      html.push('<li>' + inline(olm[1]) + '</li>');
      return;
    }

    closeList();
    html.push('<p class="chat-md-p">' + inline(trimmed) + '</p>');
  });

  closeList();
  flushTable();
  return html.join('\n');
}
