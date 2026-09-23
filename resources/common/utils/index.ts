import jsPDF from 'jspdf';

interface IPdfOptions {
    name?: string; // Filename for the downloaded PDF
    x?: number; // X coordinate for content positioning
    y?: number; // Y coordinate for content positioning
    width?: number; // Width of the content area
    windowWidth?: number; // Width of the window for rendering
    unit?: 'pt' | 'px' | 'in' | 'mm' | 'cm' | 'ex' | 'em' | 'pc';
    format?: string | number[];
}

/**
 * Generates and downloads a PDF document.
 * @param ref - HTML element or string to convert to PDF.
 * @param options - Configuration options for the PDF generation.
 */
export const downloadPdf = (
    ref: HTMLElement | null,
    options: IPdfOptions = {
        name: 'Facture EasyMoniteur',
        x: 5,
        y: 5,
        width: 200,
        windowWidth: 1000,
        unit: 'mm',
        format: 'a4',
    }
): void => {
    const { name, x, y, width, windowWidth, unit, format } = options;

    const doc = new jsPDF({
        orientation: 'portrait',
        unit,
        format, // Defaulting to A4 format
    });

    doc.html(ref, {
        callback: (pdf: jsPDF) => {
            pdf.save(`${name}.pdf`); // Save the generated PDF with the provided name
        },
        x, // Horizontal position in the PDF
        y, // Vertical position in the PDF
        width, // Content area width
        windowWidth, // Browser window width for rendering
    });
};
