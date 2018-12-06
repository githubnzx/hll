<?php
/**
 * Created by PhpStorm.
 * User: php_s
 * Date: 2018/5/15
 * Time: 15:29
 */

namespace app\user\controller;

use app\user\model\VersionModel;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;


class Version extends Base
{
    public function code(){
        ob_start();
        $set_log =true;
        $qrCode = new QrCode('http://baidu.com');
        if($set_log ==true){
            $qrCode->setLogoPath(ROOT_PATH."public/upload/20170916/1e915c70dbb9d3e8a07bede7b64e4cff.png");
            $qrCode->setLogoWidth(90);
        }
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH);
        header('Content-Type: '.$qrCode->getContentType());
        echo $qrCode->writeString();
        //这里就是把生成的图片流从缓冲区保存到内存对象上，使用base64_encode变成编码字符串，通过json返回给页面。
        $qrCodeString = base64_encode(ob_get_contents());
        //关闭缓冲区
        ob_end_clean();
        return success_out(["data"=>$qrCodeString]);

        //@去操作这张图片
        //@删除文件
        //unlink($path);
        //exit;
    }

    // 版本
    public function index()
    {
        $info = VersionModel::getInstance()->versionFind(["type"=>VersionModel::VERSION_TYPE], "title, number, version_url, describe, status, encryption");
        return success_out($info ?: []);
    }

}