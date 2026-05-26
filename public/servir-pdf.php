<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../admin/db.php';
$pdo = initDB();

$token = $_GET['token'] ?? '';
if (empty($token)) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$stmt = $pdo->prepare("
    SELECT e.*, c.pdf_path 
    FROM enlaces e 
    JOIN clientes c ON e.cliente_id = c.id 
    WHERE e.token = ?
");
$stmt->execute([$token]);
$enlace = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$enlace || !$enlace['pdf_path']) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

if (strtotime($enlace['expires_at']) <= time()) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$pdfFile = __DIR__ . '/../admin/' . $enlace['pdf_path'];
if (!file_exists($pdfFile)) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($pdfFile));
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

readfile($pdfFile);
exit;
?>