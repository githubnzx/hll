<?php
/**
 * Created by PhpStorm.
 * User: php_s
 * Date: 2018/5/15
 * Time: 15:29
 */

namespace app\user\controller;

use app\user\model\;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;


class Download extends Base
{
    public function code(){
        $set_log =true;
        $qrCode = new QrCode('http://baidu.com');
        if($set_log ==true){
            $qrCode->setLogoPath(ROOT_PATH."public/upload/20170916/1e915c70dbb9d3e8a07bede7b64e4cff.png");
            $qrCode->setLogoWidth(90);
        }
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH);
        $name = rand(1,99999999).time();
        $code_dir  = 'static/qrcode/' . date('Ymd'); // 图片保存目录
        if (!is_dir($code_dir)) {
            mkdir($code_dir);
        }
        $path = $code_dir . "/" . $name . '.png';
        $qrCode->writeFile($path);
        header('Content-Type: '.$qrCode->getContentType());
        echo $qrCode->writeString();
        //@去操作这张图片
        //@删除文件
        //unlink($path);
        exit;
    }

}