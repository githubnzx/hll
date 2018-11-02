<?php

namespace app\user\logic;

use think\Cache;
use think\Config;
use think\exception\HttpException;
use think\Loader;
use think\Request;


class QqLogic extends BaseLogic
{
    protected $app_id = 'wx72aa412d243f6cda';
    protected $app_secret = '04e910edf2d8c0edad3c6952d4ffb53a';

    // 获取 Authorization Code
    public function getAuthCode(){
        $url = "https://graph.qq.com/oauth2.0/authorize?";
        $data = "response_type=code&client_id=".$this->app_id."&client_secret=".$this->app_secret."&state=test&redirect_uri=" . Config::set("qq.redirect_uri");
        $full_url = $url . $data;
        return $this->send($full_url);
    }
    // 获取 Access Token
    public function getToken($code){
        //"https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&client_id=[YOUR_APP_ID]&client_secret=[YOUR_APP_Key]&code=[The_AUTHORIZATION_CODE]&state=[The_CLIENT_STATE]&redirect_uri=[YOUR_REDIRECT_URI]";
        $url = 'https://graph.qq.com/oauth2.0/token?';
        $data = "grant_type=authorization_code&client_id=".$this->app_id."&client_secret=".$this->app_secret."&code=".$code."&redirect_uri=" . $this->redirect_uri;
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