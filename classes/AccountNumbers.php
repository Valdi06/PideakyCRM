<?php
require_once '../conexion/conexion.php';

class AccountNumbers {
  private $pdo;
  public function __construct(){
    $this->pdo = getPDO();
    $this->ensureSchema(); // puedes quitarlo en prod
  }

  private function ensureSchema(){
    $this->pdo->exec("CREATE TABLE IF NOT EXISTS accountnumbers (
      id INT AUTO_INCREMENT PRIMARY KEY,
      timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      account_id INT NOT NULL,
      number VARCHAR(30) NOT NULL,
      UNIQUE KEY uniq_acc_num (account_id, number),
      INDEX (account_id), INDEX (number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  }

  /** Inserta o asegura la existencia del número en la cuenta (idempotente) */
  public function create(int $accountId, string $number): void {
    $st = $this->pdo->prepare(
      'INSERT INTO accountnumbers(account_id, number) VALUES(?,?)
       ON DUPLICATE KEY UPDATE number=VALUES(number)'
    );
    $st->execute([$accountId, $number]);
  }

  /** Lista números por cuenta (opcional para UI) */
  public function listByAccount(int $accountId): array {
    if (!$accountId) return [];
    $st = $this->pdo->prepare('SELECT id, timestamp, account_id, number FROM accountnumbers WHERE account_id=? ORDER BY id DESC');
    $st->execute([$accountId]);
    return $st->fetchAll();
  }
}
