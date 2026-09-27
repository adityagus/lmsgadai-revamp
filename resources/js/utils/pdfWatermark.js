import { PDFDocument, rgb, degrees, StandardFonts } from 'pdf-lib';
import { getSession } from '@/services/authService';

function sanitizeForPdf(text) {
  if (!text) return '';
  return String(text)
    .replace(/[\u2010-\u2015\u2013\u2014]/g, '-')
    .replace(/[\u2018\u2019\u201A]/g, "'")
    .replace(/[\u201C\u201D\u201E]/g, '"')
    .replace(/[^\x20-\x7E\xA0-\xFF]/g, '');
}

/**
 * Returns user NIK & Nama (line1) and formatted Date & Time (line2).
 */
export async function getUserWatermarkInfo(optionsText) {
  const now = new Date();
  const dateStr = now.toLocaleDateString('id-ID', {
    day: '2-digit', month: '2-digit', year: 'numeric'
  }).replace(/\//g, '-');
  const timeStr = now.toLocaleTimeString('id-ID', {
    hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
  }).replace(/\./g, ':');
  const line2 = `${dateStr} ${timeStr} WIB`;

  if (optionsText) {
    return { line1: sanitizeForPdf(optionsText), line2: sanitizeForPdf(line2) };
  }
  
  try {
    const res = await getSession();
    const auth = res?.auth || {};
    const nik = auth.npk || auth.user || '';
    const nama = auth.nama || auth.name || '';
    if (nik || nama) {
      const line1 = [nik, nama].filter(Boolean).join(' - ');
      return { line1: sanitizeForPdf(line1), line2: sanitizeForPdf(line2) };
    }
  } catch (err) {
    console.warn('Could not fetch user session for watermark:', err);
  }
  return { line1: 'DOKUMEN RESMI GADAI MULIA', line2: sanitizeForPdf(line2) };
}

export async function getUserWatermarkText(optionsText) {
  const info = await getUserWatermarkInfo(optionsText);
  return `${info.line1}\n${info.line2}`;
}

/**
 * Takes a raw PDF ArrayBuffer (or URL), embeds 2-line text watermark (Line 1: NIK & Name, Line 2: Timestamp) on EVERY page,
 * and returns a watermarked Blob, Blob URL, and Uint8Array.
 *
 * @param {ArrayBuffer|string} pdfInput - PDF ArrayBuffer or URL string to fetch
 * @param {Object} options - Watermark options { text }
 * @returns {Promise<{ blob: Blob, blobUrl: string, bytes: Uint8Array }>}
 */
export async function createWatermarkedPdf(pdfInput, options = {}) {
  let existingPdfBytes;
  if (typeof pdfInput === 'string') {
    const res = await fetch(pdfInput);
    if (!res.ok) {
      throw new Error(`Failed to fetch PDF from ${pdfInput}: ${res.statusText}`);
    }
    existingPdfBytes = await res.arrayBuffer();
  } else {
    existingPdfBytes = pdfInput;
  }

  // Load PDF with pdf-lib
  const pdfDoc = await PDFDocument.load(existingPdfBytes, { ignoreEncryption: true });

  // Embed Helvetica-Bold font
  const helveticaBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);

  const pages = pdfDoc.getPages();
  const watermarkInfo = await getUserWatermarkInfo(options.text);
  const line1 = sanitizeForPdf(watermarkInfo.line1);
  const line2 = sanitizeForPdf(watermarkInfo.line2);

  for (const page of pages) {
    const { width, height } = page.getSize();

    // Determine watermark centers on each page
    const positions = [];
    if (height > 1000) {
      const count = Math.ceil(height / 450);
      for (let i = 1; i <= count; i++) {
        positions.push({ x: width / 2, y: (height / (count + 1)) * i });
      }
    } else {
      positions.push({ x: width / 2, y: height * 0.35 });
      positions.push({ x: width / 2, y: height * 0.65 });
    }

    const angleDeg = -20;
    const angleRad = (angleDeg * Math.PI) / 180;

    for (const pos of positions) {
      const fontSize1 = Math.min(20, Math.max(13, width / 26));
      const fontSize2 = Math.min(16, Math.max(11, width / 32));

      const line1Width = helveticaBold.widthOfTextAtSize(line1, fontSize1);
      const line2Width = helveticaBold.widthOfTextAtSize(line2, fontSize2);

      // Line 1: NIK & Nama
      page.drawText(line1, {
        x: pos.x - (line1Width / 2) * Math.cos(angleRad),
        y: pos.y - (line1Width / 2) * Math.sin(angleRad),
        size: fontSize1,
        font: helveticaBold,
        color: rgb(0.45, 0.18, 0.9), // Purple theme color (#7F33FF)
        opacity: 0.22,
        rotate: degrees(angleDeg),
      });

      // Line 2: Tanggal & Jam Dibuka (Baris Kedua di bawah Line 1)
      const lineSpacing = fontSize1 * 1.35;
      const offsetX = lineSpacing * Math.sin(angleRad);
      const offsetY = -lineSpacing * Math.cos(angleRad);

      const line2X = pos.x + offsetX - (line2Width / 2) * Math.cos(angleRad);
      const line2Y = pos.y + offsetY - (line2Width / 2) * Math.sin(angleRad);

      page.drawText(line2, {
        x: line2X,
        y: line2Y,
        size: fontSize2,
        font: helveticaBold,
        color: rgb(0.45, 0.18, 0.9), // Purple theme color (#7F33FF)
        opacity: 0.22,
        rotate: degrees(angleDeg),
      });
    }
  }

  const watermarkedBytes = await pdfDoc.save();
  const blob = new Blob([watermarkedBytes], { type: 'application/pdf' });
  const blobUrl = URL.createObjectURL(blob);

  return { blob, blobUrl, bytes: watermarkedBytes };
}

/**
 * Downloads a watermarked version of a PDF from sourceUrl.
 * @param {string} sourceUrl - Source PDF URL
 * @param {string} fileName - Filename to save as
 * @param {Object} options - Extra options
 */
export async function downloadWatermarkedPdf(sourceUrl, fileName = 'dokumen.pdf', options = {}) {
  try {
    const { blobUrl } = await createWatermarkedPdf(sourceUrl, options);
    const link = document.createElement('a');
    link.href = blobUrl;
    link.download = fileName.endsWith('.pdf') ? fileName : `${fileName}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(() => URL.revokeObjectURL(blobUrl), 10000);
  } catch (err) {
    console.error('Error downloading watermarked PDF, falling back to direct download:', err);
    const link = document.createElement('a');
    link.href = sourceUrl;
    link.download = fileName;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
}
