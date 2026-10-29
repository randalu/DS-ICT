/**
 * Filter bar enhancement.
 *
 * The bar is a plain GET form and works without this file. With JavaScript, a
 * changed select submits immediately (and the Apply button is hidden), which
 * matches how people expect a filter bar to behave on mobile.
 *
 * Search stays explicit: typing in the search field does not auto-submit, so a
 * half-typed query never hits the server.
 */
const form = document.querySelector('[data-ic-filters]');

if (form instanceof HTMLFormElement) {
    form.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', () => {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    });
}
