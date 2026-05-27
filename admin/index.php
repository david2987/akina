<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';
$pdo = initDB();

$params = [];
$stmt = $pdo->query("SELECT clave, valor FROM parametros");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $params[$row['clave']] = $row['valor'];
}

$where = [];
$paramsQuery = [];

if (!empty($_GET['filter_fecha'])) {
    $where[] = "fecha = ?";
    $paramsQuery[] = $_GET['filter_fecha'];
}
if (!empty($_GET['filter_nombre'])) {
    $where[] = "nombre_apellido LIKE ?";
    $paramsQuery[] = '%' . $_GET['filter_nombre'] . '%';
}
if (!empty($_GET['filter_telefono'])) {
    $where[] = "telefono LIKE ?";
    $paramsQuery[] = '%' . $_GET['filter_telefono'] . '%';
}
if (!empty($_GET['filter_email'])) {
    $where[] = "email LIKE ?";
    $paramsQuery[] = '%' . $_GET['filter_email'] . '%';
}
if (!empty($_GET['filter_modelo'])) {
    $where[] = "modelo LIKE ?";
    $paramsQuery[] = '%' . $_GET['filter_modelo'] . '%';
}
if (!empty($_GET['filter_marca'])) {
    $where[] = "marca LIKE ?";
    $paramsQuery[] = '%' . $_GET['filter_marca'] . '%';
}
if (!empty($_GET['filter_localidad'])) {
    $where[] = "localidad LIKE ?";
    $paramsQuery[] = '%' . $_GET['filter_localidad'] . '%';
}

$whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM clientes $whereClause");
$countStmt->execute($paramsQuery);
$total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($total / $perPage);

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'fecha';
$order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';
$allowedSorts = ['fecha', 'nombre_apellido', 'telefono', 'email', 'marca', 'modelo', 'localidad'];
if (!in_array($sort, $allowedSorts)) $sort = 'fecha';

