<?php

namespace App\Services\Aliases;

use SplFileObject;

class AliasGenerator
{
    public static function generate()
    {
        $parts = array();

        $file = new SplFileObject(__DIR__ . '/short_words.txt');

        for ($i = 0; $i < 2; $i++) {
            $lineNumber = random_int(0, 1295);
            $file->seek($lineNumber);
            $parts[] = trim($file->current());
        }

        $parts[] = random_int(100,999);

        return implode((string) '-', $parts);
    }
}