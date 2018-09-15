<?php
namespace app\user\controller;
use app\common\logic\MsgLogic;
use app\user\model\IntegralModel;
use app\user\model\UsersModel;
use app\user\logic\UserLogic;
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
     * @param mixed token  用户token
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





}
