<?php

require_once '../conexion/conexion.php';

class StageNumbers {
  private $pdo;
  public function __construct(){ 
    $this->pdo = getPDO(); 
  }
  
public function listByPipeline(int $pipelineId): array
{
    try {
        $sql = "
          SELECT
            r.pipelinestage_id,
            r.fromnumber,
            r.order_key,
            r.id,
            ph.lasttimestamp AS last_ts,
            ph.origin,
            ph.message_type,
            ph.profile_name,
            ph.source_phone,
            COALESCE(
              CASE WHEN ph.origin='received' AND ph.message_type='text' THEN mr.message_received END,
              CASE WHEN ph.origin='sent'     AND ph.message_type='text' THEN sm.message_sent END,
              CASE WHEN ph.message_type='file' THEN CONCAT('[Archivo] ', COALESCE(cf.filename,'')) END,
              ''
            ) AS last_text
          FROM pipelinestagenumbers r
          JOIN (
            SELECT psn.fromnumber, MAX(psn.id) AS max_id
            FROM pipelinestagenumbers psn
            JOIN pipelinestages ps2  ON ps2.id = psn.pipelinestage_id
            WHERE ps2.pipeline_id = :pipeline_id1
            GROUP BY psn.fromnumber
          ) latest ON latest.max_id = r.id
          JOIN pipelinestages ps ON ps.id = r.pipelinestage_id
          JOIN pipelines pl ON pl.id = ps.pipeline_id
          JOIN phones ph ON BINARY ph.phone = BINARY r.fromnumber
          JOIN accountnumbers an ON an.account_id = pl.account_id AND BINARY an.number = BINARY ph.source_phone
          LEFT JOIN messageschb mr ON (ph.origin='received' AND ph.message_type='text' AND mr.id = ph.message_id)
          LEFT JOIN sent_messages sm ON (ph.origin='sent' AND ph.message_type='text' AND sm.id = ph.message_id)
          LEFT JOIN customerfiles cf ON (ph.message_type='file' AND cf.id = ph.message_id)
          WHERE ps.pipeline_id = :pipeline_id2
          ORDER BY r.pipelinestage_id, r.order_key IS NULL, r.order_key, r.id
        ";

        $st = $this->pdo->prepare($sql);
        $st->bindValue(':pipeline_id1', $pipelineId, PDO::PARAM_INT);
        $st->bindValue(':pipeline_id2', $pipelineId, PDO::PARAM_INT);
        $st->execute();

        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        return $rows ?: [];
    } catch (\Throwable $e) {
        return [];
    }
}


  public function assign(int $stageId, string $fromnumber, int $accountId): void {
    $this->pdo->beginTransaction();

    $maxQ = $this->pdo->prepare("
      SELECT MAX(r.order_key) AS max_key
      FROM pipelinestagenumbers r
      JOIN (
        SELECT psn.fromnumber, MAX(psn.id) AS max_id
        FROM pipelinestagenumbers psn
        WHERE psn.pipelinestage_id = ?
        GROUP BY psn.fromnumber
      ) latest ON latest.max_id = r.id
    ");
    $maxQ->execute([$stageId]);
    $maxKey = $maxQ->fetchColumn();
    $newKey = $maxKey !== null ? (string)($maxKey + 1) : '1';

    $ins = $this->pdo->prepare(
      'INSERT INTO pipelinestagenumbers(pipelinestage_id, fromnumber, order_key)
      VALUES(?,?,?)'
    );
    $ins->execute([$stageId, $fromnumber, $newKey]);

    $this->pdo->commit();
  }


  public function move(string $fromnumber, int $toStageId, int $toIndex): void {
    $this->pdo->beginTransaction();

    $listQ = $this->pdo->prepare("
      SELECT r.fromnumber, r.order_key
      FROM pipelinestagenumbers r
      JOIN (
        SELECT psn.fromnumber, MAX(psn.id) AS max_id
        FROM pipelinestagenumbers psn
        WHERE psn.pipelinestage_id = ?
        GROUP BY psn.fromnumber
      ) latest ON latest.max_id = r.id
      ORDER BY r.order_key, r.id
    ");
    $listQ->execute([$toStageId]);
    $rows = $listQ->fetchAll();

    $rows = array_values(array_filter($rows, function($x) use ($fromnumber){
      return $x['fromnumber'] !== $fromnumber;
    }));

    $toIndex = max(0, min((int)$toIndex, count($rows)));

    $prevKey = null;
    $nextKey = null;
    if ($toIndex > 0) {
      $prevKey = $rows[$toIndex - 1]['order_key'];
    }
    if ($toIndex < count($rows)) {
      $nextKey = $rows[$toIndex]['order_key'];
    }

    $newKey = null;
    if ($prevKey !== null && $nextKey !== null) {
      $newKey = ( (float)$prevKey + (float)$nextKey ) / 2.0;
    } elseif ($prevKey !== null) {
      $newKey = (float)$prevKey + 1.0;
    } elseif ($nextKey !== null) {
      $newKey = (float)$nextKey - 1.0;
    } else {
      $newKey = 1.0; // columna vacía
    }

    $ins = $this->pdo->prepare(
      'INSERT INTO pipelinestagenumbers(pipelinestage_id, fromnumber, order_key)
      VALUES(?,?,?)'
    );
    $ins->execute([$toStageId, $fromnumber, $newKey]);

    $this->pdo->commit();
  }


}
