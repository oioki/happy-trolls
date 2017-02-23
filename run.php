<?php

ini_set('memory_limit','6000M');

require_once __DIR__ . '/parser.php';
require_once __DIR__ . '/result.php';
require_once __DIR__ . '/calc.php';
require_once __DIR__ . '/Balancer.php';

$filename = __DIR__ . '/' . $argv[1];
$algo = $argv[2];

list($endpoints, $videos, $cacheCount, $cacheCapacity) = getData($filename);

$balancer = new Balancer($cacheCount, $cacheCapacity, $videos, $endpoints);
$balancer->$algo();
$balancer->validate();
$result = $balancer->getResult();

$calc = new Calculator($endpoints, $result);
$score = $calc->getScore();
var_dump($score);

save($filename . '.out', $result);
