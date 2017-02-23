<?php

class Calculator
{
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
    protected $endpoints = [];

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

    /**
     * @param array $endpoints
     * @param array $result
     */
    public function __construct($endpoints, $result)
    {
        $this->endpoints = $endpoints;
        $this->result = $result;
    }

    /**
     *
     * @param int $latencyToDC
     * @param int $latencyToCache
     * @return int
     */
    protected function calculatedSavedTime($latencyToDC, $latencyToCache)
    {
        return $latencyToDC - $latencyToCache;
    }

    /**
     * @return float
     */
    public function getScore()
    {
        $score = 0;
        $requests = 0;

        foreach ($this->endpoints as $endpoint) {
            foreach ($endpoint['requests'] as $request) {
                $latencyToCache = $endpoint['latencyDataCenter'];
                foreach ($this->result as $cacheId => $videoIds) {
                    $latencyToCache = $endpoint['cache'][$cacheId];
                }

                $score += $request * $this->calculatedSavedTime($endpoint['latencyDataCenter'], $latencyToCache);

                $requests += $request;
            }
        }

        return round($score / $requests, 2);
    }
}