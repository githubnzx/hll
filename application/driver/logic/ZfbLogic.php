<?php

namespace app\driver\logic;

use think\Cache;
use think\Config;
use think\exception\HttpException;
use think\Loader;
use think\Request;
use think\log;


class ZfbLogic extends BaseLogic
{
    // 授权 -> 获取用户信息
    public function alipayUserInfo($code = "")
    {
        if (!$code) return false;
        try {
            Loader::import('alipay.DriverAlipay');
            $alipay = new \DriverAlipay();
            return $alipay->authUserInfo($code);
        } catch (\Exception $e) {
            var_dump($e->getMessage());die;
            Log::error('支付宝获取用户信息失败: =>' . $e->getMessage());
            return false;
        }
    }

    // 获取token
    public function alipayToken($code = "")
    {
        if (!$code) return false;
        try {
            Loader::import('alipay.DriverAlipay');
            $alipay = new \DriverAlipay();
            return $alipay->token($code);
        } catch (\Exception $e) {
            Log::error('支付宝获取token失败: =>' . $e->getMessage());
            return false;
        }
    }

    /*
     * 供 app 使用
     * 通过参数调用登录授权接口。
     * infoStr：根据商户的授权请求信息生成。详见授权请求参数。
     * https://docs.open.alipay.com/218/105325/
     * apiname=com.alipay.account.auth&app_id=xxxxx&app_name=mc&auth_type=AUTHACCOUNT&biz_type=openservice&method=alipay.open.auth.sdk.code.get&pid=xxxxx&product_id=APP_FAST_LOGIN&scope=kuaijie&sign_type=RSA2&target_id=20141225xxxx&sign=fMcp4GtiM6rxSIeFnJCVePJKV43eXrUP86CQgiLhDHH2u%2FdN75eEvmywc2ulkm7qKRetkU9fbVZtJIqFdMJcJ9Yp%2BJI%2FF%2FpESafFR6rB2fRjiQQLGXvxmDGVMjPSxHxVtIqpZy5FDoKUSjQ2%2FILDKpu3%2F%2BtAtm2jRw1rUoMhgt0%3D
     * */
    public function loginAuth(){
        try {
            Loader::import('alipay.DriverAlipay');
            $alipay = new \DriverAlipay();
            return $alipay->loginAuth();
        } catch (\Exception $e) {
            Log::error('支付宝授权失败: =>' . $e->getMessage());
            return false;
        }
    }
}