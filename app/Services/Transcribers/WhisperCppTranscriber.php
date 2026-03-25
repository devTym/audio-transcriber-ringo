<?php

namespace App\Services\Transcribers;

use RuntimeException;

class WhisperCppTranscriber implements TranscriberInterface
{
    private string $bin;
    private string $model;
    private string $lang;
    private int    $threads;

    public function __construct()
    {
        $this->bin     = getenv('WHISPER_CPP_BIN')   ?: '/opt/whisper.cpp/build/bin/whisper-cli';
        $this->model   = getenv('WHISPER_CPP_MODEL')  ?: '/models/whisper.cpp/ggml-large-v3.bin';
        $this->lang    = getenv('WHISPER_CPP_LANG')   ?: 'auto';
        $this->threads = (int)(getenv('WHISPER_CPP_THREADS') ?: 4);

        if (!is_executable($this->bin)) {
            throw new RuntimeException("whisper.cpp binary not found or not executable: {$this->bin}");
        }

        if (!is_file($this->model)) {
            throw new RuntimeException("whisper.cpp model not found: {$this->model}");
        }
    }

    public function getName(): string
    {
        return 'whisper_cpp';
    }

    public function transcribe(string $audioFilePath): string
    {
        $workFile   = $audioFilePath;
        $tmpWav     = null;
        $extension  = strtolower(pathinfo($audioFilePath, PATHINFO_EXTENSION));

        if ($extension !== 'wav') {
            $tmpWav   = $audioFilePath . '_whisper_tmp.wav';
            $this->convertToWav($audioFilePath, $tmpWav);
            $workFile = $tmpWav;
        }

        try {
            $text = $this->runWhisper($workFile);
        } finally {
            if ($tmpWav !== null && file_exists($tmpWav)) {
                unlink($tmpWav);
            }
        }

        return $text;
    }


    private function runWhisper(string $wavPath): string
    {
        $cmd = sprintf(
            '%s --model %s --language %s --threads %d --no-timestamps --output-txt --file %s 2>/dev/null',
            escapeshellarg($this->bin),
            escapeshellarg($this->model),
            escapeshellarg($this->lang),
            $this->threads,
            escapeshellarg($wavPath)
        );

        [$stdout, $stderr, $exitCode] = $this->exec($cmd);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                "whisper.cpp exited with code {$exitCode}. Stderr: " . trim($stderr)
            );
        }

        $txtFile = $wavPath . '.txt';
        if (file_exists($txtFile)) {
            $text = trim(file_get_contents($txtFile));
            unlink($txtFile);
            return $text;
        }

        return trim($stdout);
    }

    private function convertToWav(string $src, string $dst): void
    {
        $cmd = sprintf(
            'ffmpeg -y -i %s -ar 16000 -ac 1 -f wav %s 2>/dev/null',
            escapeshellarg($src),
            escapeshellarg($dst)
        );

        [,, $exitCode] = $this->exec($cmd);

        if ($exitCode !== 0 || !file_exists($dst)) {
            throw new RuntimeException("ffmpeg failed to convert file: {$src}");
        }
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