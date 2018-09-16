<?php
namespace app\driver\controller;
use app\common\logic\MsgLogic;
use app\driver\model\IntegralModel;
use app\driver\model\DriverModel;
use app\driver\logic\DriverLogic;
use app\user\logic\UserLogic;
use app\common\push\Push;
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





}
