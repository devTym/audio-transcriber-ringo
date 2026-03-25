<?php

namespace App\Services\Transcribers;

use RuntimeException;

class FasterWhisperTranscriber implements TranscriberInterface
{
    private string $pythonBin;
    private string $scriptPath;
    private string $modelDir;
    private string $lang;
    private string $device;

    public function __construct()
    {
        $this->pythonBin  = getenv('PYTHON_BIN')                ?: 'python3';
        $this->scriptPath = dirname(__DIR__, 3) . '/scripts/transcribe_faster_whisper.py';
        $this->modelDir   = getenv('FASTER_WHISPER_MODEL_DIR')  ?: '/models/faster-whisper-large-v3';
        $this->lang       = getenv('FASTER_WHISPER_LANG')        ?: 'auto';
        $this->device     = getenv('FASTER_WHISPER_DEVICE')      ?: 'cpu';

        if (!is_file($this->scriptPath)) {
            throw new RuntimeException("Python script not found: {$this->scriptPath}");
        }
    }

    public function getName(): string
    {
        return 'faster_whisper';
    }

    public function transcribe(string $audioFilePath): string
    {
        $cmd = sprintf(
            '%s %s --model-dir %s --lang %s --device %s --file %s',
            escapeshellarg($this->pythonBin),
            escapeshellarg($this->scriptPath),
            escapeshellarg($this->modelDir),
            escapeshellarg($this->lang),
            escapeshellarg($this->device),
            escapeshellarg($audioFilePath)
        );

        [$stdout, $stderr, $exitCode] = $this->exec($cmd);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                "faster-whisper done with code {$exitCode}. Stderr: " . trim($stderr)
            );
        }

        return trim($stdout);
    }

    private function exec(string $cmd): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);

        if ($process === false) {
            throw new RuntimeException("Failed to start process: {$cmd}");
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [$stdout, $stderr, $exitCode];
    }
}