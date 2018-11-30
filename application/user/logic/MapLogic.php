<?php

namespace app\user\logic;

use think\Cache;
use think\Config;
use think\exception\HttpException;
use think\Loader;
use think\Request;


class MapLogic extends BaseLogic
{
    protected $mapAK = "R703NGuybtNY91wR9L8kpG0e5ZRVk5oG";// '05IlacfRaHDPtRDCsXc7yKtCoG2QoW7H';

/*
    public function check_access_token($access_token,$openid)
    {
        $url = 'https://api.weixin.qq.com/sns/auth?access_token='.$access_token.'&openid='.$openid;
        return $this->send($url);
    }
    public function refresh_token($refresh_token)
    {
        $url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid='.$this->app_id.'&grant_type=refresh_token&refresh_token='.$refresh_token;
        return $this->send($url);
    }

    public function getToken($code)
    {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?';
        $data = "appid=".$this->app_id."&secret=".$this->app_secret."&code=".$code."&grant_type=authorization_code";
        $full_url = $url . $data;
        return $this->send($full_url);
    }

    public function getUserInfo($access_token, $openid)
    {
        $url = 'https://api.weixin.qq.com/sns/userinfo?';
        $data = "access_token=".$access_token."&openid=$openid";
        $full_url = $url . $data;
        return $this->send($full_url);
    }

    public function downloadAvar($headimgurl)
    {
        $file_name = uniqid() . '.jpg';
        $path = $this->setSaveDir();
        $save_path = $path . DS . $file_name;
        $cp = curl_init($headimgurl);
        $fp = fopen($save_path,"w");
        curl_setopt($cp, CURLOPT_FILE, $fp);
        curl_setopt($cp, CURLOPT_HEADER, 0);
        curl_exec($cp);
        curl_close($cp);
        fclose($fp);
        return str_replace(Config::get('img.path'), '', $save_path);
    }

    public function setSaveDir()
    {
        $dir_name = Config::get('img.path') .DS. 'images' .DS. 'weixin' .DS. date('Ymd');
        if (file_exists($dir_name)) {
            return $dir_name;
        } else {
            mkdir($dir_name , 0777, true);
            return $dir_name;
        }
    }*/

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