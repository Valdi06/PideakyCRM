<?php
require_once '../conexion/conexion.php';

class StageNumbers {
  private $pdo;
  public function __construct(){
    $this->pdo = getPDO();
  }

  private function firstPipelineIdOfAccount(int $accountId): ?int {
    $st = $this->pdo->prepare("SELECT MIN(id) FROM pipelines WHERE account_id=?");
    $st->execute([$accountId]);
    $id = $st->fetchColumn();
    return $id !== false ? (int)$id : null;
  }

  private function accountIdOfPipeline(int $pipelineId): ?int {
    $st = $this->pdo->prepare("SELECT account_id FROM pipelines WHERE id=?");
    $st->execute([$pipelineId]);
    $id = $st->fetchColumn();
    return $id !== false ? (int)$id : null;
  }

  public function firstStageId(int $pipelineId): ?int {
    $st = $this->pdo->prepare("SELECT id FROM pipelinestages WHERE pipeline_id=? ORDER BY sort_order, id LIMIT 1");
    $st->execute([$pipelineId]);
    $id = $st->fetchColumn();
    return $id !== false ? (int)$id : null;
  }

  public function assign(int $stageId, int $chatId, int $accountId): void {
    if ($stageId <= 0) throw new InvalidArgumentException('stageId inválido');

    $this->pdo->beginTransaction();

    $ph = $this->pdo->prepare("SELECT phone AS fromnumber, source_phone FROM phones WHERE id=?");
    $ph->execute([$chatId]);
    $row = $ph->fetch(PDO::FETCH_ASSOC);
    if (!$row) { $this->pdo->rollBack(); throw new Exception('chat_id inexistente'); }

    $maxQ = $this->pdo->prepare("SELECT MAX(order_key) FROM pipelinestagenumbers WHERE pipelinestage_id=?");
    $maxQ->execute([$stageId]);
    $next = $maxQ->fetchColumn();
    $next = $next !== null ? (string)($next + 1) : '1';

    $ins = $this->pdo->prepare("
      INSERT INTO pipelinestagenumbers (pipelinestage_id, chat_id, fromnumber, source_phone, order_key)
      VALUES (?,?,?,?,?)
    ");
    $ins->execute([$stageId, $chatId, $row['fromnumber'], $row['source_phone'], $next]);

    $this->pdo->commit();
  }

  public function move(int $chatId, int $toStageId, int $toIndex): void {
    if ($toStageId <= 0) throw new InvalidArgumentException('toStageId inválido');

    $this->pdo->beginTransaction();

    $sel = $this->pdo->prepare("
      SELECT id, order_key
      FROM pipelinestagenumbers
      WHERE pipelinestage_id=?
      ORDER BY order_key, id
    ");
    $sel->execute([$toStageId]);
    $rows = $sel->fetchAll(PDO::FETCH_ASSOC);

    $toIndex = max(0, min((int)$toIndex, count($rows)));
    $prevKey = $toIndex > 0 ? (float)$rows[$toIndex-1]['order_key'] : null;
    $nextKey = $toIndex < count($rows) ? (float)$rows[$toIndex]['order_key'] : null;
    $order = 1.0;
    if ($prevKey !== null && $nextKey !== null) $order = ($prevKey + $nextKey)/2.0;
    elseif ($prevKey !== null) $order = $prevKey + 1.0;
    elseif ($nextKey !== null) $order = $nextKey - 1.0;

    $ph = $this->pdo->prepare("SELECT phone AS fromnumber, source_phone FROM phones WHERE id=?");
    $ph->execute([$chatId]);
    $row = $ph->fetch(PDO::FETCH_ASSOC);
    if (!$row) { $this->pdo->rollBack(); throw new Exception('chat_id inexistente'); }

    $ins = $this->pdo->prepare("
      INSERT INTO pipelinestagenumbers (pipelinestage_id, chat_id, fromnumber, source_phone, order_key)
      VALUES (?,?,?,?,?)
    ");
    $ins->execute([$toStageId, $chatId, $row['fromnumber'], $row['source_phone'], number_format($order, 10, '.', '')]);

    $this->pdo->commit();
  }

  public function moveToPipelineFirstStage(int $chatId, int $toPipelineId): void {
    $firstStageId = $this->firstStageId($toPipelineId);
    if (!$firstStageId) throw new Exception('Pipeline destino sin columnas');

    $this->assign($firstStageId, $chatId, 0);
  }


public function listByPipeline(int $pipelineId): array {
  // 1) Última asignación GLOBAL por chat
  $sqlReal = "
    WITH latest AS (
      SELECT psn.chat_id, MAX(psn.id) AS max_id
      FROM pipelinestagenumbers psn
      GROUP BY psn.chat_id
    ),
    latest_rows AS (
      SELECT psn1.*, ps.pipeline_id
      FROM pipelinestagenumbers psn1
      JOIN latest l ON l.max_id = psn1.id
      JOIN pipelinestages ps ON ps.id = psn1.pipelinestage_id
    )
    SELECT 
      lr.pipelinestage_id,
      lr.chat_id,
      lr.fromnumber,
      lr.source_phone,
      ph.lasttimestamp AS last_ts,
      ph.origin, ph.message_type, ph.profile_name,
      COALESCE(
        CASE WHEN ph.origin='received' AND ph.message_type='text' THEN mr.message_received END,
        CASE WHEN ph.origin='sent'     AND ph.message_type='text' THEN sm.message_sent END,
        CASE WHEN ph.message_type='file' THEN CONCAT('[Archivo] ', COALESCE(cf.filename,'')) END,
        ''
      ) AS last_text,
      0 AS is_virtual
    FROM latest_rows lr
    JOIN phones ph ON BINARY ph.id = BINARY lr.chat_id
    LEFT JOIN messageschb mr ON (ph.origin='received' AND ph.message_type='text' AND mr.id=ph.message_id)
    LEFT JOIN sent_messages sm ON (ph.origin='sent'     AND ph.message_type='text' AND sm.id=ph.message_id)
    LEFT JOIN customerfiles cf ON (ph.message_type='file' AND cf.id=ph.message_id)
    WHERE lr.pipeline_id = :pl
    ORDER BY lr.pipelinestage_id, lr.order_key, lr.id
  ";
  $stReal = $this->pdo->prepare($sqlReal);
  $stReal->execute(['pl' => $pipelineId]);
  $realRows = $stReal->fetchAll(PDO::FETCH_ASSOC);

  $accIdStmt = $this->pdo->prepare("SELECT account_id FROM pipelines WHERE id=?");
  $accIdStmt->execute([$pipelineId]);
  $accId = (int)$accIdStmt->fetchColumn();

  $firstPipeline = null;
  if ($accId) {
    $fp = $this->pdo->prepare("SELECT MIN(id) FROM pipelines WHERE account_id=?");
    $fp->execute([$accId]);
    $firstPipeline = (int)$fp->fetchColumn();
  }

  if ($firstPipeline && $firstPipeline === $pipelineId) {
    // 2) Entrada virtual del primer pipeline: chats de la cuenta SIN NINGUNA asignación global
    $firstStageIdStmt = $this->pdo->prepare("
      SELECT id FROM pipelinestages WHERE pipeline_id=? ORDER BY sort_order, id LIMIT 1
    ");
    $firstStageIdStmt->execute([$pipelineId]);
    $firstStageId = (int)$firstStageIdStmt->fetchColumn();

    $sqlVirt = "
      WITH any_assigned AS (
        SELECT DISTINCT chat_id FROM pipelinestagenumbers
      )
      SELECT
        :first_stage_id AS pipelinestage_id,
        ph.id AS chat_id,
        ph.phone AS fromnumber,
        ph.source_phone,
        ph.lasttimestamp AS last_ts,
        ph.origin, ph.message_type, ph.profile_name,
        COALESCE(
          CASE WHEN ph.origin='received' AND ph.message_type='text' THEN mr.message_received END,
          CASE WHEN ph.origin='sent'     AND ph.message_type='text' THEN sm.message_sent END,
          CASE WHEN ph.message_type='file' THEN CONCAT('[Archivo] ', COALESCE(cf.filename,'')) END,
          ''
        ) AS last_text,
        1 AS is_virtual
      FROM phones ph
      JOIN accountnumbers an
        ON an.account_id = :acc
       AND BINARY an.number = BINARY ph.source_phone
      LEFT JOIN any_assigned aa ON BINARY aa.chat_id = BINARY ph.id
      LEFT JOIN messageschb mr ON (ph.origin='received' AND ph.message_type='text' AND mr.id=ph.message_id)
      LEFT JOIN sent_messages sm ON (ph.origin='sent'     AND ph.message_type='text' AND sm.id=ph.message_id)
      LEFT JOIN customerfiles cf ON (ph.message_type='file' AND cf.id=ph.message_id)
      WHERE aa.chat_id IS NULL
      ORDER BY ph.lasttimestamp DESC
    ";
    $stVirt = $this->pdo->prepare($sqlVirt);
    $stVirt->execute([
      'first_stage_id' => $firstStageId,
      'acc' => $accId
    ]);
    $virtRows = $stVirt->fetchAll(PDO::FETCH_ASSOC);

    return array_values(array_merge($virtRows, $realRows));
  }

  return $realRows ?: [];
}

  public function moveToPipelineFirstStageApi(int $chatId, int $toPipelineId): void {
    $this->moveToPipelineFirstStage($chatId, $toPipelineId);
  }
}

