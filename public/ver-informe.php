<?php
$errorMsg = '';

function showError($title, $message) {
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $title . ' - Akina Check</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { font-family: "Inter", sans-serif; background: #f5f6fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin:0; }
            .card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); text-align: center; max-width: 400px; margin:20px; }
            .icon { width: 80px; height: 80px; background: #dc3545; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <h3>' . $title . '</h3>
            <p class="text-muted">' . $message . '</p>
            <a href="https://wa.me/5493416118718" class="btn btn-primary mt-3">Contactar</a>
        </div>
    </body>
    </html>';
    exit;
}

require_once '../admin/db.php';
$pdo = initDB();

$token = $_GET['token'] ?? '';

if (empty($token)) {
    showError('Enlace Inválido', 'El token no fue proporcionado.');
}

$stmt = $pdo->prepare("
    SELECT e.*, c.nombre_apellido, c.pdf_path 
    FROM enlaces e 
    JOIN clientes c ON e.cliente_id = c.id 
    WHERE e.token = ?
");
$stmt->execute([$token]);
$enlace = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$enlace) {
    showError('Enlace No Encontrado', 'El enlace no existe o ya fue utilizado.');
}

$now = time();
$createdAt = strtotime($enlace['created_at']);

$params = [];
$stmt = $pdo->query("SELECT clave, valor FROM parametros");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $params[$row['clave']] = $row['valor'];
}
$vigenciaDias = isset($params['tiempo_vigencia_dias']) ? (int)$params['tiempo_vigencia_dias'] : 30;
$viewDias = isset($params['tiempo_view_dias']) ? (int)$params['tiempo_view_dias'] : 7;

$tiempoMinimo = $createdAt + ($vigenciaDias * 86400);

// if ($now < $tiempoMinimo) {
//     $espera = ceil(($tiempoMinimo - $now) / 60);
//     showError('Enlace en Espera', 'Su enlace estará disponible en aproximadamente ' . $espera . ' minuto(s).');
// }

if ($now > strtotime($enlace['expires_at'])) {
    showError('Enlace Caducado', 'Si necesita un nuevo informe comuníquese con Akina Check.');
}

$remainingSeconds = strtotime($enlace['expires_at']) - $now;

if ($remainingSeconds <= 0) {
    showError('Sesión Expirada', 'Su tiempo de visualización expiró.');
}

if (!$enlace['pdf_path']) {
    showError('PDF No Disponible', 'El informe aún no ha sido cargado.');
}

