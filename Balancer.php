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
      uasort($pairs, function($a, $b) {
        if ($a == $b) {
          return 0;
        }
        return $a < $b ? 1 : -1;
      });
      foreach ($pairs as $key => $pair) {
        list($cID, $vID) = explode(':', $key);
        if (!$this->exists($cID, $vID) && $this->isOk($cID, $vID)) {
          $this->add($cID, $vID);
        }
      }
    }

    public function bonuses()
    {
      $pairs = [];
      foreach ($this->endPointList as $eID => $e) {
        $latDC = $e['latencyDataCenter'];
        foreach ($e['requests'] as $vID => $num) {
          foreach ($e['cache'] as $cID => $lat) {
            $key = $cID . ':' . $vID;
            if (!array_key_exists($key, $pairs)) {
              $pairs[$key] = 0;
            }
            $bonus = $latDC - $lat;
            $pairs[$key] += $num * $bonus;
          }
        }
      }
      uasort($pairs, function($a, $b) {
        if ($a == $b) {
          return 0;
        }
        return $a < $b ? 1 : -1;
      });
      foreach ($pairs as $key => $pair) {
        list($cID, $vID) = explode(':', $key);
        if (!$this->exists($cID, $vID) && $this->isOk($cID, $vID)) {
          $this->add($cID, $vID);
        }
      }
    }

    public function sizeDoesMatter()
    {
      global $argv;
      if (array_key_exists(3, $argv)){
        $kSize = $argv[3];
      } else {
        $kSize = 1;
      }
      $pairs = [];
      foreach ($this->endPointList as $eID => $e) {
        $latDC = $e['latencyDataCenter'];
        foreach ($e['requests'] as $vID => $num) {
          foreach ($e['cache'] as $cID => $lat) {
            $key = $cID . ':' . $vID;
            if (!array_key_exists($key, $pairs)) {
              $pairs[$key] = 0;
            }
            $bonus = $latDC - $lat;
            $pairs[$key] += $num * $bonus / pow($this->videoList[$vID], $kSize);
          }
        }
      }
      uasort($pairs, function($a, $b) {
        if ($a == $b) {
          return 0;
        }
        return $a < $b ? 1 : -1;
      });
      foreach ($pairs as $key => $pair) {
        list($cID, $vID) = explode(':', $key);
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
        return array_key_exists($cacheId, $this->result) && in_array($videoId, $this->result[$cacheId]);
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
