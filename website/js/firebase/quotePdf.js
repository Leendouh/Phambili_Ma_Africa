// Client-side quotation PDF generator (replaces api/generate_quote_pdf.php).
// Lazy-loads jsPDF from CDN the first time it is needed — no build step,
// no server code. Called from bookings.php "Download" buttons.

const JSPDF_URL = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';

let jsPdfPromise = null;

function loadJsPdf() {
    if (window.jspdf?.jsPDF) return Promise.resolve(window.jspdf.jsPDF);
    if (!jsPdfPromise) {
        jsPdfPromise = new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = JSPDF_URL;
            s.onload = () => resolve(window.jspdf.jsPDF);
            s.onerror = () => reject(new Error('Could not load jsPDF'));
            document.head.appendChild(s);
        });
    }
    return jsPdfPromise;
}

const money = (n) => `R${Number(n || 0).toFixed(2)}`;

// `req` is the mapped request object from data.js (mapRequest shape).
export async function downloadQuotePdf(req) {
    const jsPDF = await loadJsPdf();
    const doc = new jsPDF({ unit: 'pt', format: 'a4' });
    const W = doc.internal.pageSize.getWidth();
    const margin = 48;
    let y = 0;

    // Header band
    doc.setFillColor(102, 126, 234);
    doc.rect(0, 0, W, 110, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(22);
    doc.text('PHAMBILI MA-AFRICA', margin, 48);
    doc.setFontSize(12);
    doc.setFont('helvetica', 'normal');
    doc.text('Trading Enterprise — Professional Cleaning Services', margin, 68);
    doc.setFontSize(9);
    doc.text('info@phambilimaafrica.co.za  |  +27 72 662 5951  |  +27 60 970 7444', margin, 86);
    doc.text('2676 Isikhwama Street, Phiri Extension, Phiri, Soweto, 1818', margin, 98);

    doc.setFontSize(26);
    doc.setFont('helvetica', 'bold');
    doc.text('QUOTATION', W - margin, 56, { align: 'right' });
    doc.setFontSize(11);
    doc.setFont('helvetica', 'normal');
    doc.text(req.refNumber || req.ID, W - margin, 76, { align: 'right' });
    doc.text(new Date().toLocaleDateString('en-ZA', { year: 'numeric', month: 'long', day: 'numeric' }),
        W - margin, 92, { align: 'right' });

    y = 150;

    // Bill-to block
    doc.setTextColor(60, 60, 60);
    doc.setFontSize(10);
    doc.setFont('helvetica', 'bold');
    doc.text('QUOTATION FOR', margin, y);
    doc.setFont('helvetica', 'normal');
    y += 16;
    doc.setFontSize(11);
    doc.text(req.customerName || 'Customer', margin, y); y += 14;
    if (req.customerPhone) { doc.text(req.customerPhone, margin, y); y += 14; }
    if (req.customerEmail) { doc.text(req.customerEmail, margin, y); y += 14; }
    if (req.Address) { doc.text(doc.splitTextToSize(req.Address, 240), margin, y); }
    y += 30;

    // Service details table (drawn manually)
    doc.setFillColor(245, 247, 250);
    doc.rect(margin, y, W - margin * 2, 26, 'F');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    doc.text('DESCRIPTION', margin + 10, y + 17);
    doc.text('AMOUNT', W - margin - 10, y + 17, { align: 'right' });
    y += 26;

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(11);
    const desc = `${req.serviceName || 'Cleaning service'} — ${req.Cleaning_Frequency || 'once-off'}`;
    const lines = doc.splitTextToSize(desc, W - margin * 2 - 140);
    doc.text(lines, margin + 10, y + 18);
    doc.setFont('helvetica', 'bold');
    doc.text(money(req.Quoted_Amount), W - margin - 10, y + 18, { align: 'right' });
    y += Math.max(30, lines.length * 14 + 10);

    doc.setDrawColor(220, 220, 220);
    doc.line(margin, y, W - margin, y);
    y += 22;
    doc.setFontSize(13);
    doc.text('TOTAL', margin + 10, y);
    doc.text(money(req.Quoted_Amount), W - margin - 10, y, { align: 'right' });
    y += 40;

    // Schedule block
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    doc.text('SCHEDULED SERVICE', margin, y);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(11);
    y += 16;
    doc.text(`Date: ${req.Date || 'To be confirmed'}`, margin, y); y += 14;
    doc.text(`Time: ${req.Time || 'To be confirmed'}`, margin, y); y += 14;
    if (req.Property_Type) { doc.text(`Property type: ${req.Property_Type}`, margin, y); y += 14; }
    y += 20;

    // Notes / instructions
    if (req.Special_Instructions || req.Notes) {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(10);
        doc.text('NOTES', margin, y); y += 16;
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        const noteLines = doc.splitTextToSize(req.Special_Instructions || req.Notes, W - margin * 2);
        doc.text(noteLines, margin, y);
        y += noteLines.length * 13 + 20;
    }

    // Terms
    doc.setFontSize(9);
    doc.setTextColor(120, 120, 120);
    doc.text(
        doc.splitTextToSize(
            'This quotation is valid for 14 days. To accept, sign in to your account and approve the quote, ' +
            'or contact us on the details above. Prices include VAT where applicable.',
            W - margin * 2),
        margin, y);

    doc.save(`Phambili-Quotation-${req.refNumber || req.ID}.pdf`);
}
