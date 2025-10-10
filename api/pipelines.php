<?php
// api/pipelines.php
// Endpoint único para columnas (pipelines)
// Acciones: ?action=list  |  ?action=save (POST JSON {name})

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../classes/Pipelines.php';

try {
  $action = $_GET['action'] ?? $_POST['action'] ?? '';
  $pipelines = new Pipelines();

  switch ($action) {
    case 'list':
      $rows = $pipelines->listByAccount(1); // account_id fijo = 1
      echo json_encode(['ok'=>true, 'rows'=>$rows]);
      break;

    case 'save':
      $input = json_decode(file_get_contents('php://input'), true) ?? [];
      $name  = trim($input['name'] ?? '');
      if ($name === '') throw new Exception('Nombre requerido');
      $id = $pipelines->create($name, 1); // account_id fijo = 1
      echo json_encode(['ok'=>true, 'id'=>$id]);
      break;

    default:
      http_response_code(400);
      echo json_encode(['ok'=>false, 'error'=>'Acción no válida']);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
