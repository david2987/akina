<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';
$pdo = initDB();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tiempo_vigencia = (int)($_POST['tiempo_vigencia_minutos'] ?? 60);
    $tiempo_view = (int)($_POST['tiempo_view_pdf_minutos'] ?? 30);
    
    $stmt = $pdo->prepare("UPDATE parametros SET valor = ? WHERE clave = 'tiempo_vigencia_minutos'");
    $stmt->execute([$tiempo_vigencia]);
    
    $stmt = $pdo->prepare("UPDATE parametros SET valor = ? WHERE clave = 'tiempo_view_pdf_minutos'");
    $stmt->execute([$tiempo_view]);
    
    $message = 'Parámetros actualizados correctamente';
}

$params = [];
$stmt = $pdo->query("SELECT clave, valor FROM parametros");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $params[$row['clave']] = $row['valor'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akina - Parámetros del Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f5f6fa; }
        .sidebar { background: #0e2951; min-height: 100vh; position: fixed; width: 250px; padding: 20px; }
        .sidebar-brand { color: white; font-size: 24px; font-weight: 800; text-align: center; padding: 20px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar a { color: rgba(255,255,255,0.7); text-decoration: none; display: block; padding: 12px 16px; border-radius: 8px; margin-bottom: 5px; transition: all 0.3s; }
        .sidebar a:hover, .sidebar a.active { color: white; background: rgba(255,255,255,0.1); }
        .main-content { margin-left: 250px; padding: 30px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .btn-primary { background: #0e2951; border: none; }
        .btn-primary:hover { background: #1a3a5c; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">AKINA</div>
        <a href="index.php">Clientes</a>
        <a href="parametros.php" class="active">Parámetros del Sistema</a>
        <a href="logout.php">Cerrar Sesión</a>
    </div>
    
    <div class="main-content">
        <h4 class="mb-4">Parámetros del Sistema</h4>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">Configuración de Tiempos</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Tiempo de Vigencia del Enlace (minutos)</label>
                        <input type="number" name="tiempo_vigencia_minutos" class="form-control" value="<?= htmlspecialchars($params['tiempo_vigencia_minutos'] ?? 60) ?>" min="1">
                        <small class="text-muted">Tiempo desde que se genera el enlace hasta que puede ser visualesdo</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tiempo de Visualización del PDF (minutos)</label>
                        <input type="number" name="tiempo_view_pdf_minutos" class="form-control" value="<?= htmlspecialchars($params['tiempo_view_pdf_minutos'] ?? 30) ?>" min="1">
                        <small class="text-muted">Tiempo máximo que el usuario podrá visualizar el PDF una vez accede al enlace</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Información</h5>
                <p>Estos parámetros controlan la seguridad de los enlaces generados para los clientes:</p>
                <ul>
                    <li><strong>Tiempo de vigencia:</strong> Después de generar el enlace, el cliente debe esperar este tiempo para poder acceder al PDF.</li>
                    <li><strong>Tiempo de visualización:</strong> Una vez que el cliente accede al enlace, podrá ver el PDF durante este período.</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>