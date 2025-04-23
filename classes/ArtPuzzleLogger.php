<?php

class ArtPuzzleLogger
{
    protected static $logFile = _PS_ROOT_DIR_ . '/modules/art_puzzle/logs/art_puzzle.log';

    public static function log($message, $level = 'INFO')
    {
        $date = date('Y-m-d H:i:s');
        $entry = "[$date] [$level] $message" . PHP_EOL;
        file_put_contents(self::$logFile, $entry, FILE_APPEND);
    }
}
