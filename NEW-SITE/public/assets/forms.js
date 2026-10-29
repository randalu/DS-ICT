(() => {
  'use strict';

  for (const form of document.querySelectorAll('[data-demo-form]')) {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      if (!form.reportValidity()) return;
      const status = form.querySelector('[data-demo-status]');
      if (!status) return;
      status.hidden = false;
      status.textContent = form.dataset.demoMessage;
    });
    const fields = form.querySelector('[data-demo-fields]');
    if (fields) fields.disabled = false;
  }

  const privacyButton = document.querySelector('[data-privacy-apply]');
  if (privacyButton) {
    for (const input of document.querySelectorAll('[data-preview-choice]')) input.disabled = false;
    privacyButton.disabled = false;
    privacyButton.addEventListener('click', () => {
      const status = document.querySelector('[data-privacy-status]');
      if (status) {
        status.hidden = false;
        status.textContent = 'Sample choices previewed until this page is reloaded. No cookie or original-site choice changed; all providers remain off.';
      }
    });
    privacyButton.closest('form')?.addEventListener('submit', (event) => event.preventDefault());
  }

  const comparison = document.querySelector('[data-comparison]');
  if (comparison) {
    const choices = [...comparison.querySelectorAll('[data-compare-id]')];
    const link = comparison.querySelector('[data-compare-link]');
    const status = comparison.querySelector('[data-compare-status]');
    const update = () => {
      const selected = choices.filter((choice) => choice.checked);
      for (const choice of choices) choice.disabled = selected.length >= 4 && !choice.checked;
      const target = new URL('/compare', window.location.origin);
      target.searchParams.set('ids', selected.map((choice) => choice.value).join(','));
      link.href = target.pathname + target.search;
      link.setAttribute('aria-disabled', selected.length === 0 ? 'true' : 'false');
      status.textContent = `${selected.length} selected. Choose between one and four journeys.`;
    };
    for (const choice of choices) choice.addEventListener('change', update);
    link.addEventListener('click', (event) => {
      if (link.getAttribute('aria-disabled') === 'true') event.preventDefault();
    });
    update();
  }
})();
