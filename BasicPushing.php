<?php

include 'Balancer.php';
include 'constants.php';
include 'parser.php';

class BasicPushing extends Balancer
{
    public function calculate()
    {
        $videos = $this->videoList;
        $result = [];

        for ($cacheId = 0; $cacheId < $this->cacheCount; ++$cacheId) {
            $freeCapacity = $this->cacheCapacity;

            for ($videoId = 0; $videoId < $videos; ++$videoId) {
                if ($freeCapacity >= $videos[$videoId]) {
                    $result[$cacheId][] = $videoId;
                    $freeCapacity -= $videos[$videoId];
                }
            }
        }


        $this->result = $result;
    }
}

$data = getData(ME_AT_ZOO);

//$cacheCount, $cacheCapacity, $videoList, $endPointList
$balancer = new BasicPushing(
    $data['cacheCount'],
    $data['cacheCapacity'],
    $data['videos'],
    $data['endpoints']
);

$balancer->calculate();
