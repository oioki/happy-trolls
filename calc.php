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

        foreach ($this->endpoints as $endpointId => $endpoint) {
            $latencyDataCenter = $endpoint['latencyDataCenter'];
            foreach ($endpoint['requests'] as $videoId => $requestsCount) {
                $latencyToCache = $endpoint['latencyDataCenter'];
                foreach ($this->result as $cacheId => $videoIds) {
                    if (in_array($videoId, $videoIds)
                        && !empty($endpoint['cache'][$cacheId])
                        && $latencyToCache > $endpoint['cache'][$cacheId]
                    ) {
                        $latencyToCache = $endpoint['cache'][$cacheId];
                    }
                }
                $calculatedScore = $requestsCount * $this->calculatedSavedTime($latencyDataCenter, $latencyToCache);

                if ($calculatedScore > 0) {
                    $score += $calculatedScore;
                }

                $requests += $requestsCount;
            }
        }

        return (int)($score / $requests * 1000);
    }
}