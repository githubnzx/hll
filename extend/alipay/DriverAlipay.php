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
class Alipay
{
    private $appId = '2018101461697234';
    private $appPrivateKey = 'MIIEowIBAAKCAQEAzsRTUAS1uHF16G0dRxJG4cD5muNpZkm0asLGoS9VuR6O6F+5RSriGqb6ZwW3H0YGZASiOYMEh6rbzTHQ3yL8oK/EO7H5C9JIWwap8GM2ddC9MhpFsANq3/sqcuiRNcOIp6hVI2Shp2bxt9G4EoYl0S77lU/+e5OOerErvyfbQQnm2MC4U86Co6LAWH+mCmE3zIiMxnhGCSU5CsBh8Dk7cfG/wyu7KoIrtzOiv/d3qzrMKT4bvA1nx1UkEnPxDzmxDeJ+d1MhPEChF5yxlufvjQNszCLEeKTfB/ynvcqYmFogX0RfPdU3fwhtRLJbpWuG8ZwULRFbidmhNhA1uQ1DRQIDAQABAoIBAQC6vgnYvUg6ew7YiqPkqpcmEZnd0AJDhdHSknO+4/dyKC09pia4V5C6LZD+NuU6842WC7FQbApNVX0LCDDzNrAfmF+M4qJhkNwUiiI1oAVxxsL5W00ROSTvgfLGsVFk02K6uYebXam6fXlWYazz3gCNuvcx1XurtIr8OMOf86lMRLq7F0YjnsqKPPAUd3026GwGbf/ikh/RUzErNoANyBdKLP7aF51+TRKPCxCJr0aeCR2Yzt4BXSUn+IIwhS1z6PXNCB4YFyy3VmSNipyspvU58z5BHDmi3V++85v6tDswFsGQj7BHRXZCqNC38x70TqdJUlIqq/RDpglEeKp0xmmJAoGBAPTf4ePmUK7sI9tCYafAgl/oPCnk1rXG1pjRAcSEgIZT0REHN4XNSOxdxjsWbO1ephqMpEmKox06Gz/eAIeP/P4QNSVUj3CD75VP9Efdlt/jrRyOwIjah8h4OeqIqjWOaDvZxj8TiIxfseOadfq7Upz9MhG7Oe48cBD3zu/smQN/AoGBANgpN0Ng+IT82IsRORqowU7NGUNDQ82kf7IhKZOi5Ctux7HGOm6M5ox27QWVho9T7Qn5EYsxIifcIkajgPDkeeiatiqbIA19fbT+X5ht1Cd0OTR2draOkttS/xh0yCFZOTA4tXlca1o3qFGOm6xEnR+5U6+ShWcPCP1afT1m3Qs7AoGALl9zjNAh+W5Yv/4LNlR7nCQDuL9Qde4o15nFJu0c1dNrpTjjp+Afbju3/ZqamD0zYZW+yvqJN1C4tliZaxK2i3qQRfiEjX8+0NzqWu24079vGhhil8girLEv7p1g9nF4hdUQ+QL++e5pZrvqmcf9tiMw5zC4oMgrRNtZAhChTYsCgYAVgvSG9g2FXoxGOq6OIqEqMGsFMJp8ypQSrA4xLRK758hjvrBt9AO1ktg/qAO+G8IJLgo66ebWRKf8k6TvVC11on/ieZGVBhoOewoUZ0mnq39N40QpIsMmpHnSezy3ZOO2Es6shy//yG4tv51qZWGlmJHldRVlji981xaDl8sDpQKBgE5FZbWviXgobdbgvrLyKkN6tHdys9orwrgJOfT9vMqQn+C+Qu7z/bkI7DEzv2lp2BolQIhvJgndWvqUA7164eksi7ylrpnGHtk7c6SKQYt9vaMzorgcNnOaILTGG2OEFfQ0LeuEhAOjikr3XAz6/Ni/jnnctMHGFeecuP4Jx0k0';
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


    public function refund($code, $price, $desc = '正常退款')
    {
        $aop = new AopClient;
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->appPrivateKey ;
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = $this->appPublicKey;
        $request = new AlipayTradeRefundRequest(); //alipayFundTransToaccount
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
    }transfer



}