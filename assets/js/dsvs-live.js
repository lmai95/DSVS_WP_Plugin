(() => {
  'use strict';
  const refresh = async () => {
    const target = document.getElementById('dsvs-live-results');
    if (!target || document.hidden) return;
    try {
      const response = await fetch(dsvsLive.url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
      if (!response.ok) throw new Error(String(response.status));
      const data = await response.json();
      if (data.html) {
        const box = document.createElement('div'); box.innerHTML = data.html;
        const next = box.firstElementChild; if (next) target.replaceWith(next);
      }
    } catch (error) { console.warn(dsvsLive.error, error); }
  };
  window.setInterval(refresh, Number(dsvsLive.interval) || 60000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
})();
