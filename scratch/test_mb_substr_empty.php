<?php
$file = "";
$strpos = strpos($file, 'media/cache'); // false
echo "strpos is false: " . var_export($strpos, true) . PHP_EOL;
try {
    $res = mb_substr($file, $strpos, strlen($file), 'UTF-8');
    echo "mb_substr res: " . var_export($res, true) . PHP_EOL;
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
