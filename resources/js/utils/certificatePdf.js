import { PDFDocument, StandardFonts, rgb } from 'pdf-lib';

function hexToRgb(hex) {
  const clean = String(hex || '#1F2937').replace('#', '');
  const full = clean.length === 3
    ? clean.split('').map((c) => c + c).join('')
    : clean.padEnd(6, '0').slice(0, 6);
  const num = parseInt(full, 16);
  return rgb(
    ((num >> 16) & 255) / 255,
    ((num >> 8) & 255) / 255,
    (num & 255) / 255,
  );
}

function formatIndoDate(dateStr) {
  const date = dateStr ? new Date(dateStr) : new Date();
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
}

function drawCentered(page, font, text, percentX, percentY, size, color) {
  const { width, height } = page.getSize();
  const safeText = String(text || '');
  const textWidth = font.widthOfTextAtSize(safeText, size);
  const x = Math.max(12, Math.min(width - textWidth - 12, (width * Number(percentX)) / 100 - textWidth / 2));
  const y = Math.max(12, Math.min(height - size - 12, height - (height * Number(percentY)) / 100 - size / 2));
  page.drawText(safeText, { x, y, size, font, color });
}

export async function generateCertificatePdf({ courseName, recipientName, certificateNumber, issuedAt, template }) {
  let pdfDoc;
  let page;

  if (template?.file_url) {
    const res = await fetch(template.file_url, { credentials: 'include' });
    if (!res.ok) {
      throw new Error('Gagal mengunduh file template sertifikat.');
    }
    const bytes = new Uint8Array(await res.arrayBuffer());

    if (String(template.file_type).toLowerCase() === 'pdf') {
      pdfDoc = await PDFDocument.load(bytes);
      page = pdfDoc.getPages()[0];
    } else {
      pdfDoc = await PDFDocument.create();
      page = pdfDoc.addPage([841.89, 595.28]); // A4 landscape
      const img = String(template.file_type).toLowerCase() === 'png'
        ? await pdfDoc.embedPng(bytes)
        : await pdfDoc.embedJpg(bytes);
      page.drawImage(img, { x: 0, y: 0, width: 841.89, height: 595.28 });
    }

    const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
    const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);
    const color = hexToRgb(template.font_color || '#1F2937');
    const size = Number(template.font_size || 36);

    drawCentered(page, fontBold, recipientName, template.name_x ?? 50, template.name_y ?? 50, size, color);

    if (template.show_number !== false) {
      drawCentered(page, fontRegular, `No: ${certificateNumber}`, template.number_x ?? 50, template.number_y ?? 80, Math.max(11, Math.round(size * 0.4)), color);
    }
    if (template.show_date !== false) {
      drawCentered(page, fontRegular, formatIndoDate(issuedAt), template.date_x ?? 50, template.date_y ?? 86, Math.max(11, Math.round(size * 0.4)), color);
    }
  } else {
    // Built-in default certificate design
    pdfDoc = await PDFDocument.create();
    page = pdfDoc.addPage([841.89, 595.28]);
    const purple = rgb(0.4, 0.184, 1);
    const dark = rgb(0.024, 0.039, 0.137);
    const muted = rgb(0.514, 0.549, 0.616);

    page.drawRectangle({ x: 24, y: 24, width: 793.89, height: 547.28, borderColor: purple, borderWidth: 4 });
    page.drawRectangle({ x: 36, y: 36, width: 769.89, height: 523.28, borderColor: rgb(0.81, 0.86, 0.94), borderWidth: 1 });

    const fontBold = await pdfDoc.embedFont(StandardFonts.HelveticaBold);
    const fontRegular = await pdfDoc.embedFont(StandardFonts.Helvetica);

    drawCentered(page, fontBold, 'SERTIFIKAT KELULUSAN', 50, 20, 32, purple);
    drawCentered(page, fontRegular, 'Diberikan kepada:', 50, 34, 14, muted);
    drawCentered(page, fontBold, recipientName, 50, 46, 36, dark);
    drawCentered(page, fontRegular, 'Telah menyelesaikan seluruh materi pembelajaran pada kelas:', 50, 58, 14, muted);
    drawCentered(page, fontBold, courseName, 50, 66, 20, dark);
    drawCentered(page, fontRegular, `Nomor Sertifikat: ${certificateNumber}`, 50, 80, 12, muted);
    drawCentered(page, fontRegular, `Diterbitkan pada ${formatIndoDate(issuedAt)}`, 50, 85, 12, muted);
  }

  const outBytes = await pdfDoc.save();
  const blob = new Blob([outBytes], { type: 'application/pdf' });
  const url = URL.createObjectURL(blob);
  const safeCourse = String(courseName || 'Kelas').replace(/[^a-zA-Z0-9-_ ]/g, '').trim().replace(/\s+/g, '-');
  const safeName = String(recipientName || 'Peserta').replace(/[^a-zA-Z0-9-_ ]/g, '').trim().replace(/\s+/g, '-');
  const link = document.createElement('a');
  link.href = url;
  link.download = `Sertifikat-${safeCourse}-${safeName}.pdf`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}
