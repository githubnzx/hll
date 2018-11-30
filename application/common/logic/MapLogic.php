<?php

namespace app\common\logic;

use think\Cache;
use think\Config;
use think\exception\HttpException;
use think\Loader;
use think\Request;


class MapLogic extends BaseLogic
{
    protected $mapAK = "R703NGuybtNY91wR9L8kpG0e5ZRVk5oG";

    // 驾车规划请求
    public function driveKilometre($longitude, $dimension){//40.01116,116.339303 39.936404,116.452562
        $url = 'http://api.map.baidu.com/direction/v2/driving?';
        $data = "origin=". $longitude ."&destination=". $dimension ."&ak=". $this->mapAK;
        $full_url = $url . $data;
        return $this->send($full_url);
    }

    private function send($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $json = curl_exec($ch);
        curl_close($ch);
        return json_decode($json, true);
    }

}