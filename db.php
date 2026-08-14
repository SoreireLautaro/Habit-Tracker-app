<?php
/**
 * db.php
 * Conexión a la base de datos MySQL utilizando PDO.
 * Para uso con XAMPP local.
 */

$host = '127.0.0.1';
$db   = 'habit_tracker_db';
$user = 'root'; // Usuario por defecto de XAMPP
$pass = '';     // Contraseña por defecto vacía en XAMPP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanzar excepciones en errores
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devolver arrays asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usar prepared statements reales
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Si la base de datos no existe, mostramos un error amigable (en la vida real no se debe mostrar el detalle).
    die(json_encode(['error' => 'Error de conexión a la base de datos. Verifica que XAMPP esté corriendo y hayas importado schema.sql. Detalle: ' . $e->getMessage()]));
}
?>
