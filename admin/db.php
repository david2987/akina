<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'akina_db_final');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB() {
    try {
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
    
    $migrations = [
        "ALTER TABLE clientes ADD COLUMN anio VARCHAR(4) DEFAULT '' AFTER modelo",
        "ALTER TABLE clientes ADD COLUMN fecha_pago DATE NULL AFTER pdf_path",
        "ALTER TABLE clientes ADD COLUMN hora_pago TIME NULL AFTER fecha_pago",
        "ALTER TABLE clientes ADD COLUMN email_pago VARCHAR(255) NULL AFTER hora_pago",
        "ALTER TABLE clientes ADD COLUMN importe DECIMAL(10,2) NULL AFTER email_pago",
        "ALTER TABLE clientes ADD COLUMN comprobante_path VARCHAR(500) NULL AFTER importe",
    ];
    foreach ($migrations as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $e) {}
    }
    
    return $pdo;
}

function initDB() {
    $pdo = new PDO("mysql:host=".DB_HOST.";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS ".DB_NAME);
    $pdo->exec("USE ".DB_NAME);
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS parametros (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(50) UNIQUE NOT NULL,
        valor VARCHAR(255) NOT NULL
    )");
    
    $pdo->exec("INSERT IGNORE INTO parametros (clave, valor) VALUES 
        ('tiempo_vigencia_dias', '30'),
        ('tiempo_view_dias', '7')");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS clientes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_apellido VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        telefono VARCHAR(50) NOT NULL,
        marca VARCHAR(100),
        modelo VARCHAR(100),
        anio VARCHAR(4) DEFAULT '',
        localidad VARCHAR(150),
        fecha DATE NOT NULL,
        pdf_path VARCHAR(500),
        fecha_pago DATE NULL,
        hora_pago TIME NULL,
        email_pago VARCHAR(255) NULL,
        importe DECIMAL(10,2) NULL,
        comprobante_path VARCHAR(500) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS enlaces (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        token VARCHAR(64) UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        viewed_at DATETIME NULL,
        FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
    )");
    
    $nuevasColumnas = [
        "ALTER TABLE clientes ADD COLUMN anio VARCHAR(4) DEFAULT '' AFTER modelo",
        "ALTER TABLE clientes ADD COLUMN fecha_pago DATE NULL AFTER pdf_path",
        "ALTER TABLE clientes ADD COLUMN hora_pago TIME NULL AFTER fecha_pago",
        "ALTER TABLE clientes ADD COLUMN email_pago VARCHAR(255) NULL AFTER hora_pago",
        "ALTER TABLE clientes ADD COLUMN importe DECIMAL(10,2) NULL AFTER email_pago",
        "ALTER TABLE clientes ADD COLUMN comprobante_path VARCHAR(500) NULL AFTER importe",
    ];
    foreach ($nuevasColumnas as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $e) {}
    }
    
    return $pdo;
}
?>