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
     * I like watch it
     */
    public function mostPopularVideos()
    {
        $videos = $this->videoList;
        $endpoints = $this->endPointList;

        $videoRanking = [];
        $cacheRanking = [];

        foreach ($endpoints as $endpointId => $endpointData) {
            $endpointVideos = $endpointData['requests'];
            foreach ($endpointVideos as $videoId => $requestsCount) {
                if (!isset($videoRanking[$videoId])) {
                    $videoRanking[$videoId] = 0;
                }

                $videoRanking[$videoId] += $requestsCount;
            }

            $endpointCaches = $endpointData['cache'];

            foreach ($endpointCaches as $cacheId => $latency) {
                if (!isset($endpointCaches[$cacheId])) {
                    $cacheRanking[$cacheId] = 0;
                }

                $cacheRanking[$cacheId] += $latency;
            }
        }

        arsort($videoRanking);
        arsort($cacheRanking);

        $result = [];

        $cacheFreeCapacity = [];
        foreach ($cacheRanking as $cacheId => $rank) {
            $cacheFreeCapacity[$cacheId] = $this->cacheCapacity;
        }

        foreach ($videoRanking as $videoId => $videoRank) {
            foreach ($cacheRanking as $cacheId => $cacheRank) {
                if ($cacheFreeCapacity[$cacheId] >= $videos[$videoId]) {
                    $result[$cacheId][] = $videoId;

                    $cacheFreeCapacity[$cacheId] -= $videos[$videoId];
                } else {
                    break;
                }
            }
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
     * Is exists video in cache
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
     * I want this video
     */
    public function videoAllocationMethod()
    {
        $result = [];
        $cacheCapacity = [];
        for ($i = 0; $i < $this->cacheCount; ++$i) {
            $cacheCapacity[$i] = $this->cacheCapacity;
        }

        // TODO make rank not by requests
        $videosAvg = $this->getVideosAvgRequest();

        $rankedVideos = $this->getMostPopularVideos();
        $videoSizes = $this->videoList;

        $lostVideos = [];

        $endVideos = [];
        foreach ($rankedVideos as $videoId => $rank) {
            $endpointIds = $this->getEndpointIdsByVideoId($videoId);

            foreach ($endpointIds as $endpointId) {
                $cacheId = $this->getCacheIdByByEndpointIdAndVideoId(
                    $endpointId,
                    $videoId,
                    $cacheCapacity,
                    $videosAvg[$videoId],
                    $endVideos
                );

                if ($cacheId) {
                    if (!isset($result[$cacheId])) {
                        $result[$cacheId] = [];
                    }

                    if (!in_array($videoId, $result[$cacheId])) {
                        $result[$cacheId][] = $videoId;
                        $cacheCapacity[$cacheId] -= $videoSizes[$videoId];
                        $endVideos[$endpointId][] = $videoId;
                    }
                }
//                else {
//                    if (!in_array($videoId, $lostVideos)) {
//                        $lostVideos[] = $videoId;
//                    }
//                }
            }
        }

        arsort($cacheCapacity);

        $this->result = $result;
    }

    protected function getCacheIdByByEndpointIdAndVideoId($endpointId, $videoId, $cacheCapacity, $videoAvg, $endVideos)
    {
        $endpoints = $this->endPointList;
        $caches = $endpoints[$endpointId]['cache'];

        $videoRequestByEndpointId = $endpoints[$endpointId]['requests'][$videoId];

        $videoSize = $this->videoList[$videoId];

        asort($caches);

        if ($videoRequestByEndpointId >= $videoAvg) {
            foreach ($caches as $cacheId => $latency) {
                if ($cacheCapacity[$cacheId] >= $videoSize
                    && !in_array($videoId, $endVideos)
                ) {
                    return $cacheId;
                }
            }
        }

        return null;
    }


    /**
     * Get endpoint IDS by video ID
     *
     * @param $videoId
     * @return array
     */
    protected function getEndpointIdsByVideoId($videoId)
    {
        $endpointsIds = [];

        $endpoints = $this->endPointList;

        foreach ($endpoints as $endpointId => $endpointData) {
            if (array_key_exists($videoId, $endpointData['requests'])) {
                $endpointsIds[] = $endpointId;
            }
        }

        return $endpointsIds;
    }

    protected function getMostPopularVideos()
    {
        $endpoints = $this->endPointList;

        $videoRanking = [];

        foreach ($endpoints as $endpointId => $endpointData) {
            $endpointVideos = $endpointData['requests'];
            foreach ($endpointVideos as $videoId => $requestsCount) {
                if (!isset($videoRanking[$videoId])) {
                    $videoRanking[$videoId] = 0;
                }

                $videoRanking[$videoId] += $requestsCount;
            }
        }

        arsort($videoRanking);

        return $videoRanking;
    }

    /**
     * video avg requests
     *
     * @return array
     */
    protected function getVideosAvgRequest()
    {
        $endpoints = $this->endPointList;

        $videoRequests = [];
        foreach ($endpoints as $endpointId => $endpointData) {
            $endpointVideos = $endpointData['requests'];

            foreach ($endpointVideos as $videoId => $requestsCount) {
                if (!isset($videoRequests[$videoId])) {
                    $videoRequests[$videoId] = [];
                }

                $videoRequests[$videoId][] = $requestsCount;

            }
        }

        $videoAvg = [];

        foreach ($videoRequests as $videoId => $requests) {
            $videoAvg[$videoId] = count($requests)
                ? array_sum($requests) / count($requests)
                : 0;
        }

        return $videoAvg;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }
}
