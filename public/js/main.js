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

// Build reply bubble with threading support
function buildReplyBubble(reply, isMe, parentName = null) {
    const initial   = reply.fname ? reply.fname[0].toUpperCase() : '?';
    const role      = reply.user_type === 'S' ? 'student' : 'tsg';
    const isChild   = reply.isFromRequest == 0;

    // Parse MySQL datetime string as Philippine time
    const raw     = reply.datetime.replace(' ', 'T') + '+08:00';
    const dateStr = new Date(raw).toLocaleString('en-PH', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });

    const parentRef = isChild && parentName ? 
        `<div class="reply-parent-ref">Replying to ${escapeHtml(parentName)}</div>` : '';

    return `
      <div class="reply-avatar ${role}">${initial}</div>
      <div class="reply-bubble ${isMe ? 'from-me' : ''}">
        ${parentRef}
        <div class="reply-bubble-meta">
          <span>
            ${escapeHtml(reply.fname)} ${escapeHtml(reply.lname)}
            ${reply.user_type === 'T' ? '<em style="color:var(--gold); font-size:0.7rem;"> · TSG</em>' : ''}
          </span>
          <span>${dateStr}</span>
        </div>
        <div class="reply-bubble-msg">${escapeHtml(reply.message || '')}</div>
        <div style="margin-top:.5rem;">
          <button class="reply-delete-btn reply-to-btn"
                  data-reply-id="${reply.replyID}"
                  data-reply-name="${escapeHtml(reply.fname + ' ' + reply.lname)}">
            ↩ Reply
          </button>
        </div>
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

// Build the entire reply thread as a tree structure
// Build the entire reply thread as a tree structure (vertical with indentation)
function buildThreadedReplies(replies) {
    if (!replies || replies.length === 0) {
        return '<div class="empty-state">No replies yet.</div>';
    }
    
    // Map replies by ID for quick lookup
    const replyMap = new Map();
    replies.forEach(reply => {
        replyMap.set(reply.replyID, { ...reply, children: [] });
    });
    
    // Build tree structure
    const roots = [];
    replyMap.forEach(reply => {
        const parentId = reply.isFromRequest ? null : reply.parentID;
        if (parentId === null || parentId === undefined || !replyMap.has(parentId)) {
            roots.push(reply);
        } else {
            const parent = replyMap.get(parentId);
            parent.children.push(reply);
        }
    });
    
    // Sort children by datetime (oldest first)
    replyMap.forEach(reply => {
        reply.children.sort((a, b) => new Date(a.datetime) - new Date(b.datetime));
    });
    
    // Render tree recursively (vertical layout with indentation)
    function renderReply(reply, level = 0, parentName = null) {
        const isMe = false;
        const replyHtml = buildReplyBubble(reply, isMe, parentName);
        const indentLevel = level * 2.5; // 2.5rem indent per level
        
        if (reply.children && reply.children.length > 0) {
            const childrenHtml = reply.children.map(child => 
                renderReply(child, level + 1, `${reply.fname} ${reply.lname}`)
            ).join('');
            
            return `
                <div class="reply-thread-item" style="margin-left: ${indentLevel}rem;">
                    ${replyHtml}
                    ${childrenHtml}
                </div>
            `;
        }
        
        return `
            <div class="reply-thread-item" style="margin-left: ${indentLevel}rem;">
                ${replyHtml}
            </div>
        `;
    }
    
    // Render all roots (level 0 = no indent)
    return roots.map(root => renderReply(root, 0, null)).join('');
}

/* ─── Reply form submission (ONLY ONE, using threaded version) ── */
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
                // Reload the page to get updated threaded view
                window.location.reload();
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

/* ─── Reply-to-reply buttons ─────────────────────────── */
const replyingToBar    = document.getElementById('replying-to');
const replyingToLabel  = document.getElementById('replying-to-label');
const parentIdInput    = document.getElementById('parent-id-input');
const isFromReqInput   = document.getElementById('is-from-request-input');
const cancelReplyTo    = document.getElementById('cancel-reply-to');
const requestId        = parentIdInput ? parentIdInput.defaultValue : null;

function resetComposeTarget() {
    if (!parentIdInput) return;
    parentIdInput.value    = requestId;
    isFromReqInput.value   = '1';
    replyingToBar.style.display  = 'none';
    replyingToLabel.textContent  = '';
}

document.addEventListener('click', e => {
    const btn = e.target.closest('.reply-to-btn');
    if (!btn) return;
    const replyId   = btn.dataset.replyId;
    const replyName = btn.dataset.replyName;

    parentIdInput.value   = replyId;
    isFromReqInput.value  = '0';
    replyingToBar.style.display  = 'flex';
    replyingToLabel.textContent  = `↩ Replying to ${replyName}`;

    // Focus the textarea
    document.querySelector('#reply-form textarea')?.focus();
});

cancelReplyTo?.addEventListener('click', resetComposeTarget);

/* ─── Password confirmation ─────────────────────────── */
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