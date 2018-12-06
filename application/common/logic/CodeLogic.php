<?php

namespace app\common\logic;

/**
 * Created by PhpStorm.
 * User: wangaojie
 * Date: 2017/10/15
 * Time: 上午11:33
 */
class CodeLogic extends BaseLogic
{
    // 生成二维码
    public function qrcode($apk_url, $logo_url){
        ob_start();
        $set_log =true;
        $qrCode = new QrCode($apk_url);
        if($set_log ==true){
            $qrCode->setLogoPath($logo_url);
            $qrCode->setLogoWidth(90);
        }
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH);
//        $name = rand(1,99999999).time();
//        $code_dir  = 'static/qrcode/' . date('Ymd'); // 图片保存目录
//        if (!is_dir($code_dir)) {
//            mkdir($code_dir);
//        }
//        $path = $code_dir . "/" . $name . '.png';
//        $qrCode->writeFile($path);
        header('Content-Type: '.$qrCode->getContentType());
        echo $qrCode->writeString();
        //这里就是把生成的图片流从缓冲区保存到内存对象上，使用base64_encode变成编码字符串，通过json返回给页面。
        $qrCodeString = base64_encode(ob_get_contents());
        //关闭缓冲区
        ob_end_clean();
        //@去操作这张图片
        //@删除文件
        //unlink($path);
        exit;
    }

}