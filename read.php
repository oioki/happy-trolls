<?php
$file = "test.txt";
$content = file_get_contents($file);
$rows = explode(PHP_EOL, $content);
$arr = [];
foreach ($rows as $rawRow) {
    $arr[] = explode(' ', $rawRow);
}
print_r($arr);

