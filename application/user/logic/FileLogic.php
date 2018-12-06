<?php
namespace app\user\logic;

use think\Config;
use think\Image;

/**
 * Created by PhpStorm.
 * User: nzx
 * Date: 2018/09/08
 * Time: 13:33
 */
class FileLogic extends BaseLogic
{
    public function uploadOne($file, $thumb = null)
    {
        if (!$file) {
            return ['code' => 0, 'message' => '文件不存在', 'url' => ''];
        }

//        $base_dir = ROOT_PATH . 'public';
        $base_dir = Config::get('img.path');
        $save_dir  = DS .'images' . DS . date('Ymd'); // 图片保存目录

        $info = $file->getInfo();
        $names = explode('.' , $info['name']);
        if (isset($names[1])) {
            $file_ext = $names[1];
        } else {
            $file_type = explode('/', $info['type']);
            $file_ext = $file_type[1];
        }
        $file_name = uniqid() . '.' . $file_ext;
        $result = $file->move($base_dir . $save_dir, $file_name);
        if ($result) {
            $url = $save_dir . DS . $file_name;
            $data = ['url' => $url , 'code' => 1, 'message' => ''];
            if ($thumb) { //生成缩略图
                $imgURI = $base_dir . $url;
                $image = Image::open($imgURI);
                $thumb_url = str_replace('.', '-thumb.', $url);
                $image->thumb($thumb[0], $thumb[1], Image::THUMB_FIXED)->save($base_dir . $thumb_url);
                $data['thumb'] = $thumb_url;
            }
        } else {
            $data = ['url' => '' , 'code' => 1, 'message' => $file->getError()];
        }
        return $data;
    }

    public function uploadExcel($file)
    {
        if (!$file) {
            return ['code' => 0, 'message' => '文件不存在', 'url' => ''];
        }
        $base_dir = Config::get('img.path');
        $save_dir  = DS .'upload' . DS . date('Ymd'); // 图片保存目录
        $info = $file->getInfo();
        $names = explode('.' , $info['name']);
        if (isset($names[1])) {
            $file_ext = $names[1];
        } else {
            $file_type = explode('/', $info['type']);
            $file_ext = $file_type[1];
        }
        if (strtolower($file_ext) != "xlsx" && strtolower($file_ext) != "xls") {
             $data = ['url' => '' , 'code' => 1, 'message' => "不是Excel文件，重新上传"];
             return $data;
        }
        $file_name = uniqid() . '.' . $file_ext;
        $result = $file->move($base_dir . $save_dir, $file_name);
        if ($result) {
            $url = $save_dir . DS . $file_name;
            $data = ['url' => $url , 'code' => 1, 'message' => ''];
        } else {
            $data = ['url' => '' , 'code' => 1, 'message' => $file->getError()];
        }
        return $data;

    }

    public function addWaterFile($file, $waterFile, $locate = [], $replace = false)
    {
        $base_path = config('img.path');
        $image = Image::open($base_path . $file);
        if ($replace) {
            $water_url = $file;
        } else {
            $water_url = str_replace('.', '-water.', $file);
        }
        $image->water($base_path . $waterFile, $locate)->save($base_path . $water_url);
        return $water_url;
    }

    public function uploadMultiFiles($files) {
        if (is_array($files)) {
            $urls = [];
            $msg = '';

            foreach ($files as $key => $value) {
                $singleUploadInof = $this->uploadOne($value);
                if ($url = $singleUploadInof['url']) {
                    $urls[] = $url;
                } else {
                    $msg .= '第' . $key . '张' . $singleUploadInof['message'];
                }
            }
            $data = [
                'url' => $urls,
                'code' => 1,
                'message' => $msg,
            ];
        } else {
            $data = $this->uploadOne($files);
        }

        return $data;
    }
}
