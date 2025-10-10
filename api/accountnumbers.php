<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../classes/AccountNumbers.php';

try {
  $action = $_GET['action'] ?? $_POST['action'] ?? '';

  $svc = new AccountNumbers();

  switch ($action) {
    case 'create': {
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $account_id = (int)($in['account_id'] ?? 0);
      $number = trim($in['number'] ?? '');
      if (!$account_id || $number === '') throw new Exception('Datos incompletos');
      $svc->create($account_id, $number);
      echo json_encode(['ok'=>true]);
      break;
    }
    case 'list': {
      $account_id = (int)($_GET['account_id'] ?? 0);
      $rows = $svc->listByAccount($account_id);
      echo json_encode(['ok'=>true, 'rows'=>$rows]);
      break;
    }
    default:
      http_response_code(400);
      echo json_encode(['ok'=>false, 'error'=>'Acción no válida']);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
