<?php
/**
 * Created by PhpStorm.
 * User: niuzhenxiang
 * Date: 2018/10/21
 * Time: 下午17:30
 */
require_once 'aop/AopClient.php';
require_once 'aop/request/AlipayTradeAppPayRequest.php';
require_once 'aop/request/AlipayTradeRefundRequest.php';
class UserAlipay
{
    private $appId = '2018101461696215';
    private $appPrivateKey = 'MIIEpQIBAAKCAQEA5Sb77+qObK9X5kotBVdlB7mhTbEeJuQfY4JCNSNi68VJ/9m1cY8rUb4ucPmoCviRQojKP+MdSz4zIQOljr3rKzSGiRMPeY818nvXQYGWb5FzItEXZULogP38Es5wLhLqNJHM5EVpCiFgjLCrMiAGDnvBQKk3hY8FVeizLvcDfXRE8AQM1Phf+FyYgjYvuazfiEyeRbEr81bKavH7EDQ457suitCrEFn94tb5/G8vMK8I8asarwrpXWcx548hgRE6DsMxdDPxrOD7ILHKcoO9JcZvOc/Y6UX+NDndSGRXiaRkjf3PW2r7Bu7UTn3DbckFGqKzMiVBjCRUCm0trBweyQIDAQABAoIBAQDHCkaZTPZrBx7lhBZ45kF2JVIrpqXXCB0PlQwnFdaNji9JkXPd8IqjFPtH3EKPTPr3fNOYDJDcU2mbyowaYXKMc7JMDAdMAg3M0q7VUlc2D3OfkVit9yD3MiWqvC+KhZlzEhWTTYsAZp9zdN0uy8wW9n2UyabVVrY+ucIyEBKy+w7ZS3fYqxbdZn97fquj3KB/CUsMZjCeg+r/yaSqS0e2NzzCMianMJ1P+dRNgQUJjkLCvyTp+uGAc3K05tQ3NQqFYnLDBgxnOEe40CARsKbBXk7CUQPIHslug6/W88kRrIsHmi09C7Y33vCL/BF+JsoNBbtWSm03K/n+pfhkPVwBAoGBAP1ntwzNOUuxesbk2ycGETTVD4Ao2TASfdi5i3lo2aEYifQ8oYi+15RNHUh9AeAAtxW6jfhBI+gSMrqiTQdw/Ke8Lz/chWOY42DBvWjB/gj+1b9kNh9Ttj06sOVhn3gqfNEa8vjKt0MZnroWeCZdvpqS5jOuT/LJb4mFkvEjMu1JAoGBAOd/sRse1cT3r4zcwppGuyG391gSwWmjEDBxZg4BYxDLN5B5J52R88rOVbUCLz3l08hYDkp69obroubOonUFnB8Kc+xjHr0mBEEqRXBNNWVszG4nvxGcWYQIVs9jZm2K2cETpwd6zRg8OT3WKUmIHPq8jUEEnnnnS0vaorys7yWBAoGBAM6mduPMtK3ixXJPjdzMGNoR6nskVMHcQp6r6W8QL3ItdcskasL+hwXcwUWtFAtd6fChW0eZr7OAq7gBxXmNDa2oTIvyxSDMvJjaRiGZGhax4xQan/x6IwLZywq0yvyPAYzxQjXb8wmYahXtXvxCtL6Pj4WT4ITo+rmWoZdekhhpAoGAcgYbHdiaQmIdPayezY55LaqHUgIq8fU3TCUOt2dHfEYcIDUMpjf1dLcc0AM7cal6HvwATf8y3lHB8x1kN2+D7mYfoxdPVKsc/Vvsx9u/qS1lo/w/yFTSYo4Y9B1pnhr+FnOvmaeKzZ0cNLD/tRAt9fJZQOUdib27AKm37mbfhAECgYEAuu1R/fJD8VKrR2QXHNPTxLvO4521S8yWvRUtQM/TKgTF0nBqRi1CfpYB8U4d56PeRvjBmn6wM6BKpBplsA5F1KatyK0M8huJslru+meRanbvGpqvflKpXHQyfQOYvz8jxIXWHugYr2V9FZrbr/9VaD9qc9flN85iO/5WZzZhiQ8=';
    private $appPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhtR3gsa0eVelFVsIA3xPCFEq7mRVBy/gvnZBNyJqJWXCbSS6hnzXPVzGYy+L1bVyJncYUyNOeCofBX6+2ipTmNrFZ9E+ehXkX2guPk+wM4KcMIUNkJAyN2rrzL/pp8RRwcoMbV62nE8+8qqQSZS3QTxMqgapq9YWl4DcAP6XvRW1cfpJAnojwU9A4NrSLMidLQRLE7dSgXQCA2Xhx0Fj3bSSSnnG7NOBS+CwPmJxtxrdUCyKtGy6VYBLTledDR1Lze7MKDap9ecjqz1TztqMGZO+QdpbSyLoR4+RmC406c6EIL8OxgDw5OG7mXDthWdsyPec+qXXJiPH0Jg0jUnasQIDAQAB';
    private $appPid = "2088231900710773";

