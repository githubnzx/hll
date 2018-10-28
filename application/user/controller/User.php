<?php
namespace app\user\controller;
use app\admin\model\UserModel;
use app\common\logic\MsgLogic;
use app\user\model\IntegralModel;
use app\user\model\UsersModel;
use app\user\logic\UserLogic;
use app\user\logic\OrderLogic;
use app\user\logic\MsgLogic as UserMsgLogic;
use app\common\push\Push;
use think\Cache;
use think\Config;


class User extends Base
{
    /**
     * 函数的含义说明
     *
     * @access  public
     * @author  niuzhenxiang
     * @param mixed token  用户tokenupUserPhone
     * @return array
     * @date  2018/02/09
     */
    private $integralType = [1=>"收入", 2=>"转出"];
    private $integralOperationType = [1=>"用户注册", 2=>"订单", 3=>"积分购买"];

    // 修改手机号
    public function upUserPhone()
    {
        $user_id = UserLogic::getInstance()->checkToken();
        $phone = $this->request->post('phone/s', "");
        $code = $this->request->post('code/d', 0);
        if (!$phone || !$code) return error_out('', MsgLogic::PARAM_MSG);
        if (!UserLogic::getInstance()->check_mobile($phone)) {
            return error_out('', UserLogic::USER_SMS_SEND);
        }
        $oldCode = Cache::store('user')->get('mobile_code:' . $phone);
        if (!$oldCode) {
            return error_out('', UserLogic::REDIS_CODE_MSG);
        }
        if ($oldCode != $code) {
            return error_out('', UserLogic::CODE_MSG);
        }
        UserLogic::getInstance()->delTokenPhone($phone);

        $old_user_id = UsersModel::getInstance()->userFind(["phone" => $phone], 'id')["id"] ?: "";
        if ($old_user_id) {
            return error_out('', UserLogic::PHONE_EXISTED);
        }
        $res = UsersModel::getInstance()->userEdit(['id' => $user_id], ['phone' => $phone]);
        if ($res === false) return error_out('', MsgLogic::SERVER_EXCEPTION);
        return success_out('', MsgLogic::EDIT_SUCCESS);
    }

    // 修改登录密码
    public function changePwd()
    {
        $user_id = UserLogic::getInstance()->checkToken();
        $code    = $this->request->post('code/d', 0);
        $password= $this->request->post('password/s', "");
        if (!$password || !$code) return error_out('', MsgLogic::PARAM_MSG);
        //  获取用户手机号
        $userPhone = UsersModel::getInstance()->userFind(["id" => $user_id], 'phone')["phone"] ?: "";
        if (!$userPhone) return error_out("", UserMsgLogic::USER_PHONE_NOT_EXTSIS);
        $oldCode = Cache::store('user')->get('mobile_code:' . $userPhone);
        if (!$oldCode) {
            return error_out('', UserLogic::REDIS_CODE_MSG);
        }
        if ($oldCode != $code) {
            return error_out('', UserLogic::CODE_MSG);
        }
        UserLogic::getInstance()->delTokenPhone($userPhone);
        // 修改手机号
        $res = UsersModel::getInstance()->userEdit(["id"=>$user_id], ["password"=>md5(config("user_login_prefix").$password)]);
        if ($res === false) return error_out('', MsgLogic::SERVER_EXCEPTION);
        return success_out('', MsgLogic::EDIT_SUCCESS);
    }

    // 我的积分
    public function userIntegral(){
        $user_id = UserLogic::getInstance()->checkToken();
        $integral = IntegralModel::getInstance()->userIntegralFind(["user_id"=>$user_id, "user_type"=>UsersModel::USER_TYPE_USER], "integral")["integral"] ?: 0;
        $data["integral"] = $integral;
        $integralRecord = IntegralModel::getInstance()->userIntegralRecordList(["user_id"=>$user_id, "user_type"=>UsersModel::USER_TYPE_USER], "id, integral, type, operation_type, tag");
        foreach ($integralRecord as $key => &$val){
            $val["type_title"] = $this->integralType[$val["type"]];
            $val["operation_type_title"] = $this->integralOperationType[$val["operation_type"]];
            unset($val["type"], $val["operation_type"]);
        }
        $data["rcord"] = $integralRecord ?: [];
        return success_out($data);
    }

    // 检测是否登录
    public function hasLogin(){
        $user_id = UserLogic::getInstance()->checkToken();
        return success_out();
    }

    // 用户详情
    public function userInfo(){
        $user_id = UserLogic::getInstance()->checkToken();
        $userInfo = UsersModel::getInstance()->userFind(["id"=>$user_id]) ?: [];
        return success_out($userInfo);
    }

    // 充值
    public function recharge() {
        error_reporting(0);
        $user_id = UserLogic::getInstance()->checkToken();
        $price   = request()->post('price/f' , 0);
        $password= request()->post('pay_pwd/s' , "");
        $payType = request()->post('pay_type/d' , 0); // 1微信 2支付宝
        if(!$price || !$password || !$payType) return error_out('', MsgLogic::PARAM_MSG);
        // 验证金额
        if (bccomp($price, 10.00, 2) < 0) {
            return error_out('', UserMsgLogic::RECHARGE_MIN_PRICE);
        }
        // 充值金额有误
        if (bccomp($price, intval($price), 2) !== 0 || intval($price) % 10 !== 0) {
            return error_out('', UserMsgLogic::PRICE_MISTAKEN);
        }
        $data["user_id"]   = $user_id;
        $data["user_type"] = UsersModel::USER_TYPE_USER;
        $data["price"]     = $price;
        $data["code"]      = OrderLogic::getInstance()->makeCode();
        $data["pay_type"]  = $payType;
        $data["status"]    = UsersModel::STAY_PAY;
        $order_id = UsersModel::getInstance()->rechargeOrderInsert($data);
        var_dump($order_id);die;
        if ($order_id === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        if ($payType === 1) { // 微信支付
            $data["wxData"] = OrderLogic::getInstance()->payWx($data['code'], $data['price'], url('user/pay/notifyWxRecharge', '', true, true), "APP");//亟亟城运会员购买
        } else {  // 支付宝支付

        }
        return success_out($data);
    }





}
