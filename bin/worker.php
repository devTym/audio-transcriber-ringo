<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$workerName = $argv[1] ?? null;
$loop       = in_array('--loop', $argv, true);

switch ($workerName) {
    case 'transcription':
        $worker = new \App\Workers\TranscriptionWorker();
        try {
            $loop ? $worker->loop() : $worker->processOne();
        } catch (JsonException $e) {
            echo $e->getMessage();
        }
        break;

    default:
        echo "Unknown worker: {$workerName}" . PHP_EOL;
        echo "Available:" . PHP_EOL;
        echo "  transcription        - process one record and exit" . PHP_EOL;
        echo "  transcription --loop - run in loop (BLPOP)" . PHP_EOL;;;
        exit(1);
}