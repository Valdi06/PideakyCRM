<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../classes/StageNumbers.php';

try {
  $action = $_GET['action'] ?? $_POST['action'] ?? '';
  $svc = new StageNumbers();

  switch ($action) {
    case 'assign': {
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $svc->assign((int)$in['stage_id'], (int)$in['chat_id'], (int)$in['account_id']);
      echo json_encode(['ok'=>true]);
      break;
    }
    case 'move': {
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $svc->move((int)$in['chat_id'], (int)$in['to_stage_id'], (int)$in['to_index']);
      echo json_encode(['ok'=>true]);
      break;
    }
    case 'list': {
      $pipelineId = (int)($_GET['pipeline_id'] ?? 0);
      $rows = $svc->listByPipeline($pipelineId);
      echo json_encode(['ok'=>true,'rows'=>$rows]);
      break;
    }
    default:
      http_response_code(400);
      echo json_encode(['ok'=>false,'error'=>'Acción no válida']);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
