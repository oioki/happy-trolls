<?php

class Balancer
{
    /**
     * idx => size in mb
     *
     * [0 => 100, 1 => 50]
     *
     * @var array
     */
    protected $videoList = [];

    /**
     * Endpoint format:
     *
     * // endpointId
     * 0 =>[
     *   'latencyDataCenter' => 1,
     *   'cache' => [0 => 50, 1 => 100]], // cacheId => latency
     *   'requests' => [0 => 1000, 1 => 500] // videoId => requests count
     * ]
     *
     * @var array
     */
    protected $endPointList = [];

    /**
     * Cache count
     *
     * @var int
     */
    protected $cacheCount;

    /**
     * Cache capacity
     *
     * @var int
     */
    protected $cacheCapacity;

    /**
     * Result format
     *
     * // cacheID => [videoID]
     *
     * [0 => [1, 2], 1 => [2, 4]]
     *
     * @var array
     */
    protected $result = [];

    public function __construct($cacheCount, $cacheCapacity, $videoList, $endPointList)
    {
        $this->cacheCount = $cacheCount;
        $this->cacheCapacity = $cacheCapacity;
        $this->videoList = $videoList;
        $this->endPointList = $endPointList;
    }

    // stub
    public function none()
    {}

    public function sample()
    {
      $this->result = [
        2 => [3,4,5],
        4 => [5858],
      ];
    }

    public function pairs()
    {
      $pairs = [];
      foreach ($this->endPointList as $eID => $e) {
        foreach ($e['requests'] as $vID => $num) {
          foreach ($e['cache'] as $cID => $lat) {
            $key = $cID . ':' . $vID;
            if (!array_key_exists($key, $pairs)) {
              $pairs[$key] = 0;
            }
            $pairs[$key] += $num;
          }
        }
      }
      usort($pairs, function($a, $b) {
        if ($a == $b) {
          return 0;
        }
        return $a < $b ? 1 : -1;
      });
      foreach ($pairs as $key => $pair) {
        list($cID, $vID) = explode(',', $key);
        if (!$this->exists($cID, $vID) && $this->isOk($cID, $vID)) {
          $this->add($cID, $vID);
        }
      }
    }

    /**
     * Push The Tempo
     */
    public function basicPusher()
    {
        $videos = $this->videoList;
        $videoIdStart = 0;
        $result = [];

        for ($cacheId = 0; $cacheId < $this->cacheCount; $cacheId++) {
            $freeCapacity = $this->cacheCapacity;

            for ($videoId = $videoIdStart; $videoId < count($videos); ++$videoId) {
                if ($freeCapacity >= $videos[$videoId]) {
                    $result[$cacheId][] = $videoId;
                    $freeCapacity -= $videos[$videoId];
                } else {
                    break;
                }
            }
            $videoIdStart = $videoId;
        }

        $this->result = $result;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }
}
