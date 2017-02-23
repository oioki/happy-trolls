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

    protected $cacheList = [];

    public function __construct($cacheCount, $cacheCapacity, $videoList, $endPointList)
    {
        $this->cacheCount = $cacheCount;
        $this->cacheCapacity = $cacheCapacity;
        $this->videoList = $videoList;
        $this->endPointList = $endPointList;
        $this->cacheList = array_fill(0, $this->cacheCount, $this->cacheCapacity);
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
      var_dump($pairs);
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
     * @param $cacheId
     * @param $videoId
     * @return bool
     */
    public function isOk($cacheId, $videoId)
    {
        return $this->cacheList[$cacheId] >= $this->videoList[$videoId];
    }

    /**
     * Add video to cache
     *
     * @param $cacheId
     * @param $videoId
     */
    public function add($cacheId, $videoId)
    {
        $this->cacheList[$cacheId] -= $this->videoList[$videoId];

        if (!array_key_exists($cacheId, $this->result)) {
            $this->result[$cacheId] = [];
        }
        $this->result[$cacheId][] = $videoId;
    }

    /**
     * Is exists video in acche
     *
     * @param $cacheId
     * @param $videoId
     * @return bool
     */
    public function exists($cacheId, $videoId)
    {
        return in_array($videoId, $this->cacheList[$cacheId]);
    }

    /**
     * Validate result caches
     *
     * @return bool
     * @throws \Exception
     */
    public function validate()
    {
        $oversizesCacheList = array_filter($this->cacheList, function($cacheSize){
            return $cacheSize < 0;
        });

        if ($oversizesCacheList) {
            throw new Exception('Oversized caches: ' . implode(',', $oversizesCacheList));
        }

        return true;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }
}
