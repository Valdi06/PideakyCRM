<?php

require_once '../conexion/conexion.php';

class Accounts {
  private $pdo;
  public function __construct(){ 
    $this->pdo = getPDO();
  }
  
  public function create(string $name): int {
    $st=$this->pdo->prepare('INSERT INTO accounts(name) VALUES(?)');
    $st->execute([$name]);
    return (int)$this->pdo->lastInsertId();
  }

  public function listAll(): array { 
    return $this->pdo->query('SELECT id, timestamp, name FROM accounts ORDER BY id')->fetchAll(); 
  }
}
