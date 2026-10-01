(function () {
  function iconFor(type) {
    if (type === 'success') return '✅';
    if (type === 'error') return '❌';
    if (type === 'warning') return '⚠️';
    return 'ℹ️';
  }

  function buildOverlay(box) {
    var root = document.getElementById('app-popup-root');
    if (!root) return null;

    var overlay = document.createElement('div');
    overlay.className = 'app-popup-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.appendChild(box);
    root.appendChild(overlay);
    return overlay;
  }

  function showAppPopup(type, message, onClose) {
    var box = document.createElement('div');
    box.className = 'app-popup app-popup--' + (type || 'info');

    var icon = document.createElement('div');
    icon.className = 'app-popup-icon';
    icon.textContent = iconFor(type);

    var text = document.createElement('p');
    text.className = 'app-popup-message';
    text.textContent = message;

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn-primary app-popup-btn';
    btn.textContent = 'OK';

    var overlay = null;
    function close() {
      if (overlay) overlay.remove();
      if (typeof onClose === 'function') onClose();
    }

    btn.addEventListener('click', close);
    box.appendChild(icon);
    box.appendChild(text);
    box.appendChild(btn);
    overlay = buildOverlay(box);
    if (overlay) {
      overlay.addEventListener('click', function (e) {
        if (e.target === overlay) close();
      });
      btn.focus();
    }
  }

  function showAppConfirm(message, onConfirm, onCancel) {
    var box = document.createElement('div');
    box.className = 'app-popup app-popup--confirm';

    var icon = document.createElement('div');
    icon.className = 'app-popup-icon';
    icon.textContent = '❓';

    var text = document.createElement('p');
    text.className = 'app-popup-message';
    text.textContent = message;

    var actions = document.createElement('div');
    actions.className = 'app-popup-actions';

    var yesBtn = document.createElement('button');
    yesBtn.type = 'button';
    yesBtn.className = 'btn-primary app-popup-btn';
    yesBtn.textContent = 'Yes';

    var noBtn = document.createElement('button');
    noBtn.type = 'button';
    noBtn.className = 'btn-ghost app-popup-btn';
    noBtn.textContent = 'No';

    var overlay = null;
    function close() {
      if (overlay) overlay.remove();
    }

    yesBtn.addEventListener('click', function () {
      close();
      if (typeof onConfirm === 'function') onConfirm();
    });
    noBtn.addEventListener('click', function () {
      close();
      if (typeof onCancel === 'function') onCancel();
    });

    actions.appendChild(yesBtn);
    actions.appendChild(noBtn);
    box.appendChild(icon);
    box.appendChild(text);
    box.appendChild(actions);
    overlay = buildOverlay(box);
    if (overlay) {
      overlay.addEventListener('click', function (e) {
        if (e.target === overlay) {
          close();
          if (typeof onCancel === 'function') onCancel();
        }
      });
      yesBtn.focus();
    }
  }

  window.showAppPopup = showAppPopup;
  window.showAppConfirm = showAppConfirm;

  function bindConfirmForms() {
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
      if (form.dataset.confirmBound === '1') return;
      form.dataset.confirmBound = '1';
      form.addEventListener('submit', function (e) {
        if (form.dataset.confirmed === '1') {
          form.dataset.confirmed = '';
          return;
        }
        e.preventDefault();
        var msg = form.getAttribute('data-confirm') || 'Are you sure?';
        showAppConfirm(msg, function () {
          form.dataset.confirmed = '1';
          if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
          } else {
            form.submit();
          }
        });
      });
    });
  }

  function runQueue(queue, index) {
    if (!queue || index >= queue.length) return;
    var item = queue[index];
    showAppPopup(item.type, item.message, function () {
      runQueue(queue, index + 1);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindConfirmForms();
    if (window.__APP_POPUPS && window.__APP_POPUPS.length) {
      runQueue(window.__APP_POPUPS, 0);
    }
  });
})();
