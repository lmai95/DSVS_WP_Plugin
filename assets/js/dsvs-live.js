(() => {
    'use strict';

    const allowedTags = new Set([
        'SECTION', 'HEADER', 'H2', 'H3', 'DIV', 'TABLE', 'THEAD', 'TBODY',
        'TR', 'TH', 'TD', 'P', 'STRONG', 'SPAN', 'BR'
    ]);

    function copySafeNode(node) {
        if (node.nodeType === Node.TEXT_NODE) {
            return document.createTextNode(node.nodeValue || '');
        }
        if (node.nodeType !== Node.ELEMENT_NODE || !allowedTags.has(node.tagName)) {
            return null;
        }

        const safe = document.createElement(node.tagName.toLowerCase());
        for (const attribute of Array.from(node.attributes)) {
            if (attribute.name === 'class' && /^(dsvs-[a-z0-9-]+)(\\s+dsvs-[a-z0-9-]+)*$/i.test(attribute.value)) {
                safe.setAttribute('class', attribute.value);
            } else if (attribute.name === 'id' && /^dsvs-live-[a-z0-9-]+$/i.test(attribute.value)) {
                safe.id = attribute.value;
            } else if (attribute.name === 'data-dsvs-league' && /^[a-z0-9-]+$/i.test(attribute.value)) {
                safe.dataset.dsvsLeague = attribute.value;
            }
        }
        for (const child of Array.from(node.childNodes)) {
            const safeChild = copySafeNode(child);
            if (safeChild) safe.appendChild(safeChild);
        }
        return safe;
    }

    async function refresh(table) {
        if (document.hidden) return;
        const league = table.dataset.dsvsLeague || 'C2';
        try {
            const response = await fetch(`${dsvsLive.endpoint}?liga=${encodeURIComponent(league)}`, {
                headers: { Accept: 'application/json' },
                cache: 'no-store'
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            if (typeof data.html !== 'string') throw new Error('Invalid response');

            // Parse as inert HTML, then rebuild only known presentation tags and safe attributes.
            const parsed = new DOMParser().parseFromString(data.html, 'text/html');
            const root = parsed.body.firstElementChild;
            const safeRoot = root ? copySafeNode(root) : null;
            if (safeRoot && table.isConnected) table.replaceWith(safeRoot);
        } catch (error) {
            console.warn(dsvsLive.errorMessage, error);
        }
    }

    function refreshAll() {
        document.querySelectorAll('[data-dsvs-league]').forEach(refresh);
    }

    setInterval(refreshAll, Number(dsvsLive.intervalMs) || 60000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refreshAll();
    });
})();
