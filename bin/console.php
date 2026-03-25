<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$command = $argv[1] ?? null;

switch ($command) {
    case 'dispatch:transcription':
        new \App\Console\Commands\DispatchTranscriptionQueueCommand()->handle();
        break;

    default:
        echo "Unknown command: {$command}" . PHP_EOL;
        echo "Available commands:" . PHP_EOL;
        echo "  dispatch:transcription  - fill the transcription queue" . PHP_EOL;
        exit(1);
}