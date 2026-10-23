/**
 * Clicks of the menu items on the site: a followed link with `data-menu-item` (see App\Models\Menu)
 * sends its item to `menuClicks.url`; the middle button opens it too, so it is counted as well.
 *
 * @since 2027.1
 */
(() => {
    const url = window.menuClicks?.url;
    if (!url || !navigator.sendBeacon) {
        return;
    }

    const count = event => {
        const link = event.target instanceof Element ? event.target.closest('a[data-menu-item]') : null;
        if (link && (event.type === 'click' || event.button === 1)) {
            navigator.sendBeacon(url, new URLSearchParams({ item: link.dataset.menuItem }));
        }
    };

    document.addEventListener('click', count, { capture: true });
    document.addEventListener('auxclick', count, { capture: true });
})();
