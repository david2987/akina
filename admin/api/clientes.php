<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../db.php';
$pdo = initDB();

$where = [];
$params = [];

if (!empty($_GET['filter_fecha'])) {
    $where[] = "fecha = ?";
    $params[] = $_GET['filter_fecha'];
}
if (!empty($_GET['filter_nombre'])) {
    $where[] = "nombre_apellido LIKE ?";
    $params[] = '%' . $_GET['filter_nombre'] . '%';
}
if (!empty($_GET['filter_telefono'])) {
    $where[] = "telefono LIKE ?";
    $params[] = '%' . $_GET['filter_telefono'] . '%';
}
if (!empty($_GET['filter_email'])) {
    $where[] = "email LIKE ?";
    $params[] = '%' . $_GET['filter_email'] . '%';
}
if (!empty($_GET['filter_modelo'])) {
    $where[] = "modelo LIKE ?";
    $params[] = '%' . $_GET['filter_modelo'] . '%';
}
if (!empty($_GET['filter_marca'])) {
    $where[] = "marca LIKE ?";
    $params[] = '%' . $_GET['filter_marca'] . '%';
}
if (!empty($_GET['filter_anio'])) {
    $where[] = "anio LIKE ?";
    $params[] = '%' . $_GET['filter_anio'] . '%';
}
if (!empty($_GET['filter_localidad'])) {
    $where[] = "localidad LIKE ?";
    $params[] = '%' . $_GET['filter_localidad'] . '%';
}

$whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=clientes_akina_' . date('Ymd_His') . '.xls');
    echo "Fecha\tNombre/Apellido\tTeléfono\tEmail\tMarca\tModelo\tAño\tLocalidad\tPDF Adjunto\n";
    
    $stmt = $pdo->prepare("SELECT * FROM clientes $whereClause ORDER BY fecha DESC");
    $stmt->execute($params);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdf = $row['pdf_path'] ? 'Sí' : 'No';
        echo date('d/m/Y', strtotime($row['fecha'])) . "\t";
        echo $row['nombre_apellido'] . "\t";
        echo $row['telefono'] . "\t";
        echo $row['email'] . "\t";
        echo ($row['marca'] ?? '') . "\t";
        echo ($row['modelo'] ?? '') . "\t";
        echo ($row['anio'] ?? '') . "\t";
        echo ($row['localidad'] ?? '') . "\t";
        echo $pdf . "\n";
    }
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM clientes $whereClause ORDER BY fecha DESC");
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $clientes]);
?>