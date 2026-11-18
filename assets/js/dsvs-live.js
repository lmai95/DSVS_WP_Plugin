(() => {
  'use strict';
  const refresh = async (target) => {
    if (!target || document.hidden) return;
    const league = target.dataset.dsvsLeague || 'C2';
    try {
      const url = dsvsLive.baseUrl + '?liga=' + encodeURIComponent(league);
      const response = await fetch(url, {headers:{Accept:'application/json'},cache:'no-store'});
      if (!response.ok) throw new Error(String(response.status));
      const data = await response.json();
      if (data.html) { const box=document.createElement('div'); box.innerHTML=data.html; const next=box.firstElementChild; if(next)target.replaceWith(next); }
    } catch (error) { console.warn(dsvsLive.error,error); }
  };
  const refreshAll = () => document.querySelectorAll('[data-dsvs-league]').forEach(refresh);
  window.setInterval(refreshAll, Number(dsvsLive.interval)||60000);
  document.addEventListener('visibilitychange',()=>{if(!document.hidden)refreshAll();});
})();
