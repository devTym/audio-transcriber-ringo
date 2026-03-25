<?php

namespace System\Log;

enum LogLevel: string
{
    case INFO    = 'INFO';
    case ERROR   = 'ERROR';
    case WARNING = 'WARNING';
    case DEBUG   = 'DEBUG';
    case SUCCESS = 'SUCCESS';
}