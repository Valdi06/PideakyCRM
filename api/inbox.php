<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../classes/Inbox.php';

try {
  $action = $_GET['action'] ?? $_POST['action'] ?? '';
  $svc = new Inbox();

  switch ($action) {
    case 'list': {
      $accountId  = (int)($_GET['account_id'] ?? 0);
      $pipelineId = (int)($_GET['pipeline_id'] ?? 0);
      if (!$accountId || !$pipelineId) throw new Exception('Parámetros incompletos');
      $rows = $svc->listByAccount($accountId, $pipelineId);
      echo json_encode(['ok'=>true, 'rows'=>$rows]);
      break;
    }

    case 'add': {
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $fromPipelineId = (int)($in['from_pipeline_id'] ?? 0); // opcional (no lo usamos)
      $toPipelineId   = (int)($in['pipeline_id'] ?? 0);
      $chatId         = (int)($in['chat_id'] ?? 0);
      if (!$toPipelineId || !$chatId) throw new Exception('Parámetros incompletos');

      $svc->moveToPipelineInbox($fromPipelineId, $toPipelineId, $chatId);
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
