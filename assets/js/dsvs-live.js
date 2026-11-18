(() => {
    'use strict';

    async function refreshLiveTable(table) {
        if (document.hidden) {
            return;
        }

        const league = table.dataset.dsvsLeague || 'C2';
        const url = `${dsvsLive.endpoint}?liga=${encodeURIComponent(league)}`;

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            if (!data.html) {
                return;
            }

            const container = document.createElement('div');
            container.innerHTML = data.html;
            const replacement = container.firstElementChild;

            if (replacement) {
                table.replaceWith(replacement);
            }
        } catch (error) {
            console.warn(dsvsLive.errorMessage, error);
        }
    }

    function refreshAllLiveTables() {
        document.querySelectorAll('[data-dsvs-league]').forEach(refreshLiveTable);
    }

    window.setInterval(refreshAllLiveTables, Number(dsvsLive.intervalMs) || 60000);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshAllLiveTables();
        }
    });
})();
