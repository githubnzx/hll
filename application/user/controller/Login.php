<?php
namespace app\user\controller;

use app\user\logic\UserLogic;
use app\user\model\UsersModel;

use think\Cache;
use think\Db;
use think\Exception;
use think\exception\HttpException;
class Login extends Base
{

    // 登陆
    public function index(){
        $phone    = $this->request->post('phone/s', "");
        $password = $this->request->post('password/s', "");
        if(!$phone || !$password) return error_out('', '参数错误');
        if (!UserLogic::getInstance()->check_mobile($phone)) {
            return error_out('', UserLogic::USER_SMS_SEND);
        }
        $userInfo = UsersModel::getInstance()->userFind(["phone"=>$phone], "id, password, status");
        if(!$userInfo) return error_out("", UserLogic::USER_PWD_MSG);
        if($userInfo["status"]) return error_out("", UserLogic::USER_STATUS);
        if($userInfo["password"] === md5($password)) return error_out('', UserLogic::USER_PWD_MSG);
        $user_token = UserLogic::getInstance()->getToken($userInfo["id"]);
        return success_out([
            'user_token' => $user_token,
            'phone' => $phone,
        ], '登录成功');
    }

    // 发送短信
    public function sendCode()
    {
        $phone = $this->request->post('phone/s', "13601155207");
        if (!UserLogic::getInstance()->check_mobile($phone)) {
            return error_out('', UserLogic::USER_SMS_SEND);
        }

        $code = 333333;
        Cache::store('user1')->set('mobile_code:' . $phone, $code, 30);
        var_dump(Cache::store('user1')->get('mobile_code:' . $phone));
        return success_out('', '发送成功');
        /*
        $code = rand(100000 , 999999);
        $cache_result = Cache::store('user')->set('mobile_code:' . $phone, $code, 300);
        if ($cache_result !== true) return error_out('',UserLogic::USER_SMS_FAIL);
        $templateParam  = ['code'=>$code];
        $response = UserSms::code($phone , $templateParam);
        if ($response->Code == 'OK') {
            Cache::store('user')->set('mobile_code:' . $phone, $code, 300);
            return success_out('', '发送成功');
        } elseif ($response->Code == 'isv.BUSINESS_LIMIT_CONTROL') {
            return error_out('', '当前账户频率操作过快 请稍后重试');
        } else {
            return error_out('', '服务器异常');
        }*/
    }
    // 注册
    public function register(){

    }
/*
    public function index_old()
    {
        $phone = $this->request->post('phone');
        $code = $this->request->post('code');
        if (!UserLogic::getInstance()->check_mobile($phone)) {
            return error_out('', '请输入正确的手机号');
        }
        //验证码写好放开
        $oldCode = Cache::store('user')->get('mobile_code:' . $phone);
        if (!$oldCode) {
            return error_out('', '验证码已过期，请重新获取验证码');
        }
        if ($oldCode != $code) {
            return error_out('', '验证码错误，请重新输入');
        }
        UserLogic::getInstance()->delTokenPhone($phone);
        $user = Db::name('user')->where(['phone' => $phone])->field('id, name,icon,is_del')->find();

        if ($user) {
            if ($user['is_del'] != 0) {
                return error_out('', '用户已被禁用');
            }
            $user_id = $user['id'];
            $user_token = UserLogic::getInstance()->getToken($user_id);
            return success_out([
                'user_token' => $user_token,
                'phone' => $phone,
                'name' => $user['name'],
                'icon' => $user['icon'] ? config('img.domain') . $user['icon'] : '',  //头像
                'status' => 1,

            ], '登录成功');
        } else {
            $parm['name'] = '';
            $parm['phone'] = $phone;
            $parm['icon'] = '';
            $parm['sex'] = 1;
            // $parm['birthday'] = '0'.'-'.'01'.'-'.'01';
            $parm['age'] = 0;
            $parm['height'] = 0;
            $parm['weight'] = 0;
            $parm['addr'] = '';
            $parm['addr_info'] = '';
            $parm['province'] = '';
            $parm['city_code'] = '';
            $parm['ad_code'] = '';
            $parm['remark'] = '';
            $parm['pay_code'] = 0;
            $parm['is_member'] = 1;
            $parm['is_del'] = 0;
            $parm['status'] = 0;
            $parm['create_time'] = CURR_TIME;
            $parm['update_time'] = CURR_TIME;
            $uid = UsersModel::register($parm);
            if ($uid) {
                $bind_user = UsersModel::userBindAll(["phone"=>$phone, "bind_status"=>1], "user_id, bind_user_id");
                foreach ($bind_user as $key => $value){
                    UsersModel::userBindUpAll($value["user_id"], $value["bind_user_id"], $uid);
                }
                $user_token = UserLogic::getInstance()->getToken($uid);
                return success_out([
                    'user_token' => $user_token,
                    'phone' => $phone,
                    'name' => '',
                    'icon' => '',
                    'status' => 0,
                ], '登录成功');
            } else {
                return error_out('', '服务器异常');
            }
        }

    }

    // 保存push 数据
    public function clientPushAccount(){
        $coach_id = UserLogic::getInstance()->checkToken();
        $device_type = request()->post('device_type/d' , 0);
        $device_number = request()->post('device_number/s' , "");
        $service_type = request()->post('service_type/d' , 0);
        if(!$device_number) return error_out('', '参数错误');
        $where['user_id']   = $coach_id;
        $where['user_type'] = UsersModel::USER_TPYE_USER;
        $data['device_type']  = $device_type;
        $data['device_number']= $device_number;
        $data['service_type'] = $service_type;
        $user_push = UsersModel::clientPushFind(['user_id'=>$coach_id, 'user_type'=>UsersModel::USER_TPYE_USER], 'device_type, device_number');
        if(!$user_push){
            $data['user_id']   = $coach_id;
            $data['user_type'] = UsersModel::USER_TPYE_USER;
            $request = UsersModel::clientPushInsert($data);
            if($request === false)  return error_out('', "服务器异常");
        }
        if($user_push['device_type'] != $device_type || $user_push['device_number'] != $device_number){
            $request = UsersModel::clientPushUpdate(['user_id'=>$coach_id, 'user_type'=>UsersModel::USER_TPYE_USER], $data);
            if($request === false)  return error_out('', "服务器异常");
        }
        return success_out("", "添加成功");
    }


    public function sendCode()
    {
        $phone = input('phone');
        if (!UserLogic::getInstance()->check_mobile($phone)) {
            return error_out('', '请输入正确的手机号');
        }
        $user_url = config("user_domain") ?: "";
        if ($_SERVER["HTTP_HOST"] == $user_url && $phone != 15201276476){
            $code = rand(100000 , 999999);
            $cache_result = Cache::store('user')->set('mobile_code:' . $phone, $code, 300);
            if ($cache_result !== true){
                return error_out('','发送失败');
            }
            $templateParam  = ['code'=>$code];
            $response =UserSms::code($phone , $templateParam);
            if ($response->Code == 'OK') {
                Cache::store('user')->set('mobile_code:' . $phone, $code, 300);
                return success_out('', '发送成功');
            } elseif ($response->Code == 'isv.BUSINESS_LIMIT_CONTROL') {
                return error_out('', '当前账户频率操作过快 请稍后重试');
            } else {
                return error_out('', '服务器异常');
            }
        }else{
            $code = 111111;
            Cache::store('user')->set('mobile_code:' . $phone, $code, 300);
            return success_out('', '发送成功');
        }
    }

    public function add()
    {

        $date['name'] = request()->post('name', '');
        $date['phone'] = '';
        $date['icon'] = '';
        $date['addr_info'] = '';
        $date['province'] = '';
        $date['city_code'] = '';
        $date['ad_code'] = '';
        $date['create_time'] = '';
        $date['update_time'] = '';
        $date['sex'] = request()->post('sex', '');
        $date['birthday'] = request()->post('birthday', '');
        $date['birthday'] = $date['birthday'] . '-' . '01' . '-' . '01';
        $date['height'] = request()->post('height', '');
        $date['weight'] = request()->post('weight', '');
        $date['addr'] = request()->post('addr', '');
        $date['lon'] = request()->post('lon', '');
        $date['lat'] = request()->post('lat', '');
        $result = UsersModel::userAdd($date);
        // print_r($date);die;
        if ($result !== false) {
            return success_out('', '添加成功');
        } else {
            return error_out('', '服务器异常');
        }
    }

    //退出登录
    public function out()
    {
        $user_id = UserLogic::getInstance()->checkToken();
        $result = UserLogic::getInstance()->delToken($user_id);
        UserLogic::getInstance()->delDeviceId($user_id);
        if ($result === false) {
            return error_out('', '服务器异常');
        }
        return success_out('', '退出成功');
    }

    //刷新token
    public function refreshToken()
    {
        try {
            $user_id = UserLogic::getInstance()->checkToken();
            if ($user_id) {
                $user_token = UserLogic::getInstance()->getToken($user_id, true);
                return success_out([
                    'user_token' => $user_token,
                ], '刷新成功');
            } else {
                return error_out('', 'token已过期');
            }
        } catch (HttpException $e) {
            return error_out('', $e->getMessage());
        }
    }

    public function wechatOld()
    {
        $code = request()->post('code' , '');
        if (!$code) {
            return error_out('', '微信凭证不能为空');
        }
        $wx_token = WechatLogic::getInstance()->getToken($code);
        if (isset($wx_token['errcode'])) {
            return error_out('', $wx_token['errmsg']);
        }

        $access_token= $wx_token['access_token'];
        $refresh_token= $wx_token['refresh_token'];
        $openid = $wx_token['openid'];
        $unionid = isset($wx_token['unionid']) ? $wx_token['unionid'] : '';

        //验证openid是否已存在
        $user = UsersModel::getOpenid($wx_token['openid']);
        if ($user) { //已存在
            if ($user['is_del'] != 0) {
                return error_out('', '用户已被禁用');
            }
            $user_id = $user['id'];
            $user_token = UserLogic::getInstance()->getToken($user_id);
            $result['user_token'] = $user_token;
            $result['name'] =$user['name'] ? $user['name'] : '';
            $result['phone'] =$user['phone'] ? $user['phone'] : '';
            $result['icon'] =$user['icon'] ? config('img.domain') . $user['icon'] : '';
            $result['wechat_id'] =0;
            $result['status'] =$user['phone'] ? 1 : 0;
        }else{
            $check_result = WechatLogic::getInstance()->check_access_token($access_token , $openid);
            if ($check_result['errcode'] !== 0){
                $refresh_result = WechatLogic::getInstance()->refresh_token($refresh_token);
                if (isset($refresh_result['errcode'])){
                    return error_out('' , $refresh_result['errmsg']);
                }else{
                    $access_token= $refresh_result['access_token'];
                    $refresh_token= $refresh_result['refresh_token'];
                    $openid = $refresh_result['openid'];
                    $unionid = isset($refresh_result['unionid']) ? $refresh_result['unionid'] : '';
                }
            }
            //获取微信信息
            $user_info = WechatLogic::getInstance()->getUserInfo($access_token,$openid);
            if (isset($user_info['errcode'])){
                return error_out((object)array() , $user_info['errmsg']);
            }
            $wechat_info = UsersModel::wechatGetOpenid($wx_token['openid']);

            $data['access_token'] = $wx_token['access_token'];
            $data['unionid'] = $unionid;
            $data['openid'] = $wx_token['openid'];
            $data['refresh_token'] = $refresh_token;
            $data['headimgurl'] = WechatLogic::getInstance()->downloadAvar($user_info['headimgurl']);
            $data['nickname'] = $user_info['nickname'];
            if (!$wechat_info){
                $wx_id =UsersModel::wxInsert($data);
                $wechat_id=intval($wx_id);
                if (!$wechat_id){
                    return error_out('', '服务器异常');
                }
            }else{
                $wechat_id = $wechat_info['id'];
                $update_result = UsersModel::wechatUpdate($wechat_info['id'],$data);
                if (!$update_result){
                    return error_out('', '服务器异常');
                }
            }
            $result = [
                'user_token' => '',
                'phone' => '',
                'name' => '',
                'icon' => '',
                'wechat_id' =>$wechat_id,
                'status' => 0
            ];
        }
        return success_out($result , '微信登陆');
    }

    public function wechat()
    {
        $code = request()->post('code' , '');
        if (!$code) {
            return error_out('', '微信凭证不能为空');
        }
        $wx_token = WechatLogic::getInstance()->getToken($code);
        if (isset($wx_token['errcode'])) {
            return error_out('', $wx_token['errmsg']);
        }
        $access_token= $wx_token['access_token'];
        $refresh_token= $wx_token['refresh_token'];
        $openid = $wx_token['openid'];
        $unionid = isset($wx_token['unionid']) ? $wx_token['unionid'] : '';
        //验证openid是否已存在
        $user = UsersModel::getOpenid($openid);
        if ($user) { //已存在
            if ($user['is_del'] != 0) {
                return error_out('', '用户已被禁用');
            }
            $user_id = $user['id'];
            $user_token = UserLogic::getInstance()->getToken($user_id);
            $result['user_token'] = $user_token;
            $result['name'] =$user['name'] ? $user['name'] : '';
            $result['phone'] =$user['phone'] ? $user['phone'] : '';
            $result['icon'] =$user['icon'] ? config('img.domain') . $user['icon'] : '';
            $result['wechat_id'] =0;
            $result['status'] =$user['phone'] ? 1 : 0;
        }else{
            //获取微信信息
            $user_info = WechatLogic::getInstance()->getUserInfo($wx_token['access_token'],$wx_token['openid']);
            if (isset($user_info['errcode'])){
                return error_out((object)array() , $user_info['errmsg']);
            }
            $wechat_info = UsersModel::wechatGetOpenid($wx_token['openid']);
            $data['access_token'] =$access_token;
            $data['unionid'] =$unionid;
            $data['openid'] =$openid;
            $data['refresh_token'] = $refresh_token;
            $data['headimgurl'] = WechatLogic::getInstance()->downloadAvar($user_info['headimgurl']);
            // $data['nickname'] = $user_info['nickname'];
            if (!$wechat_info){
                $wx_id =UsersModel::wxInsert($data);
                $wechat_id=intval($wx_id);
                if (!$wechat_id){
                    return error_out('', '服务器异常');
                }
            }else{
                $wechat_id = $wechat_info['id'];
                $update_result = UsersModel::wechatUpdate($wechat_info['id'],$data);
                if (!$update_result){
                    return error_out('', '服务器异常');
                }
            }
            $result = [
                'user_token' => '',
                'phone' => '',
                'name' => '',
                'icon' => '',
                'wechat_id' =>$wechat_id,
                'status' => 0
            ];
        }
        return success_out($result , '微信登陆');
    }



    //微信登录
    public function bindingPhone()
    {
        $phone = $this->request->post('phone/s', '', 'trim,strip_tags');
        $code = $this->request->post('code/d');
        $wechat_id = $this->request->post('wechat_id/d');
        $wechat_info = UsersModel::wechatInfo($wechat_id);
        if (!$wechat_info){
            return error_out('', '参数错误');
        }
        $check_result = WechatLogic::getInstance()->check_access_token($wechat_info['access_token'] , $wechat_info['openid']);
        if ($check_result['errcode'] !== 0){
            $refresh_result = WechatLogic::getInstance()->refresh_token($wechat_info['refresh_token']);
            if (isset($refresh_result['errcode'])){
                return error_out('' , $refresh_result['errmsg']);
            }else{
                $wechat_info['access_token'] = $refresh_result['access_token'];
                $wechat_info['openid'] = $refresh_result['openid'];
            }
        }
        $wx_user_info = WechatLogic::getInstance()->getUserInfo($wechat_info['access_token'],$wechat_info['openid']);
        if (isset($wx_user_info['errcode'])) {
            return error_out('', $wx_user_info['errmsg']);
        }
        //验证码写好放开
        $oldCode = Cache::store('user')->get('mobile_code:' . $phone);
        if (!$oldCode) {
            return error_out('', '验证码已过期，请重新获取验证码');
        }
        if ($oldCode != $code) {
            return error_out('', '验证码错误，请重新输入');
        }
        //验证phone是否已存在;
        $user = UsersModel::getPhone($phone);
        if($user){
            if ($user['openid']){
                return error_out('', '此手机号已绑定过微信!');
            }else{
                if ($user['is_del'] != 0) {
                    return error_out('', '用户已被禁用');
                }
                try{
                    Db::startTrans();
                    $update['unionid'] = $wechat_info['unionid'] ? $wechat_info['unionid']: '';
                    $update['openid'] = $wechat_info['openid'];
                    $update['update_time'] = CURR_TIME;
                    UsersModel::userUpdate($user['id'],$update);

                    $wx_update['user_id'] = $user['id'];
                    $wx_update['headimgurl'] = $wx_user_info['headimgurl'];
                    //$wx_update['nickname'] = $wx_user_info['nickname'];
                    UsersModel::wechatUpdate($wechat_id,$wx_update);
                    Db::commit();
                }catch (Exception $e){
                    Db::rollback();
                    return error_out((object)array() , $e->getMessage());
                }
                $user_token = UserLogic::getInstance()->getToken($user['id']);
                $name = $user['name'] ? $user['name']: $wx_user_info['nickname'];
                $icon = $user['icon'] ? $user['icon']: WechatLogic::getInstance()->downloadAvar($wx_user_info['headimgurl']);
                return success_out([
                    'user_token' =>$user_token,
                    'phone' =>$phone,
                    'name' =>$name,
                    'icon' =>$icon ? config('img.domain') . $icon : '',
                    'wechat_id' =>0,
                    'status' =>1,
                ], '绑定手机号成功');
            }
        }else{
            $parm['phone'] = $phone;
            //$parm['name'] = $wx_user_info['nickname'];
            $parm['name'] = '';
            $parm['openid'] =  $wechat_info['openid'];
            $parm['unionid'] =  $wechat_info['unionid'];
            $parm['icon'] = WechatLogic::getInstance()->downloadAvar($wx_user_info['headimgurl']);
            $parm['sex'] = 1;
            //$parm['birthday'] = '0'.'-'.'01'.'-'.'01';
            $parm['age'] = 0;
            $parm['height'] = 0;
            $parm['weight'] = 0;
            $parm['addr'] = '';
            $parm['addr_info'] = '';
            $parm['province'] = '';
            $parm['city_code'] = '';
            $parm['ad_code'] = '';
            $parm['remark'] = '';
            $parm['pay_code'] = 0;
            $parm['is_member'] = 1;
            $parm['is_del'] = 0;
            $parm['status'] = 0;
            $parm['create_time'] = CURR_TIME;
            $parm['update_time'] = CURR_TIME;
            $user_id = UsersModel::register($parm);
            if ($user_id) {
                $user_token = UserLogic::getInstance()->getToken($user_id);
                return success_out([
                    'user_token' => $user_token,
                    'phone'=>$parm['phone'],
                    'name' =>$parm['name'],
                    'icon' => $parm['icon']? config('img.domain') . $parm['icon'] : '',
                    'wechat_id' =>0,
                    'status' =>0,
                ], '登录成功');
            } else {
                return error_out('', '服务器异常');
            }
        }
    }
*/


}