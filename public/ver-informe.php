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
        .viewer { position: fixed; top: 44px; left: 0; right: 0; bottom: 64px; overflow: auto; background: #525659; -webkit-overflow-scrolling: touch; }
        .page { display: flex; justify-content: center; padding: 10px; overflow: visible; }
        .page canvas { box-shadow: 0 2px 10px rgba(0,0,0,0.5); background: white; display: block; }
        .controls { display: flex; justify-content: center; gap: 8px; padding: 10px; background: #222; position: fixed; bottom: 0; left: 0; right: 0; z-index: 100; flex-wrap: wrap; height: 64px; align-items: center; }
        .controls button { background: #555; color: white; border: none; padding: 10px 18px; border-radius: 8px; font-size: 16px; cursor: pointer; touch-action: manipulation; -webkit-tap-highlight-color: transparent; min-width: 44px; min-height: 44px; -webkit-user-select: none; user-select: none; -webkit-touch-callout: none; }
        .controls button:disabled { opacity: 0.5; }
        .controls button:active { background: #777; }
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
        <button id="prevBtn">◀</button>
        <span class="page-info" id="pageInfo">- / -</span>
        <button id="nextBtn">▶</button>
        <button id="zoomOutBtn">−</button>
        <button id="zoomInBtn">+</button>
    </div>
    
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.worker.min.js';
        
        let pdfDoc = null;
        let pageNum = 1;
        let baseScale = 1.0;
        let zoomLevel = 1.0;
        const maxZoom = 3.0;
        const minZoom = 0.5;
        let isRendering = false;
        let pageViewports = {};
        
        function getEffectiveScale() {
            return baseScale * zoomLevel;
        }
        
        function renderAllPages() {
            var container = document.getElementById('pages');
            container.innerHTML = '';
            
            // First pass: get natural viewport to calculate baseScale
            pdfDoc.getPage(1).then(function(firstPage) {
                var naturalViewport = firstPage.getViewport({scale: 1.0});
                var viewerWidth = document.getElementById('viewer').clientWidth - 20;
                baseScale = viewerWidth / naturalViewport.width;
                
                // Now render all pages with the correct scale
                for (var i = 1; i <= pdfDoc.numPages; i++) {
                    var div = document.createElement('div');
                    div.className = 'page';
                    div.id = 'page-' + i;
                    
                    var canvas = document.createElement('canvas');
                    canvas.id = 'canvas-' + i;
                    div.appendChild(canvas);
                    container.appendChild(div);
                    
                    renderPage(i);
                }
                
                updatePageInfo();
                document.getElementById('loading').style.display = 'none';
                
                document.getElementById('prevBtn').disabled = true;
                document.getElementById('nextBtn').disabled = pdfDoc.numPages <= 1;
            });
        }
        
        function updatePageInfo() {
            document.getElementById('pageInfo').textContent = pageNum + ' / ' + (pdfDoc ? pdfDoc.numPages : '-') + ' (' + Math.round(zoomLevel * 100) + '%)';
        }
        
        function renderPage(num) {
            pdfDoc.getPage(num).then(function(page) {
                var canvas = document.getElementById('canvas-' + num);
                if (!canvas) return;
                var ctx = canvas.getContext('2d');
                var effectiveScale = getEffectiveScale();
                var viewport = page.getViewport({scale: effectiveScale});
                
                // Set internal canvas resolution
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                
                // Set CSS display size to match exactly (this is key for mobile)
                canvas.style.width = viewport.width + 'px';
                canvas.style.height = viewport.height + 'px';
                
                page.render({canvasContext: ctx, viewport: viewport});
            });
        }
        
        function changePage(delta) {
            if (!pdfDoc) return;
            var newPage = pageNum + delta;
            if (newPage >= 1 && newPage <= pdfDoc.numPages) {
                pageNum = newPage;
                updatePageInfo();
                document.getElementById('prevBtn').disabled = pageNum <= 1;
                document.getElementById('nextBtn').disabled = pageNum >= pdfDoc.numPages;
                
                document.getElementById('page-' + pageNum).scrollIntoView({behavior: 'smooth'});
            }
        }
        
        function doZoom(newZoomLevel) {
            if (isRendering || !pdfDoc) return;
            
            newZoomLevel = Math.round(newZoomLevel * 10) / 10;
            if (newZoomLevel < minZoom) newZoomLevel = minZoom;
            if (newZoomLevel > maxZoom) newZoomLevel = maxZoom;
            if (newZoomLevel === zoomLevel) return;
            
            isRendering = true;
            zoomLevel = newZoomLevel;
            updatePageInfo();
            
            // Re-render ALL pages with new zoom
            var promises = [];
            for (var i = 1; i <= pdfDoc.numPages; i++) {
                (function(pageNum) {
                    promises.push(
                        pdfDoc.getPage(pageNum).then(function(page) {
                            var canvas = document.getElementById('canvas-' + pageNum);
                            if (!canvas) return;
                            var ctx = canvas.getContext('2d');
                            var effectiveScale = getEffectiveScale();
                            var viewport = page.getViewport({scale: effectiveScale});
                            
                            canvas.width = viewport.width;
                            canvas.height = viewport.height;
                            canvas.style.width = viewport.width + 'px';
                            canvas.style.height = viewport.height + 'px';
                            
                            return page.render({canvasContext: ctx, viewport: viewport}).promise;
                        })
                    );
                })(i);
            }
            
            Promise.all(promises).then(function() {
                isRendering = false;
            }).catch(function() {
                isRendering = false;
            });
        }
        
        function zoomIn() {
            doZoom(zoomLevel + 0.25);
        }
        
        function zoomOut() {
            doZoom(zoomLevel - 0.25);
        }
        
        // Reliable mobile button handler
        function addMobileButton(btnId, handler) {
            var btn = document.getElementById(btnId);
            if (!btn) return;
            var touchFired = false;
            
            btn.addEventListener('touchstart', function(e) {
                e.preventDefault();
            }, { passive: false });
            
            btn.addEventListener('touchend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                touchFired = true;
                handler();
                // Reset after a short delay
                setTimeout(function() { touchFired = false; }, 400);
            }, { passive: false });
            
            btn.addEventListener('click', function(e) {
                if (touchFired) return;
                e.preventDefault();
                handler();
            });
        }
        
        addMobileButton('zoomInBtn', zoomIn);
        addMobileButton('zoomOutBtn', zoomOut);
        addMobileButton('prevBtn', function() { changePage(-1); });
        addMobileButton('nextBtn', function() { changePage(1); });
        
        document.getElementById('prevBtn').disabled = true;
        document.getElementById('nextBtn').disabled = true;
        
        fetch('servir-pdf.php?token=<?= htmlspecialchars($token) ?>')
            .then(function(response) { return response.arrayBuffer(); })
            .then(function(data) {
                pdfjsLib.getDocument({data: data}).promise.then(function(pdf) {
                    pdfDoc = pdf;
                    renderAllPages();
                }).catch(function(err) {
                    document.getElementById('loading').innerHTML = '<div class="error">Error al cargar PDF</div>';
                });
            })
            .catch(function(err) {
                document.getElementById('loading').innerHTML = '<div class="error">Error al cargar</div>';
            });
        
        var fin = Date.now() + (<?= $remainingSeconds ?> * 1000);
        
        setInterval(function() {
            if (Math.ceil((fin - Date.now()) / 1000) <= 0) {
                document.getElementById('expiredOverlay').classList.add('show');
            }
        }, 10000);
        
        document.addEventListener('contextmenu', function(e) { e.preventDefault(); });
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'p')) e.preventDefault();
            if (e.key === 'ArrowLeft') changePage(-1);
            if (e.key === 'ArrowRight') changePage(1);
        });
    </script>
</body>
</html>