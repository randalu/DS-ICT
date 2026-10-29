(() => {
    'use strict';
    const timeline = document.getElementById('itinerary-day-cards');
    if (!timeline) return;
    const cards = [...timeline.querySelectorAll('details.tl-card')];
    const controls = document.querySelector('[data-timeline-controls]');
    if (!cards.length || !controls) return;
    controls.hidden = false;
    const expand = controls.querySelector('[data-expand-days]');
    const collapse = controls.querySelector('[data-collapse-days]');
    const status = controls.querySelector('[role="status"]');
    function update() {
        const open = cards.filter(card => card.open).length;
        expand.disabled = open === cards.length;
        collapse.disabled = open === 0;
        status.textContent = `${open} of ${cards.length} days expanded`;
    }
    expand.addEventListener('click', () => { cards.forEach(card => {card.open = true;}); update(); });
    collapse.addEventListener('click', () => { cards.forEach(card => {card.open = false;}); update(); });
    cards.forEach(card => card.addEventListener('toggle', update));
    update();
})();
