<?php
/**
 * conexion.php — Punto único de conexión PDO para toda la app
 * Uso:
 *   require_once __DIR__ . '/../config/conexion.php';
 *   $pdo = getPDO();
 */

// Lee de variables de entorno si existen; si no, usa valores por defecto
$DB_HOST   = getenv('DB_HOST') ?: 'localhost';
$DB_NAME   = getenv('DB_NAME') ?: 'pidecredito';
$DB_USER   = getenv('DB_USER') ?: 'root';
$DB_PASS   = getenv('DB_PASS') ?: '12345678';
$DB_CHARSET= 'utf8mb4';

$PDO_OPTIONS = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

/** Devuelve un PDO singleton */
function getPDO(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $host    = $GLOBALS['DB_HOST'];
  $db      = $GLOBALS['DB_NAME'];
  $user    = $GLOBALS['DB_USER'];
  $pass    = $GLOBALS['DB_PASS'];
  $charset = $GLOBALS['DB_CHARSET'];
  $opts    = $GLOBALS['PDO_OPTIONS'];

  $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
  $pdo = new PDO($dsn, $user, $pass, $opts);
  return $pdo;
}
