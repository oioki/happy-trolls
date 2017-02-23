<?php

require_once __DIR__ . '/parser.php';
require_once __DIR__ . '/result.php';
require_once __DIR__ . '/calc.php';
require_once __DIR__ . '/Balancer.php';

$filename = __DIR__ . '/' . $argv[1];
$algo = $argv[2];

list($endpoints, $videos, $cacheSize, $cacheCount) = getData($filename);

$balancer = new Balancer($cacheCount, $cacheSize, $videos, $endpoints);
$balancer->$algo();
$result = $balancer->getResult();

$calc = new Calculator($endpoints, $result);
$score = $calc->getScore();
var_dump($score);

save($filename . '.out', $result);
