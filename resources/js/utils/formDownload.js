export function formDownloadUrl(card) {
  if (Number(card?.menu?.id_menu) !== 2) return null;

  const source = card.url || card.content_url || card.file || card.path || card.file_path;
  if (!source || typeof source !== 'string') return '';

  // Legacy records may contain an absolute URL or an already encoded storage path.
  let path = source.trim();
  if (/^https?:\/\//i.test(path)) path = new URL(path).pathname;
  path = path.replace(/^\/+/, '').replace(/^(?:public|storage)\//, '');
  if (!/^(?:aktif|tidakaktif|uploads)\//.test(path)) path = `aktif/${path}`;
  path = path.split('/').map(part => {
    try { return encodeURIComponent(decodeURIComponent(part)); }
    catch { return encodeURIComponent(part); }
  }).join('/');
  return `/storage/${path}?download=1`;
}

export function downloadForm(url) {
  const link = document.createElement('a');
  link.href = url;
  link.download = '';
  document.body.appendChild(link);
  link.click();
  link.remove();
}
