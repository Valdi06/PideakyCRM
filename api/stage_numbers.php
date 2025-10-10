<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
require_once __DIR__ . '/../classes/StageNumbers.php';

try {
  $action = $_GET['action'] ?? $_POST['action'] ?? '';
  $sn = new StageNumbers();
  switch ($action) {
    case 'list':
      $pipeline_id = (int)($_GET['pipeline_id'] ?? 0);
      echo json_encode(['ok'=>true, 'rows'=>$sn->listByPipeline($pipeline_id)]);
      break;
    case 'assign':
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $stage_id = (int)($in['stage_id'] ?? 0);
      $fromnumber = trim($in['fromnumber'] ?? '');
      $account_id = (int)($in['account_id'] ?? 0);
      if(!$stage_id || $fromnumber==='' || !$account_id) throw new Exception('Datos incompletos');
      $sn->assign($stage_id, $fromnumber, $account_id);
      echo json_encode(['ok'=>true]);
      break;
    case 'move':
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $fromnumber = trim($in['fromnumber'] ?? '');
      $to_stage_id = (int)($in['to_stage_id'] ?? 0);
      $to_index = (int)($in['to_index'] ?? 0);
      if($fromnumber==='' || !$to_stage_id) throw new Exception('Datos incompletos');
      $sn->move($fromnumber, $to_stage_id, $to_index);
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
