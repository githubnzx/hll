<?php
namespace app\user\controller;
use app\common\logic\MsgLogic;
use app\user\model\IntegralModel;
use app\user\model\UsersModel;
use app\user\logic\UserLogic;
use app\user\logic\MsgLogic as UserMsgLogic;
use app\user\model\MyModel;
use app\common\logic\PageLogic;
use app\common\push\Push;
use think\Cache;
use think\Config;


class my extends Base
{
    private $type        = [1=>'转入', 2=>'转出'];
    private $title       = [1=>'提现'];
    //private $pay_type   = [0=>"", 1=>'支付宝支付', 2=>'微信支付', 3=>'会员卡支付', 4=>'余额支付'];
    private $type_symbol = [1=>'+', 2=>'-'];
    // 账户余额
    public function account(){
        $user_id = UserLogic::getInstance()->checkToken();
        $balance = MyModel::getInstance()->balanceFind(["user_id"=>$user_id, "user_type"=>MyModel::USER_TYPE_USER, "status"=>MyModel::STATUS], "balance")["balance"] ?: "0.00";
        $userInfo = UsersModel::getInstance()->userFind(["id"=>$user_id], "name, phone, icon");
        if(!$userInfo) return error_out("", UserMsgLogic::USER_NOT_EXCEED);
        $userInfo["name"] = $userInfo["name"] ?: "";
        $userInfo["icon"] = handleImgPath($userInfo["icon"]);
        $userInfo["balance"] = $balance;
        return success_out($userInfo);
    }

    // 设置支付密码
    public function setPayPwd()
    {
        $user_id = UserLogic::getInstance()->checkToken();
        $password = $this->request->param('password/s', "");
        $repeat_pwd = $this->request->param('repeat_pwd/s', "");
        if (!$password || !$repeat_pwd) return error_out('', MsgLogic::PARAM_MSG);
        if ($password !== $repeat_pwd) return error_out('', UserMsgLogic::USER_REPEAT_PWD);
        $password = md5($password);//md5(config::get("pay_password").$password);
        $res = UsersModel::getInstance()->userEdit(['id' => $user_id], ["pay_pwd" => $password]);
        if ($res === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }

    // 明细
    public function record(){
        $user_id = UserLogic::getInstance()->checkToken();
        //$pages = PageLogic::getInstance()->getPages();
        $balance = UsersModel::getInstance()->balanceFind(["user_id"=>$user_id, "user_type"=>UsersModel::USER_TYPE_USER], "balance");
        $data['balance'] = $balance['balance'] ?: '0.00';
        $fields = 'id, user_id, driver_id, balance, type, pay_type, type_status, status, tag, price, date, update_time';
        $bill = UsersModel::getInstance()->billList(['driver_id'=>$user_id, 'user_type'=>UsersModel::USER_TYPE_USER], "", $fields);
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
