<?php
/**
 * Created by PhpStorm.
 * User: wangfeng
 * Date: 2018/2/12
 * Time: 下午1:54
 */

namespace app\common\model;


use think\Db;
use think\Exception;

class ServiceReceiveModel extends BaseModel
{
    //protected $tableName = 'service_receive';

    // 用户类型 1司机 2 用户
    const USER_TYPE_DRIVER = 2;
    const USER_TYPE_USER  = 1;


}