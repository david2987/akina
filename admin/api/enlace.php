<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../db.php';
$pdo = initDB();

if (isset($_GET['get_token'])) {
    $cliente_id = (int)$_GET['get_token'];
    $stmt = $pdo->prepare("SELECT token FROM enlaces WHERE cliente_id = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$cliente_id]);
    $link = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($link) {
        echo json_encode(['success' => true, 'token' => $link['token']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No hay enlace activo']);
    }
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cliente_id = $input['cliente_id'] ?? 0;

$stmt = $pdo->prepare("SELECT pdf_path FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente || !$cliente['pdf_path']) {
    echo json_encode(['success' => false, 'message' => 'El cliente no tiene PDF adjunto']);
    exit;
}

$params = [];
$stmt = $pdo->query("SELECT clave, valor FROM parametros");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $params[$row['clave']] = $row['valor'];
}

$token = bin2hex(random_bytes(32));
$vigenciaMinutos = isset($params['tiempo_vigencia_minutos']) ? (int)$params['tiempo_vigencia_minutos'] : 60;
$viewMinutos = isset($params['tiempo_view_pdf_minutos']) ? (int)$params['tiempo_view_pdf_minutos'] : 30;

$totalMinutos = $vigenciaMinutos + $viewMinutos;
$expiresAt = date('Y-m-d H:i:s', time() + ($totalMinutos * 60));

$stmt = $pdo->prepare("INSERT INTO enlaces (cliente_id, token, expires_at) VALUES (?, ?, ?)");
$stmt->execute([$cliente_id, $token, $expiresAt]);

echo json_encode(['success' => true, 'token' => $token, 'expires_at' => $expiresAt]);
?>