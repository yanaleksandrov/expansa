(() => {
    const url = window.menuClicks?.url;
    if (!url || !navigator.sendBeacon) {
        return;
    }
    const count = event => {
        const link = event.target instanceof Element ? event.target.closest('a[data-menu-item]') : null;
        if (link && (event.type === 'click' || event.button === 1)) {
            navigator.sendBeacon(url, new URLSearchParams({
                item: link.dataset.menuItem
            }));
        }
    };
    document.addEventListener('click', count, {
        capture: true
    });
    document.addEventListener('auxclick', count, {
        capture: true
    });
})();