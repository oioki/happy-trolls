<?php

class Calculator
{
    protected $endpoints = [];
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

    protected function calculatedSavedTime($latencyToDC, $latencyToCache)
    {
        return $latencyToDC - $latencyToCache;
    }


    public function getScore()
    {
        return 4;
    }
}