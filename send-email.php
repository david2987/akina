<?php
header('Content-Type: application/json');

// Configuración de errores para desarrollo (comentar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Validar y sanitizar datos del formulario
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
$mensaje = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';

// Validaciones básicas
$errors = [];

if (empty($nombre)) {
    $errors[] = 'El nombre es requerido';
}

if (empty($telefono)) {
    $errors[] = 'El teléfono es requerido';
}

if (empty($mensaje)) {
    $errors[] = 'El mensaje es requerido';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Sanitizar datos
$nombre = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
$telefono = htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8');
$mensaje = htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');

// Importar PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

try {
    $mail = new PHPMailer(true);
    
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'a0061001.ferozo.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'info@akinacheck.com';
    $mail->Password = 'LqV2@2nG';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';
    
    // Configuración del remitente y destinatario
    $mail->setFrom('info@akinacheck.com', 'Formulario Akina Check');
    $mail->addAddress('akinacheck@gmail.com', 'Akina Check Auto');
    $mail->addReplyTo('info@akinacheck.com.ar', 'Akina Check Auto');
    
    // Contenido del email
    $mail->isHTML(true);
    $mail->Subject = 'Nuevo mensaje de contacto - Akina Check Auto';
    
    // Template HTML elegante para el email
    $mail->Body = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nuevo Mensaje de Contacto</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background-color: #f4f7fa;
                padding: 20px;
            }
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background: linear-gradient(135deg, #0e2951 0%, #1a4d8f 100%);
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            }
            .email-header {
                background: linear-gradient(135deg, #0a1f3d 0%, #0e2951 100%);
                padding: 40px 30px;
                text-align: center;
                border-bottom: 4px solid #ffd700;
            }
            .email-header h1 {
                color: #ffffff;
                font-size: 28px;
                font-weight: 800;
                margin-bottom: 10px;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            .email-header p {
                color: #b8c9e0;
                font-size: 14px;
                font-weight: 300;
            }
            .email-body {
                background-color: #ffffff;
                padding: 40px 30px;
            }
            .info-card {
                background: linear-gradient(135deg, #f8f9fc 0%, #e8eef5 100%);
                border-left: 5px solid #0e2951;
                border-radius: 12px;
                padding: 25px;
                margin-bottom: 25px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            }
            .info-row {
                margin-bottom: 20px;
                padding-bottom: 20px;
                border-bottom: 1px solid #d1dce8;
            }
            .info-row:last-child {
                margin-bottom: 0;
                padding-bottom: 0;
                border-bottom: none;
            }
            .info-label {
                display: inline-block;
                font-weight: 700;
                color: #0e2951;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 8px;
            }
            .info-value {
                color: #2c3e50;
                font-size: 16px;
                line-height: 1.6;
                font-weight: 400;
            }
            .message-box {
                background-color: #ffffff;
                border: 2px solid #e1e8ed;
                border-radius: 10px;
                padding: 20px;
                margin-top: 10px;
                font-size: 15px;
                line-height: 1.7;
                color: #34495e;
            }
            .email-footer {
                background: linear-gradient(135deg, #0a1f3d 0%, #0e2951 100%);
                padding: 30px;
                text-align: center;
            }
            .email-footer p {
                color: #b8c9e0;
                font-size: 13px;
                margin-bottom: 15px;
                line-height: 1.6;
            }
            .footer-divider {
                height: 2px;
                background: linear-gradient(90deg, transparent, #ffd700, transparent);
                margin: 20px 0;
            }
            .contact-info {
                color: #ffffff;
                font-size: 12px;
                margin-top: 15px;
            }
            .contact-info a {
                color: #ffd700;
                text-decoration: none;
                font-weight: 600;
            }
            .icon {
                display: inline-block;
                width: 20px;
                height: 20px;
                margin-right: 8px;
                vertical-align: middle;
            }
            @media only screen and (max-width: 600px) {
                .email-container {
                    border-radius: 0;
                }
                .email-header, .email-body, .email-footer {
                    padding: 25px 20px;
                }
                .email-header h1 {
                    font-size: 24px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>🚗 Nuevo Mensaje de Contacto</h1>
                <p>Formulario de contacto - Akina Check Auto</p>
            </div>
            
            <div class="email-body">
                <div class="info-card">
                    <div class="info-row">
                        <div class="info-label">
                            👤 Nombre Completo
                        </div>
                        <div class="info-value">' . $nombre . '</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">
                            📱 Teléfono
                        </div>
                        <div class="info-value">' . $telefono . '</div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">
                            💬 Mensaje
                        </div>
                        <div class="message-box">' . nl2br($mensaje) . '</div>
                    </div>
                </div>
            </div>
            
            <div class="email-footer">
                <p><strong>Este mensaje fue enviado desde el formulario de contacto de su sitio web.</strong></p>
                <div class="footer-divider"></div>
                <div class="contact-info">
                    <p>
                        📧 <a href="mailto:info@akinacheck.com.ar">info@akinacheck.com.ar</a> | 
                        📞 +54 9 3416 11-8718
                    </p>
                    <p>Corrientes 2025, Rosario, Santa Fe</p>
                    <p style="margin-top: 15px; font-size: 11px; color: #7f8c9a;">
                        © ' . date('Y') . ' Akina Check Auto - Todos los derechos reservados
                    </p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ';
    
    // Versión de texto plano alternativa
    $mail->AltBody = "Nuevo mensaje de contacto\n\n" .
                     "Nombre: $nombre\n" .
                     "Teléfono: $telefono\n" .
                     "Mensaje: $mensaje\n\n" .
                     "---\n" .
                     "Este mensaje fue enviado desde el formulario de contacto de Akina Check Auto";
    
    // Enviar el email
    $mail->send();
    
    echo json_encode([
        'success' => true,
        'message' => '¡Mensaje enviado correctamente! Nos pondremos en contacto contigo pronto.'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al enviar el mensaje. Por favor, intenta nuevamente.',
        'error' => $mail->ErrorInfo
    ]);
}
