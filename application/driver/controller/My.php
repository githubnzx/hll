<?php
namespace app\driver\controller;
use app\common\logic\MsgLogic;
use app\driver\model\IntegralModel;
use app\driver\model\DriverModel;
use app\driver\logic\DriverLogic;
use app\driver\logic\MsgLogic as DriverMsgLogic;
use app\driver\model\MyModel;
use app\user\logic\UserLogic;
use app\common\logic\PageLogic;
use app\common\push\Push;
use think\Cache;
use think\Config;


class my extends Base
{
    private $type        = [1=>'转入', 2=>'转出'];
    private $title       = [1=>'提现', 2=>"充值"];
    //private $pay_type   = [0=>"", 1=>'支付宝支付', 2=>'微信支付', 3=>'会员卡支付', 4=>'余额支付'];
    private $type_symbol = [1=>'+', 2=>'-'];
    // 账户余额
    public function account(){
        $driver_id = DriverLogic::getInstance()->checkToken();
        $balance = MyModel::getInstance()->balanceFind(["user_id"=>$driver_id, "user_type"=>MyModel::USER_TYPE_USER, "status"=>MyModel::STATUS], "balance")["balance"] ?: "0.00";
        $driverInfo = DriverModel::getInstance()->userFind(["id"=>$driver_id], "name, phone, icon");
        if(!$driverInfo) return error_out("", DriverMsgLogic::DRIVER_NOT_EXCEED);
        $driverInfo["name"] = $driverInfo["name"] ?: "";
        $driverInfo["icon"] = handleImgPath($driverInfo["icon"]);
        $driverInfo["balance"] = $balance;
        return success_out($driverInfo);
    }

    // 设置支付密码
    public function setPayPwd()
    {
        $driver_id = DriverLogic::getInstance()->checkToken();
        $password = $this->request->param('password/s', "");
        $repeat_pwd = $this->request->param('repeat_pwd/s', "");
        if (!$password || !$repeat_pwd) return error_out('', MsgLogic::PARAM_MSG);
        if ($password !== $repeat_pwd) return error_out('', DriverMsgLogic::DRIVER_REPEAT_PWD);
        $password = md5($password);//md5(config::get("pay_password").$password);
        $res = DriverModel::getInstance()->userEdit(['id' => $driver_id], ["pay_pwd" => $password]);
        if ($res === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }

    // 明细
    public function record(){
        $user_id = DriverLogic::getInstance()->checkToken();
        //$pages = PageLogic::getInstance()->getPages();
        $balance = DriverModel::getInstance()->balanceFind(["user_id"=>$user_id, "user_type"=>DriverModel::USER_TYPE_USER], "balance");
        $data['balance'] = $balance['balance'] ?: '0.00';
        $fields = 'id, user_id, driver_id, balance, type, pay_type, type_status, status, tag, price, date, update_time';
        $bill = DriverModel::getInstance()->billList(['driver_id'=>$user_id, 'user_type'=>DriverModel::USER_TYPE_USER], "create_time desc", $fields);
        $dataAll = [];
        foreach ($bill as $key => $val){
            if (!isset($dataAll[$val["date"]])) {
                if (date("m") === date("m", $val["date"])) {
                    $month = "本月";
                } else {
                    $month = date("Y-m", $val["date"]);
                }
                $dataAll[$val["date"]]["date"] = $month;
            }
            $_date['date']   = date('Y-m-d H:i', $val['update_time']);
            $_date['title']  = $this->title[$val["type_status"]];
            $_date['price']  = $val['price'] ? $this->type_symbol[$val['type']].$val['price']: '0.00';
            $_date['balance']= $val['balance'] ?: '0.00';
            $dataAll[$val["date"]]["list"][] = $_date;
        }
        return success_out($dataAll);
    }






}
