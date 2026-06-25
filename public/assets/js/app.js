/**
 * Agri-Advisory — local UI scripts (no CDN).
 * Animations, toasts, modals, mobile nav.
 */
(function () {
  'use strict';

  /* ── Toast notifications ─────────────────────────────────────────── */
  function ensureToastRoot() {
    let root = document.getElementById('toast-root');
    if (!root) {
      root = document.createElement('div');
      root.id = 'toast-root';
      root.className = 'toast-root';
      root.setAttribute('aria-live', 'polite');
      document.body.appendChild(root);
    }
    return root;
  }

  window.showToast = function (message, type) {
    type = type || 'error';
    const root = ensureToastRoot();
    const el = document.createElement('div');
    el.className = 'toast toast-' + type + ' toast-enter';
    el.setAttribute('role', 'alert');
    el.textContent = message;
    root.appendChild(el);
    requestAnimationFrame(function () {
      el.classList.add('toast-visible');
    });
    setTimeout(function () {
      el.classList.remove('toast-enter');
      el.classList.add('toast-exit');
      setTimeout(function () { el.remove(); }, 320);
    }, 5000);
  };

  /* ── Scroll reveal ───────────────────────────────────────────────── */
  function initScrollReveal() {
    const els = document.querySelectorAll('[data-reveal]');
    if (!els.length || !('IntersectionObserver' in window)) return;

    const io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-revealed');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    els.forEach(function (el) { io.observe(el); });
  }

  /* ── Modal helpers ───────────────────────────────────────────────── */
  function mountModalsToBody() {
    document.querySelectorAll('[id$="Modal"]').forEach(function (m) {
      if (m.parentElement !== document.body && (m.classList.contains('fixed') || m.classList.contains('modal-backdrop') || m.classList.contains('modal-overlay'))) {
        document.body.appendChild(m);
      }
    });
  }

  window.openModal = function (id) {
    const m = document.getElementById(id);
    if (m) {
      if (m.parentElement !== document.body) {
        document.body.appendChild(m);
      }
      m.classList.remove('hidden');
      m.classList.add('modal-open');
      document.body.classList.add('modal-active');
    }
  };

  window.closeModal = function (id) {
    const m = document.getElementById(id);
    if (m) {
      m.classList.remove('modal-open');
      m.classList.add('hidden');
      if (!document.querySelector('.modal-open')) {
        document.body.classList.remove('modal-active');
      }
    }
  };

  document.addEventListener('click', function (e) {
    const closeBtn = e.target.closest('[data-close-modal]');
    if (closeBtn) {
      const modal = closeBtn.closest('[id$="Modal"], .modal-backdrop');
      if (modal && modal.id) closeModal(modal.id);
    }
    if (e.target.classList.contains('modal-backdrop') || e.target.classList.contains('modal-overlay')) {
      if (e.target.id) closeModal(e.target.id);
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-open').forEach(function (m) {
        closeModal(m.id);
      });
    }
  });

  /* ── Mobile sidebar ──────────────────────────────────────────────── */
  function initMobileNav() {
    const btn = document.querySelector('[data-mobile-nav-toggle]');
    const sidebar = document.querySelector('[data-mobile-sidebar]');
    if (!btn || !sidebar) return;

    btn.addEventListener('click', function () {
      sidebar.classList.toggle('mobile-sidebar-open');
      document.body.classList.toggle('mobile-nav-open');
    });
  }

  /* ── Page enter animation ────────────────────────────────────────── */
  function initPageEnter() {
    document.body.classList.add('page-ready');
  }

  /* ── Language switch (fallback if inline missing) ────────────────── */
  window.switchLang = window.switchLang || function (locale) {
    fetch('/set-lang', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'lang=' + encodeURIComponent(locale),
    }).then(function () { window.location.reload(); });
  };

  /* ── Stagger children animation ──────────────────────────────────── */
  function initStagger() {
    document.querySelectorAll('[data-stagger]').forEach(function (parent) {
      parent.querySelectorAll('[data-stagger-item]').forEach(function (child, i) {
        child.style.animationDelay = (i * 0.07) + 's';
        child.classList.add('stagger-item');
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initScrollReveal();
    initMobileNav();
    initPageEnter();
    initStagger();
    mountModalsToBody();
  });
})();
