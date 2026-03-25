<?php

namespace App\Services;

use RuntimeException;

class AudioDownloaderService
{
    private string $storageDir;

    public function __construct(string $storageDir)
    {
        // project_dir/storage/input/
        $this->storageDir = rtrim($storageDir, '/');
    }

    public function download(string $url, string $audioType, int $callId): string
    {
        $this->ensureDirectoryExists();

        $filename = sprintf('call_%d_%s.%s', $callId, uniqid(), $audioType);
        $targetPath = $this->storageDir . '/' . $filename;

        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL.');
        }

        $fp = fopen($targetPath, 'wb');

        if ($fp === false) {
            throw new RuntimeException("Failed to open file for writing: {$targetPath}");
        }

        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_FAILONERROR => true,
            CURLOPT_USERAGENT => 'TranscriptionWorker/1.0',
        ]);

        $success = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        fclose($fp);

        if ($success === false || $httpCode >= 400) {
            // Delete empty or incomplete file
            if (file_exists($targetPath)) {
                unlink($targetPath);
            }

            throw new RuntimeException(
                "Download error (HTTP {$httpCode}): {$error} | URL: {$url}"
            );
        }

        return $targetPath;
    }

    public function cleanup(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->storageDir)) {
            if (!mkdir($this->storageDir, 0775, true)) {
                throw new RuntimeException("Failed to create directory: {$this->storageDir}");
            }
        }
    }
}