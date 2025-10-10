<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
require_once __DIR__ . '/../classes/Accounts.php';

try {
  $action = $_GET['action'] ?? $_POST['action'] ?? '';
  $acc = new Accounts();
  switch ($action) {
    case 'list':
      echo json_encode(['ok'=>true, 'rows'=>$acc->listAll()]);
      break;
    case 'create':
      $in = json_decode(file_get_contents('php://input'), true) ?? [];
      $name = trim($in['name'] ?? '');
      if($name==='') throw new Exception('Nombre requerido');
      $id = $acc->create($name);
      echo json_encode(['ok'=>true, 'id'=>$id]);
      break;
    default:
      http_response_code(400);
      echo json_encode(['ok'=>false, 'error'=>'Acción no válida']);
  }
} catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
