<?php
require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();

// Reusable FAQ editor — include inside any admin form that has a faq_json field.
// Usage: $faqJsonValue must be set before including this file.
$faqJsonValue = $faqJsonValue ?? (string) admin_form_value($record ?? [], 'faq_json');
?>

<div class="mb-0">
    <label class="form-label fw-semibold">Frequently Asked Questions</label>

    <div id="faq-builder" class="mb-2"></div>

    <button type="button" id="faq-add-btn" class="btn btn-sm btn-outline-secondary">
        + Add Question
    </button>

    <!-- Hidden field that the save handler reads -->
    <textarea name="faq_json" id="faq_json_hidden" class="d-none"><?= e($faqJsonValue) ?></textarea>
</div>

<style>
.faq-row { background:#f8fafc; border:1px solid #e2e8f0; border-radius:.75rem; padding:1rem; margin-bottom:.75rem; }
.faq-row input, .faq-row textarea { margin-bottom:.5rem; }
.faq-remove-btn { float:right; cursor:pointer; background:none; border:none; color:#ef4444; font-size:1.1rem; line-height:1; padding:0; }
</style>

<script>
(function () {
    const builder   = document.getElementById('faq-builder');
    const hidden    = document.getElementById('faq_json_hidden');
    const addBtn    = document.getElementById('faq-add-btn');
    const form      = hidden.closest('form');

    function addRow(q, a) {
        const row = document.createElement('div');
        row.className = 'faq-row';
        row.innerHTML = `
            <button type="button" class="faq-remove-btn" title="Remove">✕</button>
            <input type="text" class="form-control faq-question" placeholder="Question" value="">
            <textarea class="form-control faq-answer" rows="3" placeholder="Answer"></textarea>
        `;
        row.querySelector('.faq-question').value = q || '';
        row.querySelector('.faq-answer').value   = a || '';
        row.querySelector('.faq-remove-btn').addEventListener('click', () => row.remove());
        builder.appendChild(row);
    }

    // Load existing data
    try {
        const existing = JSON.parse(hidden.value.trim() || '[]');
        if (Array.isArray(existing)) {
            existing.forEach(item => addRow(item.question ?? item.q ?? '', item.answer ?? item.a ?? ''));
        }
    } catch (e) { /* invalid JSON — start empty */ }

    addBtn.addEventListener('click', () => addRow('', ''));
    hidden.addEventListener('seo:replace', () => {
        try {
            const items = JSON.parse(hidden.value || '[]');
            if (!Array.isArray(items)) return;
            builder.replaceChildren();
            items.forEach(item => addRow(item.question ?? item.q, item.answer ?? item.a));
        } catch (_) { /* The SEO service validates JSON before suggesting it. */ }
    });

    // Before submit: serialize rows into hidden textarea
    form.addEventListener('submit', () => {
        const rows = builder.querySelectorAll('.faq-row');
        const items = [];
        rows.forEach(row => {
            const q = row.querySelector('.faq-question').value.trim();
            const a = row.querySelector('.faq-answer').value.trim();
            if (q && a) items.push({ question: q, answer: a });
        });
        hidden.value = items.length ? JSON.stringify(items) : '';
    });
})();
</script>
