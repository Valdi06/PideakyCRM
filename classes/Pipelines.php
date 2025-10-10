<?php
// classes/Pipelines.php
// Clase de acceso a datos para la tabla `pipelines`

require_once '../conexion/conexion.php';

class Pipelines {
  private $pdo;

  public function __construct(){
    $this->pdo = getPDO();
  }

  /** Inserta una columna y regresa el id */
  public function create(string $name, int $accountId): int {
    $st = $this->pdo->prepare('INSERT INTO pipelines(account_id, name) VALUES(?, ?)');
    $st->execute([$accountId, $name]);
    return (int)$this->pdo->lastInsertId();
  }

  /** Obtiene las columnas por cuenta */
  public function listByAccount(int $accountId): array {
    $st = $this->pdo->prepare('SELECT id, timestamp, account_id, name FROM pipelines WHERE account_id=? ORDER BY id');
    $st->execute([$accountId]);
    return $st->fetchAll();
  }
}
