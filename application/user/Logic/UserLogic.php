<?php

namespace app\user\logic;
use think\exception\HttpException;
use think\Cache;
use think\Request;
use think\Db;

class UserLogic extends BaseLogic
{


    const USER_PWD_MSG = "手机号或密码错误";
    const USER_STATUS  = "用户已被禁用";
    const USER_SMS_SEND= "请输入正确的手机号";
    const USER_SMS_FAIL= "发送失败";



    const COACH_USER_TYPE = 1;


    public function checkToken()
    {
        $token = Request::instance()->header('token', '');
        if ($token) {
            $user_id = Cache::store('user')->get('user_token:' . $token, 0);
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
        if ($token) $user_id = Cache::store('user')->get('user_token:' . $token, 0);
        return $user_id ?: 0;
    }

    public function getToken($user_id, $is_refresh = false)
    {
        $user_token = Cache::store('user')->get('user_id:' . $user_id);
        $expire = 60*60*24*10;
        if (!$is_refresh) {
            $this->delDeviceId($user_id);
            Cache::store('user')->rm('user_token:' . $user_token);
            $user_token = str_shuffle(md5(str_shuffle(microtime(true))));
        }
        Cache::store('user')->set('user_token:' . $user_token, $user_id, $expire);
        Cache::store('user')->set('user_id:' . $user_id, $user_token, $expire);
        return $user_token;
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
        
        $user_token = Cache::store('user')->pull('user_id:' . $user_id);
        return Cache::store('user')->rm('user_token:' . $user_token);
    }

    public function delTokenPhone($phone){
        Cache::store('user')->rm('mobile_code:' . $phone);
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
}