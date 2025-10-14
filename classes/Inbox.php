<?php
require_once '../conexion/conexion.php';

class Inbox {
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

  public function listByAccount(int $accountId, int $pipelineId): array {
    $first = $this->firstPipelineIdOfAccount($accountId);

    if ($first && $pipelineId === $first) {
      $sql = "
        SELECT 
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
          ) AS last_text
        FROM phones ph
        JOIN accountnumbers an
          ON an.account_id = :acc1
         AND an.number = ph.source_phone
        LEFT JOIN pipelinestagenumbers psn_any ON psn_any.chat_id = ph.id
        LEFT JOIN pipelineinbox pin_any ON pin_any.chat_id = ph.id AND pin_any.active = 1
        LEFT JOIN pipelineinbox pin_first ON pin_first.chat_id = ph.id AND pin_first.active = 1 AND pin_first.pipeline_id = :first_id
        LEFT JOIN messageschb mr ON (ph.origin='received' AND ph.message_type='text' AND mr.id=ph.message_id)
        LEFT JOIN sent_messages sm ON (ph.origin='sent'     AND ph.message_type='text' AND sm.id=ph.message_id)
        LEFT JOIN customerfiles cf ON (ph.message_type='file' AND cf.id=ph.message_id)
        WHERE (pin_first.chat_id IS NOT NULL)
           OR (psn_any.chat_id IS NULL AND pin_any.chat_id IS NULL)
        ORDER BY ph.lasttimestamp DESC
      ";
      $st = $this->pdo->prepare($sql);
      $st->execute(['acc1'=>$accountId, 'first_id'=>$first]);
      return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    $sql = "
      SELECT 
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
        ) AS last_text
      FROM pipelineinbox pin
      JOIN phones ph ON ph.id = pin.chat_id
      LEFT JOIN messageschb mr ON (ph.origin='received' AND ph.message_type='text' AND mr.id=ph.message_id)
      LEFT JOIN sent_messages sm ON (ph.origin='sent'     AND ph.message_type='text' AND sm.id=ph.message_id)
      LEFT JOIN customerfiles cf ON (ph.message_type='file' AND cf.id=ph.message_id)
      WHERE pin.pipeline_id = :pipe_id
        AND pin.active = 1
      ORDER BY ph.lasttimestamp DESC
    ";
    $st = $this->pdo->prepare($sql);
    $st->execute(['pipe_id'=>$pipelineId]);
    return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
  }

  public function moveToPipelineInbox(int $fromPipelineId, int $toPipelineId, int $chatId): void {
    $this->pdo->beginTransaction();

    $ph = $this->pdo->prepare("SELECT phone AS fromnumber, source_phone FROM phones WHERE id=?");
    $ph->execute([$chatId]);
    $row = $ph->fetch(\PDO::FETCH_ASSOC);
    if (!$row) { $this->pdo->rollBack(); throw new \Exception('chat_id inexistente'); }

    $deact = $this->pdo->prepare("UPDATE pipelineinbox SET active=0, closed_at=NOW() WHERE chat_id=? AND active=1");
    $deact->execute([$chatId]);

    $up = $this->pdo->prepare("
      UPDATE pipelineinbox
         SET active=1, closed_at=NULL, fromnumber=?, source_phone=?
       WHERE pipeline_id=? AND chat_id=?
    ");
    $up->execute([$row['fromnumber'], $row['source_phone'], $toPipelineId, $chatId]);
    if ($up->rowCount() === 0) {
      $ins = $this->pdo->prepare("
        INSERT INTO pipelineinbox (pipeline_id, chat_id, fromnumber, source_phone, active, closed_at)
        VALUES (?,?,?,?,1,NULL)
      ");
      $ins->execute([$toPipelineId, $chatId, $row['fromnumber'], $row['source_phone']]);
    }

    $this->pdo->commit();
  }
}
