<?php
require_once __DIR__ . '/../includes/api_bootstrap.inc.php';

$booking_id = $_GET['id'] ?? 0;

if (!$booking_id) {
    die('Booking ID required');
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, u.full_name as Customer_Name, u.email as Customer_Email, u.phone as Customer_Phone,
               s.Name as Service_Name, s.Description as Service_Description
        FROM bookings b 
        LEFT JOIN customers u ON b.Customer_ID = u.ID 
        LEFT JOIN services s ON b.Service_ID = s.ID
        WHERE b.ID = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        die('Booking not found');
    }

    // Generate HTML for PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Quote - Booking #' . $booking_id . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; color: #333; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #667eea; padding-bottom: 20px; }
            .logo { font-size: 24px; font-weight: bold; color: #667eea; }
            .quote-title { font-size: 28px; color: #333; margin: 10px 0; }
            .quote-number { color: #666; font-size: 14px; }
            .section { margin: 20px 0; }
            .section-title { font-size: 16px; font-weight: bold; color: #667eea; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; }
            .info-grid { display: table; width: 100%; }
            .info-row { display: table-row; }
            .info-label { display: table-cell; width: 150px; padding: 8px 0; font-weight: bold; color: #555; }
            .info-value { display: table-cell; padding: 8px 0; }
            .quote-amount { font-size: 32px; color: #28a745; font-weight: bold; text-align: center; margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 10px; }
            .breakdown { background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 20px 0; }
            .breakdown-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
            .breakdown-item:last-child { border-bottom: none; }
            .total-row { font-weight: bold; font-size: 18px; color: #28a745; margin-top: 10px; }
            .footer { margin-top: 40px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px; }
            .validity { background: #fff3cd; padding: 10px; border-radius: 5px; text-align: center; margin: 20px 0; }
            .status { display: inline-block; padding: 5px 15px; background: #667eea; color: white; border-radius: 20px; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="logo">Phambili Ma-Africa</div>
            <div class="quote-title">SERVICE QUOTATION</div>
            <div class="quote-number">Quote #' . str_pad($booking_id, 6, '0', STR_PAD_LEFT) . ' | Date: ' . date('F j, Y') . '</div>
        </div>

        <div class="section">
            <div class="section-title">CUSTOMER INFORMATION</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Name:</div>
                    <div class="info-value">' . htmlspecialchars($booking['Customer_Name']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value">' . htmlspecialchars($booking['Customer_Email']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Phone:</div>
                    <div class="info-value">' . htmlspecialchars($booking['Customer_Phone'] ?? 'N/A') . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Address:</div>
                    <div class="info-value">' . htmlspecialchars($booking['Address']) . '</div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">SERVICE DETAILS</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Service:</div>
                    <div class="info-value">' . htmlspecialchars($booking['Service_Name']) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Property Type:</div>
                    <div class="info-value">' . htmlspecialchars($booking['Property_Type'] ?? 'N/A') . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Frequency:</div>
                    <div class="info-value">' . htmlspecialchars($booking['Cleaning_Frequency'] ?? 'One-time') . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Scheduled Date:</div>
                    <div class="info-value">' . date('F j, Y', strtotime($booking['Date'])) . ' at ' . date('g:i A', strtotime($booking['Time'])) . '</div>
                </div>
            </div>
        </div>';

    if ($booking['Special_Instructions']) {
        $html .= '
        <div class="section">
            <div class="section-title">SPECIAL INSTRUCTIONS</div>
            <p>' . htmlspecialchars($booking['Special_Instructions']) . '</p>
        </div>';
    }

    $html .= '
        <div class="section">
            <div class="section-title">QUOTATION</div>
            <div class="quote-amount">
                R ' . number_format($booking['Quoted_Amount'], 2) . '
                <div style="font-size: 14px; color: #666; font-weight: normal; margin-top: 5px;">' . ucfirst($booking['Status']) . '</div>
            </div>';

    if (!empty($booking['quote_breakdown'])) {
        $html .= '
            <div class="breakdown">
                <strong>Quote Breakdown:</strong>
                <div style="margin-top: 10px;">' . nl2br(htmlspecialchars($booking['quote_breakdown'])) . '</div>
            </div>';
    }

    $validity_days = $booking['quote_validity_days'] ?? 7;
    if ($validity_days) {
        $validity_date = date('F j, Y', strtotime('+' . $validity_days . ' days'));
        $html .= '
            <div class="validity">
                This quote is valid until <strong>' . $validity_date . '</strong> (' . $validity_days . ' days)
            </div>';
    }

    if (!empty($booking['quote_notes'])) {
        $html .= '
            <div class="section">
                <div class="section-title">NOTES</div>
                <p>' . nl2br(htmlspecialchars($booking['quote_notes'])) . '</p>
            </div>';
    }

    $html .= '
        </div>

        <div class="footer">
            <p><strong>Phambili Ma-Africa Cleaning Services</strong></p>
            <p>Professional Cleaning Solutions | South Africa</p>
            <p>This is a computer-generated quote. No signature required.</p>
            <p>Generated on: ' . date('F j, Y g:i A') . '</p>
        </div>
    </body>
    </html>';

    // Output as HTML (customer can print to PDF)
    echo $html;

} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}
