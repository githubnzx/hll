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
            echo $resultCode;die;
            return $resultCode;
        }
    }



}