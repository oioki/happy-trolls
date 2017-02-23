<?php

require_once __DIR__ . '/result.php';

$filename = __DIR__ . '/test.out';
$result = [
  4 => [4,5,6],
  8 => [],
  5 => [213,4],
];

save($filename, $result);
