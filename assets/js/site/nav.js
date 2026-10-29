/**
 * Mobile navigation.
 *
 * The drawer is a plain button + panel pair in the markup; this module only
 * adds the open/close state, Escape handling, focus return and a scroll lock.
 * With JavaScript disabled the drawer never opens, and every destination is
 * still reachable from the footer.
 */
const toggle = document.querySelector('[data-ic-nav-toggle]');
const drawer = document.querySelector('[data-ic-drawer]');

if (toggle && drawer) {
    const setState = (open) => {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        drawer.hidden = !open;
        document.body.classList.toggle('ic-drawer-open', open);

        if (open) {
            const firstLink = drawer.querySelector('a, button');
            if (firstLink) {
                firstLink.focus({ preventScroll: true });
            }
        }
    };

    toggle.addEventListener('click', () => {
        setState(toggle.getAttribute('aria-expanded') !== 'true');
    });

    drawer.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.closest('a')) {
            setState(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            setState(false);
            toggle.focus();
        }
    });

    const desktop = window.matchMedia('(min-width: 1101px)');
    desktop.addEventListener('change', (event) => {
        if (event.matches) {
            setState(false);
        }
    });
}

/**
 * Desktop submenus are CSS-driven (:hover / :focus-within). Supporting touch
 * tap on the trigger keeps the second level reachable on hybrid devices.
 */
document.querySelectorAll('.ic-nav__item.has-menu > .ic-nav__link').forEach((link) => {
    link.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            const menu = link.parentElement?.querySelector('.ic-nav__menu a');
            if (menu) {
                event.preventDefault();
                menu.focus();
            }
        }
    });
});
