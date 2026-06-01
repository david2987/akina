<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

try {
    $pdo = getDB();
    $input = json_decode(file_get_contents('php://input'), true);
    $cliente_id = $input['cliente_id'] ?? null;

    if (!$cliente_id) {
        throw new Exception('ID de cliente requerido');
    }

    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        throw new Exception('Cliente no encontrado');
    }

    if (!$cliente['comprobante_path']) {
        throw new Exception('Primero debe generar el comprobante de pago');
    }

    $destinatario = $cliente['email_pago'] ?: $cliente['email'];
    if (!$destinatario) {
        throw new Exception('El cliente no tiene email registrado');
    }

    $pdfPath = __DIR__ . '/../' . $cliente['comprobante_path'];
    if (!file_exists($pdfPath)) {
        throw new Exception('Archivo de comprobante no encontrado en el servidor');
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'a0061001.ferozo.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'info@akinacheck.com';
    $mail->Password = 'LqV2@2nG';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom('info@akinacheck.com', 'Akina Check');
    $mail->addAddress($destinatario);
    $mail->addReplyTo('info@akinacheck.com.ar', 'Akina Check');
    $mail->addAttachment($pdfPath);

    $mail->isHTML(true);
    $mail->Subject = 'Comprobante de Pago - Akina Check';

    $mail->Body = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"><style>
        body { font-family: Arial, sans-serif; background: #f4f7fa; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; }
        .header { background: #0e2951; padding: 30px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; }
        .body { padding: 30px; color: #333; }
        .body p { line-height: 1.6; }
        .btn { display: inline-block; background: #0e2951; color: #fff; padding: 12px 24px; border-radius: 6px; text-decoration: none; margin-top: 15px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #888; }
    </style></head>
    <body>
        <div class="container">
            <div class="header"><h1>Comprobante de Pago</h1></div>
            <div class="body">
                <p>Hola <strong>' . htmlspecialchars($cliente['nombre_apellido']) . '</strong>,</p>
                <p>Adjuntamos el comprobante de pago correspondiente al servicio de verificación precompra.</p>
                <p><strong>Importe abonado:</strong> $' . number_format((float)$cliente['importe'], 2, ',', '.') . '</p>
                <p>Si tiene alguna consulta, no dude en respondernos este correo.</p>
                <p>Saludos,<br><strong>Akina Check</strong></p>
            </div>
            <div class="footer">Akina Check — Verificación Precompra del Automotor<br>info@akinacheck.com.ar | +54 9 3416 11-8718</div>
        </div>
    </body>
    </html>';

    $mail->AltBody = "Comprobante de Pago - Akina Check\n\nHola {$cliente['nombre_apellido']},\nAdjuntamos el comprobante de pago por \$" . number_format((float)$cliente['importe'], 2, ',', '.') . ".\n\nSaludos,\nAkina Check";

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Comprobante enviado a ' . $destinatario]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
