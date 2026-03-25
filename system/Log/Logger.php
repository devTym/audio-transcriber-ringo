<?php

namespace System\Log;

class Logger
{
    private const COLORS = [
        LogLevel::INFO->value    => "\033[36m",
        LogLevel::ERROR->value   => "\033[31m",
        LogLevel::WARNING->value => "\033[33m",
        LogLevel::DEBUG->value   => "\033[37m",
        LogLevel::SUCCESS->value => "\033[32m",
    ];
    private const COLOR_RESET = "\033[0m";

    private static ?string $logDir = null;

    public static function info(string $message, LogTarget $target = LogTarget::BOTH): void
    {
        self::write(LogLevel::INFO, $message, $target);
    }

    public static function error(string $message, LogTarget $target = LogTarget::BOTH): void
    {
        self::write(LogLevel::ERROR, $message, $target);
    }

    public static function warning(string $message, LogTarget $target = LogTarget::BOTH): void
    {
        self::write(LogLevel::WARNING, $message, $target);
    }

    public static function debug(string $message, LogTarget $target = LogTarget::BOTH): void
    {
        self::write(LogLevel::DEBUG, $message, $target);
    }

    public static function success(string $message, LogTarget $target = LogTarget::BOTH): void
    {
        self::write(LogLevel::SUCCESS, $message, $target);
    }

    public static function log(
        string    $message,
        LogLevel  $level  = LogLevel::INFO,
        LogTarget $target = LogTarget::BOTH,
    ): void {
        self::write($level, $message, $target);
    }

    public static function setLogDir(string $dir): void
    {
        self::$logDir = rtrim($dir, '/');
    }

    private static function write(LogLevel $level, string $message, LogTarget $target): void
    {
        $now  = date('Y-m-d H:i:s');
        $date = date('Y-m-d');

        if ($target === LogTarget::BOTH || $target === LogTarget::FILE) {
            $fileLine = "[{$now}] [{$level->value}] {$message}" . PHP_EOL;
            file_put_contents(self::getLogDir() . "/{$date}.log", $fileLine, FILE_APPEND | LOCK_EX);
        }

        if ($target === LogTarget::BOTH || $target === LogTarget::CONSOLE) {
            $color       = self::COLORS[$level->value] ?? '';
            $consoleLine = "[{$now}] {$color}[{$level->value}]" . " {$message}" . self::COLOR_RESET . PHP_EOL;
            echo $consoleLine;
        }
    }

    private static function getLogDir(): string
    {
        if (self::$logDir === null) {
            self::$logDir = dirname(__DIR__, 2) . '/storage/logs';
        }

        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0775, true);
        }

        return self::$logDir;
    }
}