    public function orderString($parm)
    {
        $aop = new AopClient;
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->appPrivateKey ;
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = $this->appPublicKey;
//实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new AlipayTradeAppPayRequest();
//SDK已经封装掉了公共参数，这里只需要传入业务参数
        $bizcontent = json_encode([
            'body' => $parm['body'],
            'subject' => $parm['subject'],
            'out_trade_no' => $parm['out_trade_no'],
            'timeout_express' => '30m',
            'total_amount' => bcadd($parm['total_fee'], 0, 2),
            'product_code' => 'QUICK_MSECURITY_PAY',
        ]);
        $request->setNotifyUrl($parm['notify_url']);
        $request->setBizContent($bizcontent);
//这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);
//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
//        return htmlspecialchars($response);//就是orderString 可以直接给客户端请求，无需再做处理。
        return $response;//就是orderString 可以直接给客户端请求，无需再做处理。
    }

    public function checkSign($parm)
    {
        try {
            $aop = new AopClient;
            $aop->alipayrsaPublicKey = $this->appPublicKey;
            $flag = $aop->rsaCheckV1($parm, null, "RSA2");
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        return $flag;
    }

    public function refund($code, $price, $desc = '正常退款')
    {
        $aop = new AopClient;
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->appPrivateKey ;
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = $this->appPublicKey;
        $request = new AlipayTradeRefundRequest();
        $bizcontent = json_encode([
            'out_trade_no' => $code,
            'refund_amount' => bcadd($price, 0, 2),
            'refund_reason' => $desc,
        ]);
        $request->setBizContent($bizcontent);
        $result = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if (!empty($resultCode) && $resultCode == 10000) {
            return true;
        } else {
            return $resultCode;
        }
    }

    // 提现
    public function transfer($code, $price, $desc = '支付宝提现')
    {
        $aop = new AopClient;
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->appPrivateKey ;
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = $this->appPublicKey;
        $request = new AlipayFundTransToaccountTransferRequest(); //
        $bizcontent = json_encode([
            'out_trade_no' => $code,
            'refund_amount' => bcadd($price, 0, 2),
            'refund_reason' => $desc,
        ]);
        $request->setBizContent($bizcontent);
        $result = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if (!empty($resultCode) && $resultCode == 10000) {
            return true;
        } else {
            return $resultCode;
        }
    }

    // 获取token
    public function token($auth_code)
    {
        if (!$auth_code) return false;
        $aop = new AopClient;
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->appPrivateKey ;
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = $this->appPublicKey;

        //获取access_token
        $request = new AlipaySystemOauthTokenRequest();
        $request->setGrantType("authorization_code");
        $request->setCode($auth_code);
        //$request->setRefreshToken("201208134b203fe6c11548bcabd8da5bb087a83b");
        $result = $aop->execute($request);
        if(isset($result->error_response)){
            return $result->error_response->sub_msg;
        }
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        return $result->$responseNode->access_token;
    }

    // 获取用户信息
    public function authUserInfo($access_token) {
        if(empty($access_token)) return false;
        $aop = new \AopClient();
        //初始化
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->appPrivateKey ;
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = $this->appPublicKey;
        //获取用户信息
        $request = new AlipayUserInfoShareRequest();
        $result = $aop->execute($request, $access_token); //这里传入获取的access_token
        if(isset($result->error_response)){
            return $result->error_response->sub_msg;
        }
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $alipayUser =array();
        if ($result->$responseNode->code == "10000" && $result->$responseNode->msg == "Success"){
            $alipayUser['nick_name'] = isset($result->$responseNode->nick_name) ? $result->$responseNode->nick_name : "";
            $alipayUser['user_id'] = $result->$responseNode->user_id;                   // 用户唯一id
            $alipayUser['province'] = $result->$responseNode->province;                 // 省份
            $alipayUser['avatar'] = $result->$responseNode->avatar;                     // 头像
            $alipayUser['city'] = $result->$responseNode->city;                         // 城市
            $alipayUser['user_type'] = $result->$responseNode->user_type;               // 用户类型（1/2）1代表公司账户2代表个人账户
            $alipayUser['user_status'] = $result->$responseNode->user_status;           // 用户状态（Q/T/B/W）。Q代表快速注册用户T代表已认证用户B代表被冻结账户W代表已注册，未激活的账户
            $alipayUser['is_certified'] = $result->$responseNode->is_certified;         // 是否通过实名认证。T是通过 F是没有实名认证。
            $alipayUser['gender'] = $result->$responseNode->gender;                     // 是否通过实名认证。T是通过 F是没有实名认证。
        } else {
            return $result->$responseNode->msg;
        }
        return $alipayUser;
    }

    /*
    * 供 app 使用
    * 通过参数调用登录授权接口。
    * infoStr：根据商户的授权请求信息生成。详见授权请求参数。
    * https://docs.open.alipay.com/218/105325/
    * apiname=com.alipay.account.auth&app_id=xxxxx&app_name=mc&auth_type=AUTHACCOUNT&biz_type=openservice&method=alipay.open.auth.sdk.code.get&pid=xxxxx&product_id=APP_FAST_LOGIN&scope=kuaijie&sign_type=RSA2&target_id=20141225xxxx&sign=fMcp4GtiM6rxSIeFnJCVePJKV43eXrUP86CQgiLhDHH2u%2FdN75eEvmywc2ulkm7qKRetkU9fbVZtJIqFdMJcJ9Yp%2BJI%2FF%2FpESafFR6rB2fRjiQQLGXvxmDGVMjPSxHxVtIqpZy5FDoKUSjQ2%2FILDKpu3%2F%2BtAtm2jRw1rUoMhgt0%3D
    * */
    public function loginAuth(){
        $aop = new \AopClient(); //实例化支付宝sdk里面的AopClient类,下单时需要的操作,都在这个类里面
        $param = [
            "apiname"   => 'com.alipay.account.auth',
            "method"    => "alipay.open.auth.sdk.code.get",
            "app_id"    => $this->appId,
            "app_name"  => "mc",
            "biz_type"  => "openservice",
            "pid"       => $this->appPid,
            "product_id"=> "APP_FAST_LOGIN",
            "scope"     => "kuaijie",
            "target_id" => md5(time() . mt_rand(0,1000)),
            "auth_type" => "AUTHACCOUNT",
            "sign_type" => "RSA2"
        ];
        //生成签名
        $paramStr = $aop->getSignContent($param);
        $sign = $aop->alonersaSign($paramStr, $this->appPrivateKey, 'RSA2');
        $param['sign'] = $sign;
        $str = $aop->getSignContentUrlencode($param);
        return $str;
    }





}