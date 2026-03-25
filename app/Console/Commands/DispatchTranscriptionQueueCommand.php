<?php

namespace App\Console\Commands;

use App\Repositories\CallListRepository;
use App\Repositories\TranscriptionQueueRepository;
use System\Log\Logger;
use System\Log\LogTarget;

class DispatchTranscriptionQueueCommand
{
    private const int BATCH_SIZE = 500;

    private CallListRepository $callListRepo;
    private TranscriptionQueueRepository $queueRepo;

    public function __construct()
    {
        $this->callListRepo = new CallListRepository();
        $this->queueRepo    = new TranscriptionQueueRepository();
    }

    public function handle(): void
    {
        Logger::warning('Filling Redis transcription queue...');
        Logger::info(sprintf(
            'Pending: %d | Failed: %d',
            $this->queueRepo->pendingCount(),
            $this->queueRepo->failedCount()
        ));

        $lastId   = 0;
        $total    = 0;
        $enqueued = 0;
        $skipped  = 0;
        $batchNum = 0;

        do {
            $batchNum++;
            $rows = $this->callListRepo->getPendingTranscriptionBatch($lastId, self::BATCH_SIZE);

            if (empty($rows)) {
                break;
            }

            Logger::info("Batch #{$batchNum}: " . count($rows) . " records loaded (after id={$lastId})");

            foreach ($rows as $row) {
                $total++;
                $lastId = (int) $row['id'];

                [$audioUrl, $audioType] = $this->pickAudio($row);

                if ($audioUrl === null) {
                    $skipped++;
                    continue;
                }

                $wasAdded = $this->queueRepo->enqueue((int) $row['id'], $audioUrl, $audioType);
                $wasAdded ? $enqueued++ : $skipped++;
            }

        } while (count($rows) === self::BATCH_SIZE);

        Logger::info('---', LogTarget::CONSOLE);
        Logger::info("Checked   : {$total}");
        Logger::info("Enqueued  : {$enqueued}");
        Logger::info("Skipped   : {$skipped}");
        Logger::info(sprintf(
            'Queue now: %d pending | %d failed',
            $this->queueRepo->pendingCount(),
            $this->queueRepo->failedCount()
        ));
        Logger::success('Done!');
    }

    private function pickAudio(array $row): array
    {
        if (!empty($row['recording'])) {
            return [$row['recording'], 'ogg'];
        }

        if (!empty($row['recording_wav'])) {
            return [$row['recording_wav'], 'wav'];
        }

        return [null, ''];
    }
}