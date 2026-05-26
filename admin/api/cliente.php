<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../db.php';
$pdo = initDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($cliente);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nombre_apellido = $_POST['nombre_apellido'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $marca = $_POST['marca'] ?? '';
    $modelo = $_POST['modelo'] ?? '';
    $localidad = $_POST['localidad'] ?? '';
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    
    $pdfPath = null;
    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/pdfs/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'pdf') {
            echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos PDF']);
            exit;
        }
        
        $filename = uniqid('pdf_') . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['pdf']['tmp_name'], $targetPath)) {
            $pdfPath = 'uploads/pdfs/' . $filename;
        }
    }
    
    if ($id) {
        $sql = "UPDATE clientes SET nombre_apellido=?, email=?, telefono=?, marca=?, modelo=?, localidad=?, fecha=?";
        $params = [$nombre_apellido, $email, $telefono, $marca, $modelo, $localidad, $fecha];
        
        if ($pdfPath) {
            $sql .= ", pdf_path=?";
            $params[] = $pdfPath;
        }
        
        $sql .= " WHERE id=?";
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $sql = "INSERT INTO clientes (nombre_apellido, email, telefono, marca, modelo, localidad, fecha, pdf_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre_apellido, $email, $telefono, $marca, $modelo, $localidad, $fecha, $pdfPath]);
    }
    
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método no permitido']);
?>