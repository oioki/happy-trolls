<?php

require_once __DIR__ . '/calc.php';

$endpoints = [
    [
        'latencyDataCenter' => 1000,
        'cache' => [100, 300, 200], // cacheId => latency
        'requests' => [3 => 1500, 4 => 500, 1 => 1000], // videoId => requests count
    ],
    [
        'latencyDataCenter' => 500,
        'cache' => [], // cacheId => latency
        'requests' => [0 => 1000], // videoId => requests count
    ],
];

$result = [
  0 => [2],
  1 => [3, 1],
  2 => [0, 1],
];

$calc = new Calculator($endpoints, $result);

$expected = 462.5 * 1000;
assert($calc->getScore() == $expected, 'Wrong result');
