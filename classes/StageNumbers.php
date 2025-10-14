<?php
require_once '../conexion/conexion.php';

class StageNumbers {
  private $pdo;
  public function __construct(){
    $this->pdo = getPDO();
  }

  public function assign(int $stageId, int $chatId, int $accountId): void {
    $this->pdo->beginTransaction();

    $ph = $this->pdo->prepare("SELECT phone AS fromnumber, source_phone FROM phones WHERE id=?");
    $ph->execute([$chatId]);
    $row = $ph->fetch(PDO::FETCH_ASSOC);
    if (!$row) { $this->pdo->rollBack(); throw new \Exception('chat_id inexistente'); }

    $maxQ = $this->pdo->prepare("SELECT MAX(order_key) FROM pipelinestagenumbers WHERE pipelinestage_id=?");
    $maxQ->execute([$stageId]);
    $next = $maxQ->fetchColumn();
    $next = $next !== null ? (string)($next + 1) : '1';

    $ins = $this->pdo->prepare("
      INSERT INTO pipelinestagenumbers (pipelinestage_id, chat_id, fromnumber, source_phone, order_key)
      VALUES (?,?,?,?,?)
    ");
    $ins->execute([$stageId, $chatId, $row['fromnumber'], $row['source_phone'], $next]);

    $pl = $this->pdo->prepare("SELECT pipeline_id FROM pipelinestages WHERE id=?");
    $pl->execute([$stageId]);
    $pipelineId = (int)$pl->fetchColumn();
    if ($pipelineId) {
      $deact = $this->pdo->prepare("UPDATE pipelineinbox SET active=0, closed_at=NOW() WHERE pipeline_id=? AND chat_id=? AND active=1");
      $deact->execute([$pipelineId, $chatId]);
    }

    $this->pdo->commit();
  }

  public function move(int $chatId, int $toStageId, int $toIndex): void {
    $this->pdo->beginTransaction();

    $sel = $this->pdo->prepare("
      SELECT psn.id, psn.order_key
      FROM pipelinestagenumbers psn
      WHERE psn.pipelinestage_id=?
      ORDER BY psn.order_key, psn.id
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
    if (!$row) { $this->pdo->rollBack(); throw new \Exception('chat_id inexistente'); }

    $ins = $this->pdo->prepare("
      INSERT INTO pipelinestagenumbers (pipelinestage_id, chat_id, fromnumber, source_phone, order_key)
      VALUES (?,?,?,?,?)
    ");
    $ins->execute([$toStageId, $chatId, $row['fromnumber'], $row['source_phone'], number_format($order, 10, '.', '')]);

    $pl = $this->pdo->prepare("SELECT pipeline_id FROM pipelinestages WHERE id=?");
    $pl->execute([$toStageId]);
    $pipelineId = (int)$pl->fetchColumn();
    if ($pipelineId) {
      $deact = $this->pdo->prepare("UPDATE pipelineinbox SET active=0, closed_at=NOW() WHERE pipeline_id=? AND chat_id=? AND active=1");
      $deact->execute([$pipelineId, $chatId]);
    }

    $this->pdo->commit();
  }

  public function listByPipeline(int $pipelineId): array {
    $sql = "
      SELECT 
        cur.pipelinestage_id,
        cur.chat_id,
        cur.fromnumber,
        cur.source_phone,
        ph.lasttimestamp AS last_ts,
        ph.origin, ph.message_type, ph.profile_name,
        COALESCE(
          CASE WHEN ph.origin='received' AND ph.message_type='text' THEN mr.message_received END,
          CASE WHEN ph.origin='sent'     AND ph.message_type='text' THEN sm.message_sent END,
          CASE WHEN ph.message_type='file' THEN CONCAT('[Archivo] ', COALESCE(cf.filename,'')) END,
          ''
        ) AS last_text
      FROM (
        /* Última fila por chat_id dentro del pipeline (MAX(id)) */
        SELECT psn1.*
        FROM pipelinestagenumbers psn1
        JOIN pipelinestages ps1 ON ps1.id = psn1.pipelinestage_id
        JOIN (
          SELECT psn.chat_id, MAX(psn.id) AS max_id
          FROM pipelinestagenumbers psn
          JOIN pipelinestages ps ON ps.id = psn.pipelinestage_id
          WHERE ps.pipeline_id = :pl1
          GROUP BY psn.chat_id
        ) last ON last.max_id = psn1.id
        WHERE ps1.pipeline_id = :pl2
      ) cur
      LEFT JOIN pipelineinbox pin ON pin.chat_id = cur.chat_id AND pin.active = 1
      JOIN phones ph ON BINARY ph.id = BINARY cur.chat_id
      LEFT JOIN messageschb mr ON (ph.origin='received' AND ph.message_type='text' AND mr.id=ph.message_id)
      LEFT JOIN sent_messages sm ON (ph.origin='sent'     AND ph.message_type='text' AND sm.id=ph.message_id)
      LEFT JOIN customerfiles cf ON (ph.message_type='file' AND cf.id=ph.message_id)
      WHERE pin.chat_id IS NULL
      ORDER BY cur.pipelinestage_id, cur.order_key, cur.id
    ";
    $st = $this->pdo->prepare($sql);
    $st->bindValue(':pl1', $pipelineId, \PDO::PARAM_INT);
    $st->bindValue(':pl2', $pipelineId, \PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
  }
}
