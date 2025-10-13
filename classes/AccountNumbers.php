<?php
require_once '../conexion/conexion.php';

class AccountNumbers {
  private $pdo;
  public function __construct(){
    $this->pdo = getPDO();
  }

  public function create(int $accountId, string $number): void {
    $st = $this->pdo->prepare(
      'INSERT INTO accountnumbers(account_id, number) VALUES(?,?)
       ON DUPLICATE KEY UPDATE number=VALUES(number)'
    );
    $st->execute([$accountId, $number]);
  }

  public function listByAccount(int $accountId): array {
    if (!$accountId) return [];
    $st = $this->pdo->prepare('SELECT id, timestamp, account_id, number FROM accountnumbers WHERE account_id=? ORDER BY id DESC');
    $st->execute([$accountId]);
    return $st->fetchAll();
  }
}
