<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../db.php';

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

    if (!$cliente['importe'] || !$cliente['fecha_pago']) {
        throw new Exception('Complete Fecha de Pago, Hora e Importe antes de generar el comprobante');
    }

    $regenerar = $input['regenerar'] ?? false;

    if ($regenerar && $cliente['comprobante_path']) {
        $oldFile = __DIR__ . '/../' . $cliente['comprobante_path'];
        if (file_exists($oldFile)) {
            unlink($oldFile);
        }
    }

    $logoPath = __DIR__ . '/../../assets/images/Akina-Logo.jpg';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoBase64 = base64_encode(file_get_contents($logoPath));
    }

    $bannerPath = __DIR__ . '/../../assets/images/Akina-Banner.png';
    $bannerBase64 = '';
    if (file_exists($bannerPath)) {
        $bannerBase64 = base64_encode(file_get_contents($bannerPath));
    }

    $importe = number_format((float)$cliente['importe'], 2, ',', '.');
    $fechaPago = date('d/m/Y', strtotime($cliente['fecha_pago']));
    $horaPago = $cliente['hora_pago'] ? date('H:i', strtotime($cliente['hora_pago'])) : '';
    $nombreCliente = htmlspecialchars($cliente['nombre_apellido']);
    $localidad = htmlspecialchars($cliente['localidad'] ?? '');
    $emailCli = htmlspecialchars($cliente['email'] ?? '');
    $telefono = htmlspecialchars($cliente['telefono'] ?? '');
    $marca = htmlspecialchars($cliente['marca'] ?? '');
    $modelo = htmlspecialchars($cliente['modelo'] ?? '');
    $anio = htmlspecialchars($cliente['anio'] ?? '');
    $vehiculo = trim("$marca $modelo $anio");
    $fechaEmision = date('d/m/Y H:i');

    $html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 15px; }
    body {
        font-family: "DejaVu Sans", sans-serif;
        color: #2c3e50;
        font-size: 11px;
        line-height: 1.5;
        background: #ffffff;
    }

    .watermark-bg {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        z-index: -1;
    }

    .header {
        width: 100%;
        border-bottom: 3px solid #d93228;
        padding-bottom: 12px;
        margin-bottom: 8px;
    }
    .header td { vertical-align: middle; }
    .header .logo-cell { width: 85px; text-align: center; }
    .header .logo-cell img { width: 75px; }
    .header .info-cell { text-align: right; }
    .header .company-name {
        font-size: 20px; font-weight: 800;
        color: #0e2951; letter-spacing: 1px;
    }
    .header .tagline {
        font-size: 10px; color: #d93228;
        font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .header .contact-row {
        font-size: 9px; color: #5a6a7e; margin-top: 3px;
    }

    .title-bar {
        background-color: #0e2951;
        color: #ffffff;
        text-align: center;
        padding: 10px 0;
        margin: 10px 0 14px 0;
        font-size: 16px;
        font-weight: bold;
        letter-spacing: 2px;
    }

    .recibo-num {
        text-align: right;
        font-size: 9px;
        color: #888;
        margin-bottom: 10px;
    }

    .section-title {
        font-size: 10px;
        font-weight: bold;
        color: #0e2951;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        border-bottom: 1px solid #0e2951;
        padding-bottom: 4px;
        margin-bottom: 8px;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
    }
    .data-table td {
        padding: 5px 8px;
        border-bottom: 1px solid #e8ecf0;
        font-size: 11px;
    }
    .data-table .label {
        font-weight: bold;
        color: #0e2951;
        width: 120px;
        background-color: #f8f9fc;
    }
    .data-table .value {
        color: #2c3e50;
    }

    .importe-box {
        text-align: center;
        background-color: #0e2951;
        color: #ffffff;
        padding: 16px;
        margin: 14px 0;
        border-radius: 4px;
    }
    .importe-box .label-importe {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 2px;
        opacity: 0.85;
    }
    .importe-box .valor {
        font-size: 32px;
        font-weight: 800;
        margin: 4px 0 2px 0;
        letter-spacing: 1px;
    }
    .importe-box .divider-dot {
        width: 40px; height: 2px; background-color: #d93228;
        margin: 6px auto;
    }

    .importante {
        margin: 16px 0;
        border: 1.5px solid #d93228;
        padding: 12px 16px;
        background-color: #fef6f5;
    }
    .importante .title-imp {
        font-size: 12px;
        font-weight: bold;
        color: #d93228;
        text-align: center;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
    .importante ol {
        margin: 4px 0;
        padding-left: 18px;
        font-size: 9.5px;
        color: #4a5a6e;
    }
    .importante li {
        margin-bottom: 5px;
        text-align: justify;
    }

    .footer {
        text-align: center;
        font-size: 8.5px;
        color: #999;
        margin-top: 18px;
        padding-top: 8px;
        border-top: 1px solid #ddd;
    }
    .footer strong { color: #0e2951; }
    .footer .social { color: #d93228; }

    .badge-pagado {
        text-align: center;
        margin: 6px 0;
        font-size: 9px;
        color: #2e7d32;
        font-weight: bold;
    }
</style>
</head>
<body>';

    if ($bannerBase64) {
        $html .= '<div class="watermark-bg"><img src="data:image/png;base64,' . $bannerBase64 . '" style="width:100%;opacity:0.04" alt=""></div>';
    }

    $html .= '
    <table class="header">
        <tr>
            <td class="logo-cell">';
    if ($logoBase64) {
        $html .= '<img src="data:image/jpeg;base64,' . $logoBase64 . '" alt="Akina Check">';
    }
    $html .= '
            </td>
            <td class="info-cell">
                <div class="company-name">AKINA CHECK AUTO</div>
                <div class="tagline">Verificaci&oacute;n Automotriz Precompra</div>
                <div class="contact-row">info@akinacheck.com.ar &nbsp;|&nbsp; +54 9 3416 11-8718</div>                
            </td>
        </tr>
    </table>

    <div class="recibo-num">Comprobante N&deg; ' . str_pad($cliente_id, 4, '0', STR_PAD_LEFT) . ' &nbsp;|&nbsp; Emitido: ' . $fechaEmision . '</div>

    <div class="title-bar">COMPROBANTE DE PAGO</div>

    <div class="section-title">Datos del Cliente</div>
    <table class="data-table">
        <tr><td class="label">Nombre y Apellido</td><td class="value">' . $nombreCliente . '</td></tr>
        <tr><td class="label">Email</td><td class="value">' . $emailCli . '</td></tr>
        <tr><td class="label">Tel&eacute;fono</td><td class="value">' . $telefono . '</td></tr>
        <tr><td class="label">Localidad</td><td class="value">' . $localidad . '</td></tr>
        <tr><td class="label">Veh&iacute;culo</td><td class="value">' . ($vehiculo ?: '-') . '</td></tr>
    </table>

    <div class="section-title">Detalle del Pago</div>
    <table class="data-table">
        <tr><td class="label">Fecha de Pago</td><td class="value">' . $fechaPago . ' a las ' . $horaPago . '</td></tr>
        <tr><td class="label">Servicio</td><td class="value">Verificaci&oacute;n Automotriz Precompra</td></tr>
    </table>

    <div class="importe-box">
        <div class="label-importe">Importe Abonado</div>
        <div class="divider-dot"></div>
        <div class="valor">$ ' . $importe . '</div>
        <div style="font-size:9px;opacity:0.7;margin-top:2px;letter-spacing:1px;text-transform:uppercase;">Pesos Argentinos</div>
    </div>

    <div class="badge-pagado">✓ PAGO CONFIRMADO</div>

    <div class="importante">
        <div class="title-imp">Importante</div>
        <ol>
            <li>No se realizar&aacute; el reintegro del importe abonado si la cancelaci&oacute;n del servicio se solicita dentro de las 12 horas posteriores a la contrataci&oacute;n del mismo.</li>
            <li>El servicio no podr&aacute; ejecutarse si el comprador y el vendedor del veh&iacute;culo no acuerdan los t&eacute;rminos y condiciones estipulados en el presente contrato.</li>
            <li>Akina Check act&uacute;a como intermediario en la verificaci&oacute;n precompra, no responsabiliz&aacute;ndose por vicios ocultos no detectables mediante una inspecci&oacute;n visual y mec&aacute;nica est&aacute;ndar.</li>
        </ol>
    </div>

    <div class="footer">
        <strong>Akina Check</strong> &mdash; Verificaci&oacute;n Precompra del Automotor<br>
        <span class="social">info@akinacheck.com.ar</span> &nbsp;|&nbsp; +54 9 3416 11-8718<br>
        Corrientes 2025, Rosario, Santa Fe<br>
        <span style="font-size:7.5px;color:#bbb;">Este comprobante es v&aacute;lido &uacute;nicamente para el cliente y veh&iacute;culo aqu&iacute; detallados. Conservar para cualquier reclamo.</span>
    </div>
</body>
</html>';

    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $uploadDir = __DIR__ . '/../uploads/comprobantes/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = 'comprobante_' . $cliente_id . '_' . date('Ymd_His') . '.pdf';
    $filepath = $uploadDir . $filename;
    file_put_contents($filepath, $dompdf->output());

    $relativePath = 'uploads/comprobantes/' . $filename;
    $stmt = $pdo->prepare("UPDATE clientes SET comprobante_path = ? WHERE id = ?");
    $stmt->execute([$relativePath, $cliente_id]);

    echo json_encode(['success' => true, 'path' => $relativePath]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
