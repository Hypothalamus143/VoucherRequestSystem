/* public/js/main.js — Client-side interactivity for VRS */

'use strict';

/* ─── Auto-dismiss alerts after 4 s ─────────────────── */
document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .4s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 4000);
});

/* ─── Confirm before delete ─────────────────────────── */
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
        if (!confirm(el.dataset.confirm || 'Are you sure?')) {
            e.preventDefault();
        }
    });
});

/* ─── Reply compose auto-resize ─────────────────────── */
document.querySelectorAll('textarea.autoresize').forEach(el => {
    const resize = () => {
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    };
    el.addEventListener('input', resize);
    resize();
});

/* ─── Inline form AJAX for reply compose ────────────── */
const replyForm = document.getElementById('reply-form');
if (replyForm) {
    replyForm.addEventListener('submit', async e => {
        e.preventDefault();
        const btn = replyForm.querySelector('[type=submit]');
        const textarea = replyForm.querySelector('textarea');
        const msg = textarea.value.trim();
        if (!msg) return;

        btn.disabled = true;
        btn.textContent = 'Sending…';

        try {
            const fd = new FormData(replyForm);
            const res = await fetch(replyForm.action, { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                // Append new reply bubble to thread
                const thread = document.getElementById('reply-thread');
                const div = document.createElement('div');
                div.className = 'reply-item';
                div.innerHTML = buildReplyBubble(data.reply, true);
                thread.appendChild(div);

                textarea.value = '';
                textarea.style.height = 'auto';

                // Update empty state if visible
                const empty = thread.querySelector('.empty-state');
                if (empty) empty.remove();
            } else {
                alert(data.error || 'Could not send reply.');
            }
        } catch {
            alert('Network error. Please try again.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Send Reply';
        }
    });
}

function buildReplyBubble(reply, isMe) {
    const initial = reply.fname ? reply.fname[0].toUpperCase() : '?';
    const role = reply.user_type === 'S' ? 'student' : 'tsg';
    const dateStr = new Date(reply.datetime).toLocaleString('en-PH', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });

    return `
      <div class="reply-avatar ${role}">${initial}</div>
      <div class="reply-bubble ${isMe ? 'from-me' : ''}">
        <div class="reply-bubble-meta">
          <span>${reply.fname} ${reply.lname}</span>
          <span>${dateStr}</span>
        </div>
        <div class="reply-bubble-msg">${escapeHtml(reply.message)}</div>
      </div>`;
}

function escapeHtml(str) {
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/* ─── Password confirm validation ───────────────────── */
const confirmPw = document.getElementById('confirm_password');
const pw        = document.getElementById('password');
if (confirmPw && pw) {
    const check = () => {
        if (confirmPw.value && confirmPw.value !== pw.value) {
            confirmPw.setCustomValidity('Passwords do not match.');
        } else {
            confirmPw.setCustomValidity('');
        }
    };
    confirmPw.addEventListener('input', check);
    pw.addEventListener('input', check);
}
