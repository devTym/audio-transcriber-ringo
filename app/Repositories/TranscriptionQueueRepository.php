<?php

namespace App\Repositories;

use JsonException;
use System\Redis\RedisClient;

class TranscriptionQueueRepository
{
    private const string KEY_QUEUE      = 'transcription:queue';
    private const string KEY_FAILED     = 'transcription:queue:failed';
    private const string KEY_QUEUED_IDS = 'transcription:queued_ids';

    private RedisClient $redis;

    public function __construct()
    {
        $this->redis = new RedisClient();
    }

    /**
     * @throws JsonException
     */
    public function enqueue(int $callId, string $audioUrl, string $audioType): bool
    {
        $id = (string) $callId;

        if ($this->redis->sismember(self::KEY_QUEUED_IDS, $id)) {
            return false;
        }

        $payload = json_encode([
            'call_id'    => $callId,
            'audio_url'  => $audioUrl,
            'audio_type' => $audioType,
        ], JSON_THROW_ON_ERROR);

        $this->redis->sadd(self::KEY_QUEUED_IDS, $id);
        $this->redis->rpush(self::KEY_QUEUE, $payload);

        return true;
    }

    public function dequeue(): ?array
    {
        $raw = $this->redis->lpop(self::KEY_QUEUE);

        if ($raw === false || $raw === null) {
            return null;
        }

        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }

    public function blockingDequeue(int $timeout = 30): ?array
    {
        $result = $this->redis->blpop(self::KEY_QUEUE, $timeout);

        if ($result === false || $result === null) {
            return null;
        }

        return json_decode($result[1], true, 512, JSON_THROW_ON_ERROR);
    }

    public function markDone(int $callId): void
    {
        $this->redis->srem(self::KEY_QUEUED_IDS, (string) $callId);
    }

    public function markFailed(int $callId, string $audioUrl, string $audioType, string $error): void
    {
        $payload = json_encode([
            'call_id'    => $callId,
            'audio_url'  => $audioUrl,
            'audio_type' => $audioType,
            'error'      => $error,
            'failed_at'  => date('Y-m-d H:i:s'),
        ], JSON_THROW_ON_ERROR);

        $this->redis->rpush(self::KEY_FAILED, $payload);
    }

    public function pendingCount(): int
    {
        return (int) $this->redis->llen(self::KEY_QUEUE);
    }

    public function failedCount(): int
    {
        return (int) $this->redis->llen(self::KEY_FAILED);
    }
}