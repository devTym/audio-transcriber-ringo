<?php

namespace App\Services\Transcribers;

use RuntimeException;

interface TranscriberInterface
{
    /**
     * @param  string $audioFilePath
     * @return string
     *
     * @throws RuntimeException
     */
    public function transcribe(string $audioFilePath): string;

    public function getName(): string;
}