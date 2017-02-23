<?php

function getVideosEndpoints($filename = "me_at_the_zoo.in")
{
    $rows = file(__DIR__ . '/' . $filename);
    
    $arr = [];
    foreach ($rows as $rawRow) {
        $arr[] = explode(' ', $rawRow);
    }
    
    list($V,$E,$R,$C,$X) = $arr[0];
    $videos = array_map('intval',$arr[1]);
    
    $endpoints = array();
    
    $endpointId = 0;
    $currentRow = 2;
    $endpointCount = 0;
    while ($currentRow < count($rows)-1)
    {
        $latencyDataCenter = (int) $arr[$currentRow][0];
        $countCacheConnections  = (int) $arr[$currentRow][1];
        //var_dump($countCacheConnections);
        $currentRow++;
        
        $newendpoint = array('latencyDataCenter' => $latencyDataCenter, 'cache' => array());
        for ($j=0; $j<$countCacheConnections; $j++)
        {
            $newendpoint['cache'][$arr[$currentRow+$j][0]] = (int) $arr[$currentRow+$j][1];
        }
        $endpoints[$endpointId] = $newendpoint;
    
        $currentRow += $countCacheConnections;
        $endpointId++;
        if ($endpointId==$E) break;
    }
    
    while ($currentRow < count($rows)-1)
    {
        list($videoId, $endpointId, $requestsCount) = $arr[$currentRow];
        $endpoints[$endpointId]['requests'][$videoId] =  (int) $requestsCount;
        $currentRow++;
    }

    return array('endpoints' => $endpoints, 'videos' => $videos);
}

var_dump(getVideosEndpoints());
