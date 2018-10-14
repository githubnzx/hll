<?php
namespace app\driver\controller;
use app\common\logic\MsgLogic;
use app\driver\model\IntegralModel;
use app\driver\model\DriverModel;
use app\driver\logic\DriverLogic;
use app\driver\logic\MsgLogic as DriverMsgLogic;
use app\driver\model\MyModel;
use app\user\logic\UserLogic;
use app\common\push\Push;
use think\Cache;
use think\Config;


class my extends Base
{
    // 账户余额
    public function account(){
        $driver_id = DriverLogic::getInstance()->checkToken();
        $balance = MyModel::getInstance()->balanceFind(["user_id"=>$driver_id, "user_type"=>MyModel::USER_TYPE, "status"=>MyModel::STATUS], "balance")["balance"] ?: "0.00";
        $driverInfo = DriverModel::getInstance()->userFind(["id"=>$driver_id], "name, phone, icon");
        if(!$driverInfo) return error_out("", DriverMsgLogic::DRIVER_NOT_EXCEED);
        $driverInfo["balance"] = $balance;
        return success_out($driverInfo);
    }






}
