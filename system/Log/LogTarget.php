<?php

namespace System\Log;

enum LogTarget
{
    case BOTH;
    case FILE;
    case CONSOLE;
}