$pdfFile = __DIR__ . '/../admin/' . $enlace['pdf_path'];
if (!file_exists($pdfFile)) {
    showError('Archivo No Encontrado', 'El informe no está disponible.');
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Informe - Akina Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
    <style>
        * { box-sizing: border-box; -webkit-touch-callout: none; }
        html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; font-family: 'Inter', sans-serif; background: #333; }
        .header { background: #0e2951; color: white; padding: 10px 15px; position: fixed; top: 0; left: 0; right: 0; z-index: 100; display: flex; justify-content: space-between; align-items: center; height: 44px; }
        .header h4 { margin: 0; font-size: 14px; }
        .viewer { position: fixed; top: 44px; left: 0; right: 0; bottom: 0; overflow: auto; background: #525659; -webkit-overflow-scrolling: touch; }
        .page { display: flex; justify-content: center; padding: 10px; }
        .page canvas { max-width: 100%; height: auto; box-shadow: 0 2px 10px rgba(0,0,0,0.5); background: white; }
        .controls { display: flex; justify-content: center; gap: 8px; padding: 10px; background: #222; position: fixed; bottom: 0; left: 0; right: 0; z-index: 100; flex-wrap: wrap; }
        .controls button { background: #555; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 14px; cursor: pointer; }
        .controls button:disabled { opacity: 0.5; }
        .page-info { color: white; padding: 8px; text-align: center; font-size: 12px; }
        .loading { color: white; text-align: center; padding: 40px; font-size: 16px; }
        .error { color: #ff6b6b; text-align: center; padding: 20px; }
        .expired-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.85); z-index: 200; justify-content: center; align-items: center; flex-direction: column; color: white; padding: 30px; }
        .expired-overlay.show { display: flex; }
        .expired-overlay h3 { color: #ffc107; margin-bottom: 15px; font-size: 22px; }
        .expired-overlay .btn { background: #ffc107; color: #000; padding: 10px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h4>Informe de Verificación</h4>
    </div>
    <div class="viewer" id="viewer">
        <div id="loading" class="loading">Cargando informe...</div>
        <div id="pages"></div>
    </div>
    
    <div class="expired-overlay" id="expiredOverlay">
        <h3>Tiempo de visualización agotado</h3>
        <p style="text-align:center;opacity:0.8;">Su tiempo de visualización ha expirado. Si necesita acceder nuevamente, solicite un nuevo enlace.</p>
        <a href="https://wa.me/5493416118718" class="btn">Contactar</a>
    </div>
    
    <div class="controls">
        <button id="prevBtn" onclick="changePage(-1)">◀</button>
        <span class="page-info" id="pageInfo">- / -</span>
        <button id="nextBtn" onclick="changePage(1)">▶</button>
        <button onclick="zoomOut()">−</button>
        <button onclick="zoomIn()">+</button>
    </div>
    
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.worker.min.js';
        
        let pdfDoc = null;
        let pageNum = 1;
        let scale = window.innerWidth < 600 ? 0.8 : 1.2;
        const maxScale = 3;
        const minScale = 0.5;
        
        function renderAllPages() {
            const container = document.getElementById('pages');
            container.innerHTML = '';
            
            for (let i = 1; i <= pdfDoc.numPages; i++) {
                const div = document.createElement('div');
                div.className = 'page';
                div.id = 'page-' + i;
                
                const canvas = document.createElement('canvas');
                canvas.id = 'canvas-' + i;
                div.appendChild(canvas);
                container.appendChild(div);
                
                renderPage(i);
            }
            
            document.getElementById('pageInfo').textContent = pageNum + ' / ' + pdfDoc.numPages;
            document.getElementById('loading').style.display = 'none';
        }
        
        function renderPage(num) {
            pdfDoc.getPage(num).then(function(page) {
                const canvas = document.getElementById('canvas-' + num);
                const ctx = canvas.getContext('2d');
                const viewport = page.getViewport({scale: scale});
                
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                page.render(renderContext);
            });
        }
        
        function changePage(delta) {
            const newPage = pageNum + delta;
            if (newPage >= 1 && newPage <= pdfDoc.numPages) {
                pageNum = newPage;
                document.getElementById('pageInfo').textContent = pageNum + ' / ' + pdfDoc.numPages;
                document.getElementById('prevBtn').disabled = pageNum <= 1;
                document.getElementById('nextBtn').disabled = pageNum >= pdfDoc.numPages;
                
                document.getElementById('page-' + pageNum).scrollIntoView({behavior: 'smooth'});
            }
        }
        
        function zoomIn() {
            if (scale < maxScale) {
                scale += 0.3;
                renderAllPages();
            }
        }
        
        function zoomOut() {
            if (scale > minScale) {
                scale -= 0.3;
                renderAllPages();
            }
        }
        
        document.getElementById('prevBtn').disabled = true;
        document.getElementById('nextBtn').disabled = true;
        
        fetch('servir-pdf.php?token=<?= htmlspecialchars($token) ?>')
            .then(response => response.arrayBuffer())
            .then(data => {
                pdfjsLib.getDocument({data: data}).promise.then(function(pdf) {
                    pdfDoc = pdf;
                    renderAllPages();
                }).catch(err => {
                    document.getElementById('loading').innerHTML = '<div class="error">Error al cargar PDF</div>';
                });
            })
            .catch(err => {
                document.getElementById('loading').innerHTML = '<div class="error">Error al cargar</div>';
            });
        
        let fin = Date.now() + (<?= $remainingSeconds ?> * 1000);
        
        setInterval(function() {
            if (Math.ceil((fin - Date.now()) / 1000) <= 0) {
                document.getElementById('expiredOverlay').classList.add('show');
            }
        }, 10000);
        
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('keydown', e => {
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'p')) e.preventDefault();
            if (e.key === 'ArrowLeft') changePage(-1);
            if (e.key === 'ArrowRight') changePage(1);
        });
    </script>
</body>
</html>