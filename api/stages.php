<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
require_once __DIR__ . '/../classes/PipelineStages.php';

try {
  $action = $_GET['action'] ?? $_POST['action'] ?? '';
  $st = new PipelineStages();
  switch ($action) {
    case 'list':
      $pipeline_id = (int)($_GET['pipeline_id'] ?? 0);
      echo json_encode(['ok'=>true, 'rows'=>$st->listByPipeline($pipeline_id)]);
      break;
    case 'create':
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $pipeline_id = (int)($in['pipeline_id'] ?? 0);
      $name = trim($in['name'] ?? '');
      if(!$pipeline_id || $name==='') throw new Exception('Datos incompletos');
      $id = $st->create($pipeline_id, $name);
      echo json_encode(['ok'=>true, 'id'=>$id]);
      break;
    case 'reorder':
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $order = $in['order'] ?? [];
      $st->reorder($order);
      echo json_encode(['ok'=>true]);
      break;
    default:
      http_response_code(400);
      echo json_encode(['ok'=>false, 'error'=>'Acción no válida']);
  }
} catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
