<?php

namespace App\Services;

use App\Services\Transcribers\FasterWhisperTranscriber;
use App\Services\Transcribers\TranscriberInterface;
use App\Services\Transcribers\WhisperCppTranscriber;
use InvalidArgumentException;
use RuntimeException;

class TranscriptionService
{
    private static array $registry = [
        'whisper_cpp'    => WhisperCppTranscriber::class,
        'faster_whisper' => FasterWhisperTranscriber::class,
    ];

    private TranscriberInterface $transcriber;

    public function __construct(?string $transcriberName = null)
    {
        $name = $transcriberName
            ?? getenv('TRANSCRIBER')
            ?: 'whisper_cpp';

        $this->transcriber = self::make($name);
    }

    public function transcribe(string $audioFilePath, string $audioType, int $callId): string
    {
        return $this->transcriber->transcribe($audioFilePath);
    }

    public function getTranscriberName(): string
    {
        return $this->transcriber->getName();
    }

    public static function make(string $name): TranscriberInterface
    {
        if (!isset(self::$registry[$name])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown transcriber "%s". Available options: %s',
                $name,
                implode(', ', self::available())
            ));
        }

        $class = self::$registry[$name];

        if (!class_exists($class)) {
            throw new RuntimeException(sprintf(
                'Transcriber class "%s" not found.',
                $class
            ));
        }

        return new $class();
    }

    public static function available(): array
    {
        return array_keys(self::$registry);
    }
}