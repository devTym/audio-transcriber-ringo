<?php

namespace App\Repositories;

use PDO;
use System\Database\PdoFactory;
use System\Log\Logger;

class CallListRepository
{
    private PDO $pdo;

    private string $table = 'ringostat_call_lists';

    public function __construct()
    {
        $this->pdo = PdoFactory::make();
    }

    public function getPendingTranscriptionBatch(int $afterId = 0, int $limit = 500): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, recording, recording_wav
               FROM ' . $this->table . ' 
              WHERE id > :after_id
                AND (
                    (recording     IS NOT NULL AND recording     <> \'\')
                 OR (recording_wav IS NOT NULL AND recording_wav <> \'\')
                )
                AND (recording_text IS NULL OR recording_text = \'\')
              ORDER BY id ASC
              LIMIT :limit'
        );
        $stmt->bindValue(':after_id', $afterId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',    $limit,   PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function saveRecordingText(int $id, string $text): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ' . $this->table . '
                SET recording_text = :text
              WHERE id = :id'
        );
        $stmt->execute([':text' => $text, ':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}