$stmt = $pdo->prepare("SELECT * FROM clientes $whereClause ORDER BY $sort $order LIMIT $perPage OFFSET $offset");
$stmt->execute($paramsQuery);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akina - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f5f6fa; }
        .sidebar { background: #0e2951; min-height: 100vh; position: fixed; width: 250px; padding: 20px; }
        .sidebar-brand { color: white; font-size: 24px; font-weight: 800; text-align: center; padding: 20px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar a { color: rgba(255,255,255,0.7); text-decoration: none; display: block; padding: 12px 16px; border-radius: 8px; margin-bottom: 5px; transition: all 0.3s; }
        .sidebar a:hover, .sidebar a.active { color: white; background: rgba(255,255,255,0.1); }
        .main-content { margin-left: 250px; padding: 30px; }
        .top-bar { background: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .btn-primary { background: #0e2951; border: none; }
        .btn-primary:hover { background: #1a3a5c; }
        .table-header-sort { cursor: pointer; }
        .table-header-sort:hover { color: #0e2951; }
        .badge-activo { background: #28a745; }
        .badge-inactivo { background: #dc3545; }
        .pagination { margin-top: 20px; }
        .filter-section { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .btn-excel { background: #217346; color: white; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">AKINA</div>
        <a href="index.php" class="active">Clientes</a>
        <a href="parametros.php">Parámetros del Sistema</a>
        <a href="logout.php">Cerrar Sesión</a>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h4 class="mb-0">Gestión de Clientes</h4>
            <div>
                <span class="me-3">Usuario: <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong></span>
                <a href="logout.php" class="btn btn-outline-secondary btn-sm">Cerrar Sesión</a>
            </div>
        </div>
        
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="filter_fecha" class="form-control" value="<?= htmlspecialchars($_GET['filter_fecha'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Nombre/Apellido</label>
                    <input type="text" name="filter_nombre" class="form-control" placeholder="Buscar..." value="<?= htmlspecialchars($_GET['filter_nombre'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="filter_telefono" class="form-control" placeholder="Buscar..." value="<?= htmlspecialchars($_GET['filter_telefono'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Email</label>
                    <input type="text" name="filter_email" class="form-control" placeholder="Buscar..." value="<?= htmlspecialchars($_GET['filter_email'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Modelo</label>
                    <input type="text" name="filter_modelo" class="form-control" placeholder="Buscar..." value="<?= htmlspecialchars($_GET['filter_modelo'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Marca</label>
                    <input type="text" name="filter_marca" class="form-control" placeholder="Buscar..." value="<?= htmlspecialchars($_GET['filter_marca'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Localidad</label>
                    <input type="text" name="filter_localidad" class="form-control" placeholder="Buscar..." value="<?= htmlspecialchars($_GET['filter_localidad'] ?? '') ?>">
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="index.php" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title">Listado de Clientes (<?= $total ?>)</h5>
                    <div>
                        <button class="btn btn-excel btn-sm" onclick="exportExcel()">Exportar a Excel</button>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#nuevoClienteModal">+ Nuevo Cliente</button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="clientesTable">
                        <thead>
                            <tr>
                                <th class="table-header-sort" onclick="sortTable('fecha')">Fecha ↕</th>
                                <th class="table-header-sort" onclick="sortTable('nombre_apellido')">Nombre/Apellido ↕</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th class="table-header-sort" onclick="sortTable('modelo')">Modelo ↕</th>
                                <th class="table-header-sort" onclick="sortTable('marca')">Marca ↕</th>
                                <th class="table-header-sort" onclick="sortTable('localidad')">Localidad ↕</th>
                                <th>PDF</th>
                                <th>Enlace</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($cliente['fecha'])) ?></td>
                                <td><?= htmlspecialchars($cliente['nombre_apellido']) ?></td>
                                <td><?= htmlspecialchars($cliente['telefono']) ?></td>
                                <td><?= htmlspecialchars($cliente['email']) ?></td>
                                <td><?= htmlspecialchars($cliente['modelo'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($cliente['marca'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($cliente['localidad'] ?? '-') ?></td>
                                <td>
                                    <?php if ($cliente['pdf_path']): ?>
                                        <span class="badge bg-success">Adjunto</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Sin PDF</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $linkStmt = $pdo->prepare("SELECT token, expires_at, viewed_at FROM enlaces WHERE cliente_id = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
                                    $linkStmt->execute([$cliente['id']]);
                                    $link = $linkStmt->fetch(PDO::FETCH_ASSOC);
                                    if ($link): ?>
                                        <span class="badge bg-info">Activo</span>
                                        <button class="btn btn-sm btn-outline-success btn-sm d-block mt-1" onclick="copiarEnlace(<?= $cliente['id'] ?>)">📋 Copiar</button>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Sin vínculo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editarCliente(<?= $cliente['id'] ?>)">Editar</button>
                                    <?php if ($cliente['pdf_path']): ?>
                                    <button class="btn btn-sm btn-outline-success" onclick="generarEnlace(<?= $cliente['id'] ?>)">🔗 Enlace</button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarCliente(<?= $cliente['id'] ?>)">Eliminar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal Nuevo/Editar Cliente -->
    <div class="modal fade" id="nuevoClienteModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuevo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="clienteForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="clienteId" name="id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre y Apellido *</label>
                                <input type="text" name="nombre_apellido" id="nombre_apellido" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono *</label>
                                <input type="text" name="telefono" id="telefono" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha *</label>
                                <input type="date" name="fecha" id="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Marca</label>
                                <input type="text" name="marca" id="marca" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Modelo</label>
                                <input type="text" name="modelo" id="modelo" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Localidad</label>
                            <input type="text" name="localidad" id="localidad" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adjuntar PDF (solo admin)</label>
                            <input type="file" name="pdf" id="pdf" class="form-control" accept=".pdf">
                            <div id="pdfActual"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Generar Enlace -->
    <div class="modal fade" id="generarEnlaceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generar Enlace</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>El enlace permitirá al cliente visualizar el PDF durante el tiempo configurado en parámetros del sistema.</p>
                    <p><strong>Tiempo de vigencia:</strong> <?= htmlspecialchars($params['tiempo_vigencia_dias'] ?? 30) ?> Días</p>
                    <p><strong>Tiempo de visualización:</strong> <?= htmlspecialchars($params['tiempo_view_dias'] ?? 7) ?> Días</p>
                    <div id="enlaceGenerado" class="alert alert-info d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="confirmarGenerarEnlace()">Generar Enlace</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let clienteIdActual = 0;
        
        function sortTable(column) {
            const url = new URL(window.location.href);
            const currentSort = url.searchParams.get('sort');
            const currentOrder = url.searchParams.get('order');
            
            if (currentSort === column) {
                url.searchParams.set('order', currentOrder === 'asc' ? 'desc' : 'asc');
            } else {
                url.searchParams.set('sort', column);
                url.searchParams.set('order', 'desc');
            }
            window.location.href = url.toString();
        }
        
        function editarCliente(id) {
            fetch('api/cliente.php?id=' + id)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('clienteId').value = data.id;
                    document.getElementById('nombre_apellido').value = data.nombre_apellido || '';
                    document.getElementById('email').value = data.email || '';
                    document.getElementById('telefono').value = data.telefono || '';
                    document.getElementById('fecha').value = data.fecha || '';
                    document.getElementById('marca').value = data.marca || '';
                    document.getElementById('modelo').value = data.modelo || '';
                    document.getElementById('localidad').value = data.localidad || '';
                    
                    if (data.pdf_path) {
                        document.getElementById('pdfActual').innerHTML = '<small class="text-success">PDF actual adjuntado</small>';
                    } else {
                        document.getElementById('pdfActual').innerHTML = '';
                    }
                    
                    document.getElementById('modalTitle').textContent = 'Editar Cliente';
                    new bootstrap.Modal(document.getElementById('nuevoClienteModal')).show();
                });
        }
        
        document.getElementById('clienteForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api/cliente.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error al guardar');
                }
            });
        });
        
        function eliminarCliente(id) {
            if (confirm('¿Está seguro de eliminar este cliente?')) {
                fetch('api/cliente.php?id=' + id, { method: 'DELETE' })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) location.reload();
                        else alert(data.message);
                    });
            }
        }
        
        function generarEnlace(id) {
            clienteIdActual = id;
            document.getElementById('enlaceGenerado').classList.add('d-none');
            new bootstrap.Modal(document.getElementById('generarEnlaceModal')).show();
        }
        
        function confirmarGenerarEnlace() {
            fetch('api/enlace.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ cliente_id: clienteIdActual })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const baseUrl = window.location.origin + '/public/ver-informe.php';
                    document.getElementById('enlaceGenerado').innerHTML = 
                        '<strong>Enlace generado:</strong><br><a href="' + baseUrl + '?token=' + data.token + '" target="_blank"> Enlace de Informe </a>';
                    document.getElementById('enlaceGenerado').classList.remove('d-none');
                } else {
                    alert(data.message || 'Error al generar enlace');
                }
            });
        }
        
        function exportExcel() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            window.location.href = 'api/clientes.php?' + params.toString();
        }
        
        function copiarEnlace(id) {
            fetch('api/enlace.php?get_token=' + id)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.token) {
                        const baseUrl = window.location.origin + '/public/ver-informe.php';
                        const enlace = baseUrl + '?token=' + data.token;
                        navigator.clipboard.writeText(enlace).then(() => {
                            alert('Enlace copiado al portapapeles');
                        });
                    } else {
                        alert('No hay enlace activo para este cliente');
                    }
                });
        }
    </script>
</body>
</html>