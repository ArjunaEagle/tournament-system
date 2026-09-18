const $ = (s, c=document) => c.querySelector(s);
const $$ = (s, c=document) => [...c.querySelectorAll(s)];
const csrf = () => $('meta[name="csrf-token"]')?.content || '';

function toast(message, type='success') {
  const el = $('#toast'); if (!el) return;
  el.textContent = message; el.className = `toast show ${type}`;
  clearTimeout(window.toastTimer); window.toastTimer = setTimeout(() => el.classList.remove('show'), 2800);
}

async function api(url, options={}) {
  options.headers = {'X-CSRF-Token': csrf(), ...(options.headers || {})};
  const response = await fetch(url, options);
  const data = await response.json().catch(() => ({success:false,message:'Invalid server response'}));
  if (!response.ok || !data.success) throw new Error(data.message || 'Request failed');
  return data;
}

$('.nav-toggle')?.addEventListener('click', () => $('.public-nav nav').classList.toggle('open'));
$('.sidebar-toggle')?.addEventListener('click', () => $('#sidebar').classList.toggle('open'));
$$('[data-confirm]').forEach(el => el.addEventListener('click', e => { if (!confirm(el.dataset.confirm)) e.preventDefault(); }));

document.addEventListener('submit', async e => {
  const form = e.target.closest('[data-ajax-form]'); if (!form) return;
  e.preventDefault(); const button = form.querySelector('[type="submit"]');
  button?.classList.add('loading');
  try { const result = await api(form.action, {method: form.method || 'POST', body: new FormData(form)}); toast(result.message); if (form.dataset.reload !== 'false') setTimeout(() => location.reload(), 500); }
  catch (err) { toast(err.message, 'error'); }
  finally { button?.classList.remove('loading'); }
});

