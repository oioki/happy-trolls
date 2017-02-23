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
     * [  => [1, 2], 1 => [2, 4]]
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

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }
}