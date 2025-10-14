<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../classes/Stages.php';

try {
  $action = $_GET['action'] ?? $_POST['action'] ?? '';
  $svc = new Stages();

  switch ($action) {
    case 'list': {
      $pipelineId = (int)($_GET['pipeline_id'] ?? 0);
      $rows = $svc->list($pipelineId);
      echo json_encode(['ok'=>true,'rows'=>$rows]);
      break;
    }
    case 'create': {
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $pipelineId = (int)($in['pipeline_id'] ?? 0);
      $name = trim($in['name'] ?? '');
      if (!$pipelineId || $name==='') throw new Exception('Parámetros inválidos');
      $id = $svc->createStage($pipelineId, $name);
      echo json_encode(['ok'=>true,'id'=>$id]);
      break;
    }
    case 'reorder': {
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $order = $in['order'] ?? [];
      $svc->reorder(array_map('intval', $order));
      echo json_encode(['ok'=>true]);
      break;
    }
    case 'createPipeline': {
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $accountId = (int)($in['account_id'] ?? 0);
      $name = trim($in['name'] ?? '');
      if (!$accountId || $name==='') throw new Exception('Parámetros inválidos');
      $pipelineId = $svc->createPipeline($accountId, $name);
      echo json_encode(['ok'=>true,'pipeline_id'=>$pipelineId]);
      break;
    }
    default:
      http_response_code(400);
      echo json_encode(['ok'=>false,'error'=>'Acción no válida']);
  }
} catch(Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
