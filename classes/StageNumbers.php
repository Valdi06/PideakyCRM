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
            p.lasttimestamp AS last_ts,
            p.origin,
            p.message_type,
            p.profile_name,
            COALESCE(
              CASE WHEN p.origin='received' AND p.message_type='text' THEN mr.message_received END,
              CASE WHEN p.origin='sent'     AND p.message_type='text' THEN sm.message_sent END,
              CASE WHEN p.message_type='file' THEN CONCAT('[Archivo] ', COALESCE(cf.filename,'')) END,
              ''
            ) AS last_text
          FROM pipelinestagenumbers r
          JOIN (
            SELECT psn.fromnumber, MAX(psn.id) AS max_id
            FROM pipelinestagenumbers psn
            JOIN pipelinestages ps2 ON ps2.id = psn.pipelinestage_id
            WHERE ps2.pipeline_id = :pipeline_id1
            GROUP BY psn.fromnumber
          ) latest ON latest.max_id = r.id
          JOIN pipelinestages ps ON ps.id = r.pipelinestage_id
          LEFT JOIN phones p
            ON p.phone = r.fromnumber
          LEFT JOIN messageschb mr
            ON (p.origin='received' AND p.message_type='text' AND mr.id = p.message_id)
          LEFT JOIN sent_messages sm
            ON (p.origin='sent' AND p.message_type='text' AND sm.id = p.message_id)
          LEFT JOIN customerfiles cf
            ON (p.message_type='file' AND cf.id = p.message_id)
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
        // Opcional: loguea el error
        // error_log('listByPipeline error: '.$e->getMessage());
        return [];
    }
}



  public function assign(int $stageId, string $fromnumber, int $accountId): void {
    $this->pdo->beginTransaction();

    // Asegura que exista en accountnumbers
    // $st = $this->pdo->prepare(
    //   'INSERT INTO accountnumbers(account_id, number) VALUES(?,?)
    //   ON DUPLICATE KEY UPDATE number=VALUES(number)'
    // );
    // $st->execute([$accountId, $fromnumber]);

    // order_key al final de ese stage, basado en la “foto” actual
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

    // Insert histórico (sin updates)
    $ins = $this->pdo->prepare(
      'INSERT INTO pipelinestagenumbers(pipelinestage_id, fromnumber, order_key)
      VALUES(?,?,?)'
    );
    $ins->execute([$stageId, $fromnumber, $newKey]);

    $this->pdo->commit();
  }


  public function move(string $fromnumber, int $toStageId, int $toIndex): void {
    $this->pdo->beginTransaction();

    // 1) Foto actual de la columna destino (latest por fromnumber)
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

    // 2) Quitar si ya aparece en destino (por si se reubica dentro del mismo stage)
    $rows = array_values(array_filter($rows, function($x) use ($fromnumber){
      return $x['fromnumber'] !== $fromnumber;
    }));

    // 3) Clamp del índice destino
    $toIndex = max(0, min((int)$toIndex, count($rows)));

    // 4) Obtener vecinos
    $prevKey = null;
    $nextKey = null;
    if ($toIndex > 0) {
      $prevKey = $rows[$toIndex - 1]['order_key'];
    }
    if ($toIndex < count($rows)) {
      $nextKey = $rows[$toIndex]['order_key'];
    }

    // 5) Calcular order_key intermedio
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

    // 6) Insertar nueva “foto” (histórico)
    $ins = $this->pdo->prepare(
      'INSERT INTO pipelinestagenumbers(pipelinestage_id, fromnumber, order_key)
      VALUES(?,?,?)'
    );
    $ins->execute([$toStageId, $fromnumber, $newKey]);

    $this->pdo->commit();
  }


}
