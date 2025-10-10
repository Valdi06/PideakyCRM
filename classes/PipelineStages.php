<?php

require_once '../conexion/conexion.php';

class PipelineStages {
  private $pdo;
  public function __construct(){ 
    $this->pdo = getPDO();
  }
  
  public function create(int $pipelineId, string $name): int {
    $sort = $this->pdo->prepare('SELECT COALESCE(MAX(sort_order), -1)+1 FROM pipelinestages WHERE pipeline_id=?');
    $sort->execute([$pipelineId]);
    $next = (int)$sort->fetchColumn();
    $st = $this->pdo->prepare('INSERT INTO pipelinestages(pipeline_id,name,sort_order) VALUES(?,?,?)');
    $st->execute([$pipelineId,$name,$next]);
    return (int)$this->pdo->lastInsertId();
  }
  public function listByPipeline(int $pipelineId): array {
    $st=$this->pdo->prepare('SELECT id, timestamp, pipeline_id, name, sort_order FROM pipelinestages WHERE pipeline_id=? ORDER BY sort_order, id');
    $st->execute([$pipelineId]);
    return $st->fetchAll();
  }
  public function reorder(array $order): void {
    $this->pdo->beginTransaction();
    foreach($order as $i=>$id){
      $up=$this->pdo->prepare('UPDATE pipelinestages SET sort_order=? WHERE id=?');
      $up->execute([$i,(int)$id]);
    }
    $this->pdo->commit();
  }
}
