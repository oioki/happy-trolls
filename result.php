<?php

function toString(array $result) {
  $strings = [];
  $strings[] = (string) count($result);
  foreach ($result as $cacheId => $videoIds) {
    if ($videoIds) {
      $strings[] = $cacheId . ' ' . implode(' ', $videoIds);
    }
  }
  return implode(PHP_EOL, $strings);
}

function save($filename, array $result) {
  $data = toString($result);
  if (!file_put_contents($filename, $data)) {
    throw new Exception('Can\'t save');
  }
}
