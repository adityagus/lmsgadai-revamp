const fs = require('fs');
const path = require('path');

const targetFile = path.resolve('resources/js/pages/course-preview/ContentText.vue');
let content = fs.readFileSync(targetFile, 'utf8');

const newEmbedLogic = `function buildIframeForUrl(rawUrl) {
  if (!rawUrl) return null;
  const url = rawUrl.trim();

  // 1. YouTube (watch?v=, youtu.be, embed, shorts)
  const ytMatch = url.match(/(?:youtube\\.com\\/(?:watch\\?v=|embed\\/|shorts\\/)|youtu\\.be\\/)([\\w-]{11})/i);
  if (ytMatch) {
    const iframe = document.createElement('iframe');
    iframe.src = \`https://www.youtube.com/embed/\${ytMatch[1]}\`;
    iframe.style.width = '100%';
    iframe.style.aspectRatio = '16/9';
    iframe.style.minHeight = '360px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.style.display = 'block';
    iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
    iframe.allowFullscreen = true;
    return iframe;
  }

  // 2. Canva
  if (url.includes('canva.com/design/')) {
    let canvaUrl = url;
    if (!canvaUrl.includes('embed=true')) {
      canvaUrl = canvaUrl.replace(/([?&])embed(=[^&]*)?/i, '');
      canvaUrl += (canvaUrl.includes('?') ? '&' : '?') + 'embed=true';
    }
    const iframe = document.createElement('iframe');
    iframe.src = canvaUrl;
    iframe.style.width = '100%';
    iframe.style.aspectRatio = '16/9';
    iframe.style.minHeight = '480px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.style.display = 'block';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
    return iframe;
  }

  // 3. Google Workspace
  if (url.includes('docs.google.com/document/')) {
    const iframe = document.createElement('iframe');
    iframe.src = url.replace(/\\/edit.*$/, '/preview');
    iframe.style.width = '100%';
    iframe.style.minHeight = '560px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
    return iframe;
  }

  if (url.includes('docs.google.com/presentation/')) {
    const iframe = document.createElement('iframe');
    iframe.src = url.replace(/\\/edit.*$/, '/embed?start=false&loop=false&delayms=3000');
    iframe.style.width = '100%';
    iframe.style.aspectRatio = '16/9';
    iframe.style.minHeight = '480px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
    return iframe;
  }

  if (url.includes('docs.google.com/spreadsheets/')) {
    const iframe = document.createElement('iframe');
    iframe.src = url.replace(/\\/edit.*$/, '/preview?widget=true&headers=false');
    iframe.style.width = '100%';
    iframe.style.minHeight = '500px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
    return iframe;
  }

  if (url.includes('docs.google.com/forms/') || url.includes('forms.gle/')) {
    const iframe = document.createElement('iframe');
    iframe.src = url.includes('embedded=true') ? url : \`\${url}\${url.includes('?') ? '&' : '?'}embedded=true\`;
    iframe.style.width = '100%';
    iframe.style.minHeight = '560px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
    return iframe;
  }

  if (url.includes('drive.google.com/file/d/')) {
    const iframe = document.createElement('iframe');
    iframe.src = url.replace(/\\/view.*$/, '/preview');
    iframe.style.width = '100%';
    iframe.style.aspectRatio = '16/9';
    iframe.style.minHeight = '480px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
    return iframe;
  }

  // 4. Vimeo
  const vimeoMatch = url.match(/(?:vimeo\\.com\\/(?:video\\/)?|player\\.vimeo\\.com\\/video\\/)(\\d+)/i);
  if (vimeoMatch) {
    const iframe = document.createElement('iframe');
    iframe.src = \`https://player.vimeo.com/video/\${vimeoMatch[1]}\`;
    iframe.style.width = '100%';
    iframe.style.aspectRatio = '16/9';
    iframe.style.minHeight = '400px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.allow = 'autoplay; fullscreen; picture-in-picture';
    iframe.allowFullscreen = true;
    return iframe;
  }

  // 5. Loom
  const loomMatch = url.match(/loom\\.com\\/share\\/([a-zA-Z0-9]+)/i);
  if (loomMatch) {
    const iframe = document.createElement('iframe');
    iframe.src = \`https://www.loom.com/embed/\${loomMatch[1]}\`;
    iframe.style.width = '100%';
    iframe.style.aspectRatio = '16/9';
    iframe.style.minHeight = '400px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.allowFullscreen = true;
    return iframe;
  }

  // 6. Generic web URL
  if (/^https?:\\/\\//i.test(url)) {
    const iframe = document.createElement('iframe');
    iframe.src = url;
    iframe.style.width = '100%';
    iframe.style.minHeight = '500px';
    iframe.style.border = '0';
    iframe.style.borderRadius = '12px';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
    return iframe;
  }

  return null;
}

function replaceOembedWithIframe() {
  if (!contentWrapper.value) return;

  // 1. Process <oembed url="..."> or data-oembed-url
  const oembeds = contentWrapper.value.querySelectorAll('oembed[url], [data-oembed-url]');
  oembeds.forEach(el => {
    const url = el.getAttribute('url') || el.getAttribute('data-oembed-url');
    const iframe = buildIframeForUrl(url);
    if (iframe) {
      el.parentNode.replaceChild(iframe, el);
    }
  });

  // 2. Process standalone URL paragraphs (e.g. <p>https://www.canva.com/design/.../view?embed</p> or <p><a href="...">...</a></p>)
  const paragraphs = contentWrapper.value.querySelectorAll('p');
  paragraphs.forEach(p => {
    const textContent = p.textContent.trim();
    const linkChild = p.children.length === 1 && p.children[0].tagName === 'A' ? p.children[0] : null;
    const targetUrl = linkChild ? (linkChild.getAttribute('href') || textContent) : textContent;

    if (
      targetUrl &&
      /^(https?:\\/\\/)?(www\\.)?(canva\\.com\\/design|youtube\\.com|youtu\\.be|docs\\.google\\.com|drive\\.google\\.com|forms\\.gle|vimeo\\.com|player\\.vimeo\\.com|loom\\.com\\/share)/i.test(targetUrl)
    ) {
      const iframe = buildIframeForUrl(targetUrl);
      if (iframe) {
        p.parentNode.replaceChild(iframe, p);
      }
    }
  });

  // 3. Style existing iframes
  const existingIframes = contentWrapper.value.querySelectorAll('iframe');
  existingIframes.forEach(iframe => {
    if (!iframe.style.width) iframe.style.width = '100%';
    if (!iframe.style.borderRadius) iframe.style.borderRadius = '12px';
    iframe.style.border = '0';
    iframe.loading = 'lazy';
    iframe.allowFullscreen = true;
  });
}`;

// Replace function replaceOembedWithIframe() ...
const funcRegex = /function replaceOembedWithIframe\(\)\s*\{[\s\S]*?\n\}/;
if (!funcRegex.test(content)) {
  console.error('Could not find replaceOembedWithIframe function');
  process.exit(1);
}

content = content.replace(funcRegex, newEmbedLogic);

// Add iframe styles to <style scoped>
if (!content.includes('.ck-content iframe')) {
  content = content.replace(
    '</style>',
    `
.ck-content iframe {
  width: 100% !important;
  max-width: 100% !important;
  border-radius: 12px !important;
  border: 0 !important;
  margin-top: 1rem !important;
  margin-bottom: 1rem !important;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
}

.ck-content .media {
  clear: both;
  margin-top: 1rem;
  margin-bottom: 1rem;
  display: block;
  width: 100%;
}
</style>`
  );
}

fs.writeFileSync(targetFile, content, 'utf8');
console.log('Successfully updated ContentText.vue!');
