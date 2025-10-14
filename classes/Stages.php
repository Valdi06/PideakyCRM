<?php
require_once '../conexion/conexion.php';

class Stages {
  private $pdo;
  public function __construct(){ 
    $this->pdo = getPDO();
}

  public function list(int $pipelineId): array {
    $st = $this->pdo->prepare("SELECT id, name, sort_order FROM pipelinestages WHERE pipeline_id=? ORDER BY sort_order, id");
    $st->execute([$pipelineId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }

  public function createStage(int $pipelineId, string $name): int {
    $ord = $this->pdo->prepare("SELECT COALESCE(MAX(sort_order), -1)+1 FROM pipelinestages WHERE pipeline_id=?");
    $ord->execute([$pipelineId]);
    $next = (int)$ord->fetchColumn();

    $ins = $this->pdo->prepare("INSERT INTO pipelinestages(pipeline_id, name, sort_order) VALUES(?,?,?)");
    $ins->execute([$pipelineId, $name, $next]);
    return (int)$this->pdo->lastInsertId();
  }

  public function reorder(array $orderIds): void {
    $this->pdo->beginTransaction();
    foreach ($orderIds as $i => $id) {
      $up = $this->pdo->prepare("UPDATE pipelinestages SET sort_order=? WHERE id=?");
      $up->execute([$i, (int)$id]);
    }
    $this->pdo->commit();
  }

  public function createPipeline(int $accountId, string $name): int {
    $this->pdo->beginTransaction();
    $pi = $this->pdo->prepare("INSERT INTO pipelines(account_id, name) VALUES(?,?)");
    $pi->execute([$accountId, $name]);
    $pipelineId = (int)$this->pdo->lastInsertId();

    // crear primera columna
    $this->createStage($pipelineId, 'Entrada');

    $this->pdo->commit();
    return $pipelineId;
  }

  public function firstStageId(int $pipelineId): ?int {
    $st = $this->pdo->prepare("SELECT id FROM pipelinestages WHERE pipeline_id=? ORDER BY sort_order, id LIMIT 1");
    $st->execute([$pipelineId]);
    $id = $st->fetchColumn();
    return $id !== false ? (int)$id : null;
  }
}
