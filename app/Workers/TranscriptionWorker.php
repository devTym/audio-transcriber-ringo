<?php

namespace App\Workers;

use App\Repositories\CallListRepository;
use App\Repositories\TranscriptionQueueRepository;
use App\Services\AudioDownloaderService;
use App\Services\TranscriptionService;
use JsonException;
use System\Log\Logger;
use Throwable;

class TranscriptionWorker
{
    private const BLPOP_TIMEOUT = 30;

    private TranscriptionQueueRepository $queueRepo;
    private CallListRepository           $callListRepo;
    private AudioDownloaderService       $downloader;
    private TranscriptionService         $transcriber;
    private string                       $storageDir;

    public function __construct()
    {
        $this->storageDir   = dirname(__DIR__, 2) . '/storage/input';

        $this->queueRepo    = new TranscriptionQueueRepository();
        $this->callListRepo = new CallListRepository();
        $this->downloader   = new AudioDownloaderService($this->storageDir);
        $this->transcriber  = new TranscriptionService();

        Logger::info('Worker started. Transcriber: ' . $this->transcriber->getTranscriberName());
    }

    /**
     * @throws JsonException
     */
    public function processOne(): bool
    {
        $item = $this->queueRepo->blockingDequeue(self::BLPOP_TIMEOUT);

        if ($item === null) {
            Logger::warning('Queue is empty. Exiting.');
            return false;
        }

        $this->processItem($item);
        return true;
    }

    public function loop(): void
    {
        Logger::info('Start infinite loop using BLPOP...');

        while (true) {
            $item = $this->queueRepo->blockingDequeue(0);

            if ($item === null) {
                continue;
            }

            $this->processItem($item);
        }
    }

    private function processItem(array $item): void
    {
        $callId    = (int)    $item['call_id'];
        $audioUrl  = (string) $item['audio_url'];
        $audioType = (string) $item['audio_type'];

        Logger::info(" >>> call_id={$callId} [{$audioType}]");

        $audioFilePath = null;

        try {
            Logger::info("  [->] Downloading audio file...");
            $audioFilePath = $this->downloader->download($audioUrl, $audioType, $callId);
            Logger::info("  [OK] {$audioFilePath}");

            Logger::info("  [->] Transcribe [{$this->transcriber->getTranscriberName()}]...");
            $text = $this->transcriber->transcribe($audioFilePath, $audioType, $callId);
            Logger::info('  [OK] ' . mb_strlen($text) . ' symbols');

            $textFilePath = $this->storageDir . "/call_{$callId}.txt";
            file_put_contents($textFilePath, $text);
            Logger::info("  [OK] File: {$textFilePath}");

            $this->callListRepo->saveRecordingText($callId, $text);
            Logger::info("  [OK] recording_text >>> DB");

            $this->queueRepo->markDone($callId);

            $this->downloader->cleanup($audioFilePath);
            Logger::success("Done call_id={$callId}");

        } catch (Throwable $e) {
            Logger::error("[ERROR] " . $e->getMessage());

            $this->queueRepo->markFailed($callId, $audioUrl, $audioType, $e->getMessage());

            if ($audioFilePath !== null) {
                $this->downloader->cleanup($audioFilePath);
            }
        }
    }
}