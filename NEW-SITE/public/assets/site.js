(() => {
  'use strict';

  const toggle = document.querySelector('.menu-toggle');
  const menu = document.querySelector('#mobile-menu');
  if (toggle && menu) {
    const closeMenu = (restoreFocus = false) => {
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Open navigation');
      menu.classList.remove('is-open');
      if (restoreFocus) toggle.focus();
    };
    toggle.addEventListener('click', () => {
      const open = toggle.getAttribute('aria-expanded') !== 'true';
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
      menu.classList.toggle('is-open', open);
      if (open) menu.querySelector('a')?.focus();
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') closeMenu(true);
    });
    document.addEventListener('click', (event) => {
      if (!menu.contains(event.target) && !toggle.contains(event.target)) closeMenu();
    });
    menu.addEventListener('click', (event) => {
      if (event.target.closest('a')) closeMenu();
    });
    window.matchMedia('(min-width: 1025px)').addEventListener('change', (event) => {
      if (event.matches) closeMenu();
    });
  }
  document.documentElement.classList.add('is-enhanced');

  const selector = document.querySelector('[data-journey-select]');
  const panels = [...document.querySelectorAll('[data-journey-panel]')];
  if (selector && panels.length) {
    const selectJourney = () => {
      const selected = Number(selector.value);
      if (!Number.isInteger(selected) || selected < 0 || selected >= panels.length) return;
      for (const panel of panels) panel.hidden = Number(panel.dataset.journeyPanel) !== selected;
      const status = document.querySelector('[data-journey-status]');
      if (status) status.textContent = `${selector.selectedOptions[0].textContent} selected.`;
      const currentUrl = new URL(window.location.href);
      currentUrl.searchParams.set('journey', String(selected));
      window.history.replaceState(null, '', currentUrl);
    };
    selector.addEventListener('change', selectJourney);
    selector.form.addEventListener('submit', (event) => {
      event.preventDefault();
      selectJourney();
    });
  }

  const experienceImage = document.querySelector('.experience-section__image img');
  for (const details of document.querySelectorAll('[data-experience-image]')) {
    details.addEventListener('toggle', () => {
      if (!details.open || !experienceImage) return;
      for (const source of experienceImage.parentElement.querySelectorAll('source')) source.remove();
      experienceImage.src = details.dataset.experienceImage;
      experienceImage.alt = details.dataset.experienceAlt;
      const caption = document.querySelector('[data-experience-caption]');
      if (caption) caption.textContent = details.dataset.experienceTitle;
    });
  }

  const dialog = document.querySelector('#preview-dialog');
  if (dialog && typeof dialog.showModal === 'function') {
    for (const link of document.querySelectorAll('[data-preview-open]')) {
      link.addEventListener('click', (event) => {
        event.preventDefault();
        if (!dialog.open) dialog.showModal();
      });
    }
    for (const button of dialog.querySelectorAll('[data-preview-close]')) button.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
      const rect = dialog.getBoundingClientRect();
      if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
    });
  }
})();
