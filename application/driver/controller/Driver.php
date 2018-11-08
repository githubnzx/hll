<?php
namespace app\driver\controller;
use app\common\logic\MsgLogic;
use app\driver\logic\MsgLogic as DriverMsgLogic;
use app\driver\model\IntegralModel;
use app\driver\model\DriverModel;
use app\driver\logic\DriverLogic;
use app\driver\logic\OrderLogic;
use app\user\logic\UserLogic;
use app\common\push\Push;
use app\user\model\UsersModel;
use think\Cache;
use think\Config;


class driver extends Base
{
    /**
     * 函数的含义说明
     *
     * @access  public
     * @author  niuzhenxiang
     * @param mixed token  用户token
     * @return array
     * @date  2018/02/09
     */
    private $integralType = [1=>"收入", 2=>"转出"];
    private $integralOperationType = [1=>"司机注册"];
    // 明细
    private $type        = [1=>'转入', 2=>'转出'];
    private $pay_type    = [0=>"", 1=>'支付宝支付', 2=>'微信支付', 3=>'会员卡支付', 4=>'余额支付'];
    private $type_symbol = [1=>'+', 2=>'-'];
    private $tx_number   = [1=>'d', 2=>'W', 3=>'n', 4=>'Y'];
    private $tx_number_msg = [1=>'本日', 2=>'本周', 3=>'本月', 4=>'本年'];


    // 修改手机号
    public function upUserPhone()
    {
        $user_id = DriverLogic::getInstance()->checkToken();
        $phone = $this->request->post('phone/s', "");
        $code = $this->request->post('code/d', 0);
        if (!$phone || !$code) return error_out('', MsgLogic::PARAM_MSG);
        if (!UserLogic::getInstance()->check_mobile($phone)) {
            return error_out('', UserLogic::USER_SMS_SEND);
        }
        $oldCode = getCache()->get('mobile_code:' . $phone);
        if (!$oldCode) {
            return error_out('', UserLogic::REDIS_CODE_MSG);
        }
        if ($oldCode != $code) {
            return error_out('', UserLogic::CODE_MSG);
        }
        //UserLogic::getInstance()->delTokenPhone($phone);

        $old_user_id = DriverModel::getInstance()->userFind(["phone" => $phone], 'id')["id"] ?: "";
        if ($old_user_id) {
            return error_out('', UserLogic::PHONE_EXISTED);
        }
        $res = DriverModel::getInstance()->userEdit(['id' => $user_id], ['phone' => $phone]);
        if ($res === false) return error_out('', MsgLogic::SERVER_EXCEPTION);
        return success_out('', MsgLogic::EDIT_SUCCESS);
    }

    // 修改登录密码
    public function changePwd()
    {
        $user_id = DriverLogic::getInstance()->checkToken();
        $code    = $this->request->post('code/d', 0);
        $password= $this->request->post('password/s', "");
        if (!$password || !$code) return error_out('', MsgLogic::PARAM_MSG);
        //  获取用户手机号
        $userPhone = DriverModel::getInstance()->userFind(["id" => $user_id], 'phone')["phone"] ?: "";
        if (!$userPhone) return error_out("", DriverMsgLogic::USER_PHONE_NOT_EXTSIS);
        $oldCode = Cache::store('driver')->get('mobile_code:' . $userPhone);
        if (!$oldCode) {
            return error_out('', DriverLogic::REDIS_CODE_MSG);
        }
        if ($oldCode != $code) {
            return error_out('', DriverLogic::CODE_MSG);
        }
        UserLogic::getInstance()->delTokenPhone($userPhone);
        // 修改手机号
        $res = DriverModel::getInstance()->userEdit(["id"=>$user_id], ["password"=>md5(config("user_login_prefix").$password)]);
        if ($res === false) return error_out('', MsgLogic::SERVER_EXCEPTION);
        return success_out('', MsgLogic::EDIT_SUCCESS);
    }

    // 我的积分
    public function userIntegral(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $integral = IntegralModel::getInstance()->userIntegralFind(["user_id"=>$user_id, "user_type"=>DriverModel::USER_TYPE_USER], "integral")["integral"] ?: 0;
        $data["integral"] = $integral;
        $integralRecord = IntegralModel::getInstance()->userIntegralRecordList(["user_id"=>$user_id, "user_type"=>DriverModel::USER_TYPE_USER], "id, integral, type, operation_type, tag");
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
        $user_id = DriverLogic::getInstance()->checkToken();
        return success_out();
    }

