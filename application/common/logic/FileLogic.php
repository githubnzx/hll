<?php

namespace app\common\logic;

/**
 * Created by PhpStorm.
 * User: wangaojie
 * Date: 2017/10/15
 * Time: 上午11:33
 */
class FileLogic extends BaseLogic
{
    private $relative_path;

    public function copyUri($url, $file_name, $dir = 'temp')
    {
//        $file_name = uniqid() . '.jpg';
        $path = $this->setSaveDir($dir);
        $save_path = $path . DS . $file_name;
        $cp = curl_init($url);
        $fp = fopen($save_path,"w");
        curl_setopt($cp, CURLOPT_FILE, $fp);
        curl_setopt($cp, CURLOPT_HEADER, 0);
        curl_exec($cp);
        curl_close($cp);
        fclose($fp);
        return str_replace(config('img.path'), '', $save_path);
    }

    public function writeContent($content, $file_name, $dir = 'temp')
    {
        $path = $this->setSaveDir($dir);
        $save_file = $path . DS . $file_name;
        file_put_contents($save_file, $content);
        return $this->relative_path . DS . $file_name;
    }

    public function setSaveDir($dir)
    {
        $this->relative_path = DS. 'upload' .DS. $dir .DS. date('Ymd');
        $dir_name = config('img.path') . $this->relative_path;
        if (file_exists($dir_name)) {
            return $dir_name;
        } else {
            mkdir($dir_name , 0777, true);
            return $dir_name;
        }
    }
}