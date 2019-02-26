<?php

namespace app\driver\logic;

use app\driver\model\DriverModel;
use think\exception\HttpException;
use think\Cache;
use think\Request;
use think\Db;

class DriverLogic extends BaseLogic
{


    const USER_PWD_MSG   = "手机号或密码错误";
    const USER_STATUS    = "用户已被禁用";
    const USER_SMS_SEND  = "请输入正确的手机号";
    const USER_SMS_FAIL  = "发送失败";
    const USER_PHONE_MSG = "手机号错误";
    const PASSWORD_MSG   = "两次密码不一致";
    const PWD_FOORMAT    = "密码格式不正确";
    const HAS_REGISTER   = "该手机号已注册";
    const CODE_MSG       = "验证码错误，请重新输入";
    const REDIS_CODE_MSG = "验证码已过期，请重新获取验证码";
    const USER_OUT       = "退出成功";
    const WECHAT_CODE    = "微信凭证不能为空";
    const WECHAT_CODE_EXISTS = "此账号已绑定微信";
    const USER_IS_DEL    = "用户已被禁用";
    const WECHAT_BINDINGS= "此手机号已绑定过微信";
    const WX_BIND_SUCCESS= "绑定手机号成功";
    const LOGIN_SUCCESS  = "登录成功";
    const PHONE_EXISTED  = "此手机号已注册，请重新输入";
    const DRIVER_NOT_EXISTS = "此用户不存在";

    const USER_NAME      = "用户名称格式错误";
    const USER_ID_CARD   = "用户身份证错误";
    const USER_CAR_NUM   = "车牌号错误";
    const USER_ID_JUST   = "身份证正面照必填";
    const USER_ID_BACK   = "身份证反面照必填";
    const USER_JS_CERT   = "驾驶证件照必填";
    const USER_XS_CERT   = "行驶证件照必填";
    const USER_CAR       = "车辆照片必填";

    const ZFB_AUTH_CODE  = "支付宝凭证不能为空";





    const COACH_USER_TYPE = 1;


    public function checkToken()
    {
        $token = Request::instance()->header('token', '');
        if ($token) {
            $user_id = getCache()->get('user_token:' . $token, 0);
            if (!$user_id) {
                throw new HttpException(401, '用户令牌已过期');
            }
        } else {
            throw new HttpException(403, '用户令牌不存在');
        }
        return $user_id;
    }

    public function getUserID()
    {
        $user_id = 0;
        $token = Request::instance()->header('token', '');
        if ($token) $user_id = getCache()->get('user_token:' . $token, 0);
        return $user_id ?: 0;
    }

    public function getToken($user_id, $is_refresh = false)
    {
        $user_token = getCache()->get('user_id:' . $user_id);
        if ($user_token) {
            getCache()->rm('user_token:' . $user_token);
        }
        DriverModel::getInstance()->delDeviceId($user_id);
        $user_token = str_shuffle(md5(str_shuffle(microtime(true))));
        getCache()->set('user_token:' . $user_token, $user_id);
        getCache()->set('user_id:' . $user_id, $user_token);
        return $user_token;


        //$expire = 60*60*24*10;
//        if (!$is_refresh) {
//            $this->delDeviceId($user_id);
//            getCache()->rm('user_token:' . $user_token);
//            $user_token = str_shuffle(md5(str_shuffle(microtime(true))));
//        }
//        getCache()->set('user_token:' . $user_token, $user_id, $expire);
//        getCache()->set('user_id:' . $user_id, $user_token, $expire);

        /*if (!$user_token || $is_refresh) {
            $expire = 60*60*24*10;
            if (!$user_token) {
                $user_token = str_shuffle(md5(str_shuffle(microtime(true))));
            }
            Cache::store('user')->set('user_token:' . $user_token, $user_id, $expire);
            Cache::store('user')->set('user_id:' . $user_id, $user_token, $expire);
        }*/
    }

    public function delDeviceId($user_id){
        $where['user_id'] = $user_id;
        $where['user_type'] = self::COACH_USER_TYPE;
        return Db::table($this->client_push)->where($where)->delete();
    }

    public function delToken($user_id)
    {
        
        $user_token = getCache()->pull('user_id:' . $user_id);
        return getCache()->rm('user_token:' . $user_token);
    }

    public function delTokenPhone($phone){
        getCache()->rm('mobile_code:' . $phone);
    }

    public function check_mobile($mobile)
    {
        return preg_match("/^((\\(\\d{2,3}\\))|(\\d{3}\\-))?\\s*(13|14|15|16|17|18)\\d{9}$/", $mobile);
    }

    // 课件名称 只包含中英文数字
    public function check_name($name){
        //$preg_name='/^[\x{4e00}-\x{9fa5}]{1,10}$|^[a-zA-Z\s]*[a-zA-Z\s]{1,20}$/isu';
        $preg_name='/^[\x{4e00}-\x{9fa5}a-zA-Z]{1,10}$/isu';
        if(preg_match($preg_name, $name)){
            return true;
        } else {
            return false;
        }
    }
    // 密码验证
    public function check_password($password){
        $preg_name='/^[A-Za-z0-9]{6,11}$/isu';
        if(preg_match($preg_name, $password)){
            return true;
        } else {
            return false;
        }
    }
    // 课件名称 只包含中英文数字
    public function check_user_name($name)
    {
        $preg_name = '/^[\x{4e00}-\x{9fa5}a-zA-Z]{1,10}$/isu';
        if (preg_match($preg_name, $name)) {
            return true;
        } else {
            return false;
        }
    }

    //获取定位用户所在城市编码
    public function getCityCode()
    {
        return request()->header('city') ?: config('default_city.code');
    }

    // 车牌号验证
    public function check_vehicle_number($car_number){
        // /[\x80-\xff][A-Z][a-z0-9]{5}/i
        $vehicleNumber = "/^[京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领A-Z]{1}[A-Z]{1}[A-Z0-9]{4}[A-Z0-9挂学警港澳]{1}$/isu";
        if (preg_match($vehicleNumber, $car_number)) {
            return true;
        } else {
            return false;
        }
    }
}