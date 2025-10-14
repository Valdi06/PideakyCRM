<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../classes/StageNumbers.php';

try {
  $action = $_GET['action'] ?? $_POST['action'] ?? '';
  $svc = new StageNumbers();

  switch ($action) {
    case 'assign': {
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $stageId = (int)($in['stage_id'] ?? 0);
      $chatId  = (int)($in['chat_id'] ?? 0);
      $accId   = (int)($in['account_id'] ?? 0);
      if ($stageId <= 0 || $chatId <= 0) throw new Exception('Parámetros inválidos');
      $svc->assign($stageId, $chatId, $accId);
      echo json_encode(['ok'=>true]);
      break;
    }
    case 'move': {
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $toStageId = (int)($in['to_stage_id'] ?? 0);
      $chatId    = (int)($in['chat_id'] ?? 0);
      $toIndex   = (int)($in['to_index'] ?? 0);
      if ($toStageId <= 0 || $chatId <= 0) throw new Exception('Parámetros inválidos');
      $svc->move($chatId, $toStageId, $toIndex);
      echo json_encode(['ok'=>true]);
      break;
    }
    case 'list': {
      $pipelineId = (int)($_GET['pipeline_id'] ?? 0);
      $rows = $svc->listByPipeline($pipelineId);
      echo json_encode(['ok'=>true,'rows'=>$rows]);
      break;
    }
    case 'move_to_pipeline': { 
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $toPipelineId = (int)($in['to_pipeline_id'] ?? 0);
      $chatId       = (int)($in['chat_id'] ?? 0);
      if ($toPipelineId <= 0 || $chatId <= 0) throw new Exception('Parámetros inválidos');
      $svc->moveToPipelineFirstStageApi($chatId, $toPipelineId);
      echo json_encode(['ok'=>true]);
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