    // 完善信息
    public function perfectInfo(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $cityCode = $this->request->post('city_code/s', "");
        $name         = $this->request->post("name/s", "");
        $id_card      = $this->request->post("id_number/s", "");
        $contactsName = $this->request->post("contacts_name/s", "");
        $contactsPhone= $this->request->post("contacts_phone/s", "");
        $carColor     = $this->request->post("car_color/d", 0);
        $carNumber    = $this->request->post("car_number/s", "");
        $carType      = $this->request->post("car_type/d", 0);
        $photo        = $this->request->post("photo/a", []);
        if(!$cityCode || !$name || !$id_card || !$contactsName || !$contactsPhone || !$carColor || !$carNumber || !$carType || empty($photo)) return error_out('', '参数错误');
        if(!UserLogic::getInstance()->check_name($name)) return error_out("", DriverLogic::USER_NAME);          // 用户名验证
        if(!UserLogic::getInstance()->check_id_card($id_card)) return error_out("", DriverLogic::USER_NAME);    // 省份证正则验证
        if(!UserLogic::getInstance()->check_name($contactsName)) return error_out("", DriverLogic::USER_NAME);  // 紧急联系人姓名验证
        if(!UserLogic::getInstance()->check_mobile($contactsPhone)) return error_out('', UserLogic::USER_SMS_SEND);// 紧急联系人电话验证
        if(!DriverLogic::getInstance()->check_vehicle_number($carNumber)) return error_out('', DriverLogic::USER_CAR_NUM);// 车牌号验证
        if (!isset($photo["id_card"]["just"]) || empty($photo["id_card"]["just"])) { // 身份证 正面照验证
            return error_out("", DriverLogic::USER_ID_JUST);
        }
        if (!isset($photo["id_card"]["back"]) || empty($photo["id_card"]["back"])){ // 身份证 反面照验证
            return error_out("", DriverLogic::USER_ID_BACK);
        }
        if(!isset($photo["js_cert"]) || empty($photo["js_cert"])) return error_out("", DriverLogic::USER_JS_CERT); // 驾驶证 验证
        if(!isset($photo["xs_cert"]) || empty($photo["xs_cert"])) return error_out("", DriverLogic::USER_XS_CERT); // 行驶证 验证
        if(!isset($photo["car"]) || empty($photo["car"])) return error_out("", DriverLogic::USER_XS_CERT);          // 车辆照片 验证
        $data["city_code"] = $cityCode;
        $data["id_number"] = $id_card;
        $data["car_color"] = $carColor;
        $data["car_number"]= $carNumber;
        $data["car_type"]  = $carType;
        $data["contacts_name"] = $contactsName;
        $data["contacts_phone"]= $contactsPhone; 
        $data["is_register"]= 1;
        $result = DriverModel::getInstance()->userPerfectInfoEdit($user_id, $data, $photo);
        if($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }

    // 司机详情
    public function driverInfo(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $driverInfo = DriverModel::getInstance()->userFind(["id"=>$user_id]);
        return success_out($driverInfo);
    }

    // 体现
    public function transfer(){
        error_reporting(0);
        $tx_status = false;
        $user_id = DriverLogic::getInstance()->checkToken();
        $price = request()->post('price/f' , 0);
        $password = request()->post('password/s' , "");
        //$payType = request()->post('pay_type/d' , 0); // 1微信 2支付宝
        if(!$price || !$password) return error_out('', MsgLogic::PARAM_MSG);
        // 判断是否微信授权
        $driver = DriverModel::getInstance()->userFind(["id"=>$user_id], 'name, openid, phone, pay_pwd');
        // 判断用户支付密码
        if($driver["pay_pwd"] !== md5($password)) return error_out('', DriverMsgLogic::DRIVER_PAY_PWD);
        if(!is_array($driver) || empty($driver['openid'])){
            return error_out('', DriverMsgLogic::TRANSFER_WX_AUTH);
        }
        if (bccomp($price, 100.00, 2) < 0) {
            return error_out('', DriverMsgLogic::TRANSFER_WX_MIN_PRICE);
        }
        // 查询余额是否满足体现金额
        $result = DriverModel::getInstance()->balanceInfoById($user_id);
        if(!$result || bccomp($result['balance'], $price, 2) < 0) return error_out('', DriverMsgLogic::DRIVER_PRICE_LESS);
        // 教练余额
        $balance_total = bcsub($result['balance'], $price, 2);
        $balance_res = DriverModel::getInstance()->addBillAndTxBalance($user_id, $result['id'], $balance_total, $price, DriverModel::TYPE_OUT, 1, '提现', 1);
        if($balance_res){
            // 发送消息
            //$msg['name'] = $coach['title'];
            //$msg['money']= $price;
            //CoachSms::withdrawal($coach['phone'], $msg);
            return success_out('', '已处理');
        } else {
            return error_out('', MsgLogic::SERVER_EXCEPTION);
        }
    }

    // 充值
    public function recharge() {
        error_reporting(0);
        $user_id = DriverLogic::getInstance()->checkToken();
        $price   = request()->post('price/f' , 0);
        $password= request()->post('pay_pwd/s' , "");
        $payType = request()->post('pay_type/d' , 0); // 1微信 2支付宝
        if(!$price || !$password || !$payType) return error_out('', MsgLogic::PARAM_MSG);
        // 验证金额
        if (bccomp($price, 10.00, 2) < 0) {
            return error_out('', DriverMsgLogic::RECHARGE_MIN_PRICE);
        }
        // 充值金额有误
        if (bccomp($price, intval($price), 2) !== 0 || intval($price) % 10 !== 0) {
            return error_out('', DriverMsgLogic::PRICE_MISTAKEN);
        }
        $data["user_id"]   = $user_id;
        $data["user_type"] = DriverModel::USER_TYPE_USER;
        $data["price"]     = $price;
        $data["code"]      = OrderLogic::getInstance()->makeCode();
        $data["pay_type"]  = $payType;
        $data["status"]    = DriverModel::STAY_PAY;
        $order_id = DriverModel::getInstance()->rechargeOrderInsert($data);
        var_dump($order_id);die;
        if ($order_id === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        if ($payType === 1) { // 微信支付
            $data["wxData"] = OrderLogic::getInstance()->payWx($data['code'], $data['price'], url('Driver/pay/notifyWxRecharge', '', true, true), "APP");//亟亟城运会员购买
        } else {  // 支付宝支付

        }
        return success_out($data);
    }




}
