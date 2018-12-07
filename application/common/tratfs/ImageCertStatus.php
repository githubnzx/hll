<?php
/**
 * Created by PhpStorm.
 * User: wangfeng
 * Date: 2018/6/11
 * Time: 下午4:58
 */
namespace app\common\tratfs;

Interface  ImageCertStatus
{
    CONST ID_FRONT_TYPE = 1; //身份证正面
    CONST ID_BACK_TYPE  = 2; //身份证反面
    CONST CERT_TYPE     = 3; //服务类型证书
    CONST PEOPLE_TYPE   = 4; //个人照片
    CONST COURSE_TYPE   = 5; //课件照片
    CONST VENUE_TYPE    = 6; //场地照片
}