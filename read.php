<?php
$file = __DIR__ . "/input.txt";
$rows = file($file);
$arr = [];
foreach ($rows as $rawRow) {
    $arr[] = explode(' ', $rawRow);
}
print_r($arr);
