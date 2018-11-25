<?php

namespace app\driver\logic;

use app\common\config\WxPayUserConfig;
use app\user\model\OrderModel;
use app\common\config\WxPayDriverConfig;
use app\driver\model\DriverModel;
use app\user\model\TasteModel;
use app\common\sms\UserSms;
use think\exception\HttpException;
use think\Loader;
use think\Log;

/**
 * Created by PhpStorm.
 * User: nzx
 * Date: 2018/03/19
 * Time: 13:00
 */
class OrderLogic extends BaseLogic
{

    public function payWx($code, $price, $notifyUrl, $body = "	亟亟城运支付")
    {
        $price = 0.01;
        $times = CURR_TIME;
        $time_start = date("YmdHis", $times);
        $time_expire= date("YmdHis", $times + 90);
        Loader::import('wxpay.lib.WxPay#Api');
        $inputObj = new \WxPayUnifiedOrder();
        $inputObj->SetOut_trade_no($code);
        $inputObj->SetBody($body);
        $inputObj->SetNotify_url($notifyUrl);
        $inputObj->SetTotal_fee(intval($price * 100));
        $inputObj->SetTrade_type("APP");
        $inputObj->SetTime_start($time_start);
        $inputObj->SetTime_expire($time_expire);
        $config = new WxPayUserConfig();
        $order = \WxPayApi::unifiedOrder($config, $inputObj);
        if ($order['return_code'] != 'SUCCESS' || $order['result_code'] != 'SUCCESS') {
            return false;
        }
        //$inputObj->values = [];
        $data['appid'] = $order['appid'];
        $data['partnerid'] = $order['mch_id'];
        $data['prepayid'] = $order['prepay_id'];
        $data['package'] = "Sign=WXPay";
        $data['noncestr'] = \WxPayApi::getNonceStr();
        $data['timestamp'] = (string)time();
        //$inputObj->values = $data;
        $data['sign'] = $inputObj->SetSign($config);
        return $data;
    }

    public function refundWx($code, $price)
    {
        $price = 0.01;
        Loader::import('wxpay.user.lib.WxPay#Api');
        $inputObj = new \WxPayRefund();
        $inputObj->SetOut_trade_no($code);
        $inputObj->SetOut_refund_no($code);
        $inputObj->SetTotal_fee(intval($price * 100));
        $inputObj->SetRefund_fee(intval($price * 100));
        $order = \WxPayApi::refund($inputObj);
        if ($order['return_code'] != 'SUCCESS' || $order['result_code'] != 'SUCCESS') {
            Log::error('微信退款失败:' . $code . '=>' . $order['err_code_des']);
        }
        return $order;
    }

    public function transferWx($code, $openid, $price, $check_name = 'FORCE_CHECK', $user_name = '')
    {
        $price = 0.01;
        Loader::import('wxpay.user.lib.WxPay#Api');
        $inputObj = new \WxTransOrder();
        $inputObj->SetPartner_trade_no($code);
        $inputObj->SetOpen_id($openid);
        $inputObj->SetCheck_name($check_name);
        $inputObj->SetRe_user_name($user_name);
        $inputObj->SetAmount(intval($price * 100));
        $inputObj->SetDesc('提现');
        $order = \WxPayApi::transfer($inputObj);
        if ($order['return_code'] != 'SUCCESS' || $order['result_code'] != 'SUCCESS') {
            Log::error('微信提现失败:' . $code . '=>' . $order['err_code_des']);
            return false;
        }
        return true;
    }

    public function refundZfb($code, $price, $desc)
    {
        $price = 0.01;
        try {
            Loader::import('alipay.Alipay');
            $alipay = new \Alipay();
            return $alipay->refund($code, $price, $desc);
        } catch (\Exception $e) {
            Log::error('支付宝退款失败:' . $code . '=>' . $e->getMessage());
            return false;
        }
    }

    public function payZfb($code, $price, $notifyUrl)
    {
        $price = 0.01;
        Loader::import('alipay.Alipay');
        $alipay = new \Alipay();
        $param['body'] = '昊动';
        $param['subject'] = '昊动';
        $param['out_trade_no'] = $code;
        $param['total_fee'] = $price;
        $param['notify_url'] = $notifyUrl;
        return $alipay->orderString($param);
    }

    public function makeCode()
    {
        return date("YmdHis") . rand(100000, 999999);
    }

   /* public function actionMessage($orderList, $title, $content, $delay = false)
    {
        $current_time = time();
//        $messagesList = [];
        if ($delay) {
            $push_time = strtotime("+10 hours");
        } else {
            $push_time = strtotime("+2 seconds");
        }
        $messagesModel = new MessagsModel();
        foreach ($orderList as $k => $order) {
            $role_id = isset($order['role_id']) ? $order['role_id'] : MessagsModel::ROLE_SERVICE;

            $messages = [
                'u_id' => $order['user_id'],
                'order_id' => $order['id'],
                'role_id' => $role_id,
                'read_status' => 0,
                'status' => 1,
                'title' => $title,
                'content' => $content,
                'create_time' => $current_time,
            ];
            $messags_id = $messagesModel->insertMsg($messages);

            $userToken = getApiCache()->get('user_id:' . $order['user_id']);
            if ($userToken) {
                $extData['order_id'] = $order['id'];
                $extData['messags_id'] = $messags_id;
                $extData['role_id'] = $role_id;
                $ext = json_encode($extData);
                $pushResult = PushLogic::getInstance()->sendAll($userToken, $title, $content, $ext, $push_time, 'ACCOUNT');
            } else {
                $pushResult = false;
            }
        }
//        $messagesModel->insertMsgList($messagesList);
    }
*/
    public function makeContractCode($order_id)
    {
        return 'DX-APP-' . date('Y') . '-' . sprintf("%010d", $order_id);
    }
    /**
     * 处理时间节点返回时长
     * @param  string $time_nodes    时间节点
     * @return int    $length_time   时长
     */
    public function lengthTime($time_nodes){
        if(empty($time_nodes)) return 0;
        $length_time = count(array_filter(explode(',', $time_nodes))) / 2;
        return $length_time;
    }
    // 私教/陪打
    public function sendSPSms($uid, $type_id, $date, $timeNode, $pay_user_id, $type){
        if($pay_user_id === $uid){
            $where['id'] = $uid;
        } else {
            $where['id'] = ['in', [$uid, $pay_user_id]];
        }
        $userInfo = UsersModel::getUserGroupInfo($where, 'name, phone');
        $timeNodeAr = ScheduleConfig::getInstance()->formatTimeNode($timeNode);
        $msg['date'] = date('Y年m月d日', $date);
        $msg['timeNode'] = $timeNodeAr['start_time'] . '-' . $timeNodeAr['end_time'];
        $msg['serviceType'] = CourseConfig::getInstance()->getCourseById($type_id);
        //var_dump($msg);die;
        switch ($type) {
            case 'cancel':
                foreach ($userInfo as $key => $val){
                    $msg['name'] = $val['name'];
                    if($val['phone']){
                        $sms_code = UserSms::cancel($val['phone'], $msg);
                        if($sms_code->Code != "OK" || $sms_code->Message != "OK"){
                            $error_msg = "姓名：".$val["name"]." 手机号：".$val["phone"].$sms_code->Message;
                            Log::record($error_msg,'error');
                        }
                    }
                }
                break;
            case 'upsign':
                foreach ($userInfo as $key => $val){
                    $msg['name'] = $val['name'];
                    if($val['phone']){
                        $sms_code = UserSms::endorse($val['phone'], $msg);
                        if($sms_code->Code != "OK" || $sms_code->Message != "OK"){
                            $error_msg = "姓名：".$val["name"]." 手机号：".$val["phone"].$sms_code->Message;
                            Log::record($error_msg,'error');
                        }
                    }
                }
                break;
            case 'create':
                foreach ($userInfo as $key => $val){
                    $msg['name'] = $val['name'];
                    if($val['phone']){
                        $sms_code = UserSms::subscribe($val['phone'], $msg);
                        if($sms_code->Code != "OK" || $sms_code->Message != "OK"){
                            $error_msg = "姓名：".$val["name"]." 手机号：".$val["phone"].$sms_code->Message;
                            Log::record($error_msg,'error');
                        }
                    }
                }
                break;
        }
    }
    // 团课
    public function sendTKSms($order_id, $user_id, $pay_user_id, $type){
        if($pay_user_id === $user_id){
            $where['id'] = $user_id;
        } else {
            $where['id'] = ['in', [$user_id, $pay_user_id]];
        }
        $start_time = $end_time = '';
        $orderDate = OrderModel::getInstance()->getOrderDateColumn(['order_id'=>$order_id]);
        if($orderDate){
            $start_time = key($orderDate);
            $_end_time = array_keys($orderDate);
            $end_time = array_pop($_end_time);
            $start_time = OrderModel::getInstance()->courseTime(reset($orderDate), $start_time)["start_time"];
        }
        $userInfo = UsersModel::getUserGroupInfo($where, 'name, phone');
        $msg['startDate'] = $start_time ? date('Y年m月d日', $start_time) : '';
        $msg['endDate'] = $end_time ? date('Y年m月d日', $end_time) : '';
        switch ($type) {
            case 'cancel':
                foreach ($userInfo as $key => $val){
                    $msg['name'] = $val['name'];
                    if($val['phone']){
                        UserSms::tkCancel($val['phone'], $msg);
                    }
                }
                break;
            case 'create':
                foreach ($userInfo as $key => $val){
                    $msg['name'] = $val['name'];
                    if($val['phone']){
                        UserSms::tkOrder($val['phone'], $msg);
                    }
                }
                break;
        }
    }

    public function scheduleVerify($time_nodes, $ordered_time_nodes, $date_time, $hour_time = 1){
        $dataAr = ['start'=>[], 'end'=>0];
        // 计算可预约时间
        $currDateDelayTimeNode = [];
        if($date_time === strtotime(CURR_DATE)){
            $currDateDelayTimeNode = range(18, currDateDelayTimeNode());
        }
        $result = startTimeNodeByDuration($time_nodes, $ordered_time_nodes, $hour_time, $currDateDelayTimeNode);
        if($result){
            $dataAr['start'] = $result;
            $dataAr['end'] = reset($result)+1;
        }
        return $dataAr;
    }

    public function memberAvgPirce($order_list, $member_list){
        $memberlist = $date_number = $data = [];
        foreach ($member_list as $value){
            for ($keyi = 0; $keyi < $value["surplus_number"]; $keyi++){
                $memberlist[] = $value["id"] . "_" . $value["avg_price"];
            }
        }
        foreach ($order_list as $key => $val){
            $time_nodes = OrderLogic::getInstance()->lengthTime($val["time_nodes"]);
            $date_number = array_slice($memberlist, 0, $time_nodes);
            $memberlist = array_slice($memberlist, $time_nodes);
            $res = array_count_values($date_number);
            $data[$val["id"]] = json_encode($res);
        }
        return $data;
    }

    // redis 同时间段课程微信多人支付 处理
    public function wxHandleSynchPay($order_id, $coach_id, $date, $time_nodes){
        $time_nodes_ar = explode(',', $time_nodes);
        array_push($time_nodes_ar, strval(end($time_nodes_ar)+1));
        $redis_count = false;
        foreach ($time_nodes_ar as $key => $value) {
            $redis_key = 'orderpay:' . $coach_id . $date . $value;
            if(getCache()->has($redis_key)){
                $redis_count = true; break;
            }
        }
        if($redis_count) return false;
        foreach ($time_nodes_ar as $key => $value) {
            $redis_key = 'orderpay:' . $coach_id . $date . $value;
            getCache()->set($redis_key, $order_id, 100);
        }
        return true;
    }
    // 清楚redis 占用
    public function cancelRedisPay($coach_id, $date_time, $time_nodes){
        $time_nodes_ar = explode(',', $time_nodes);
        array_push($time_nodes_ar, strval(end($time_nodes_ar)+1));
        foreach ($time_nodes_ar as $key => $value) {
            $redis_key = 'orderpay:' . $coach_id . $date_time . $value;
            if (getCache()->has($redis_key)) {
                getCache()->rm($redis_key);
            }
        }
        return true;
    }

    // 处理预约场地数据
    public function handleFieldSchedule($schedule_time_nodes, $ordered_time_nodes, $data_time_nodes, $type = "Inc")
    {
        $data = [];
        $posTimeNodes = strpos($schedule_time_nodes, $data_time_nodes);
        if ($posTimeNodes === false) return false;
        $ordered_time_nodes_arr = $ordered_time_nodes ? explode(",", $ordered_time_nodes) : [];
        $time_nodes_arr = $data_time_nodes ? explode(",", $data_time_nodes) : [];
        if ($type == "Inc") {
            $data_ordered_time_nodes = array_merge($ordered_time_nodes_arr, $time_nodes_arr);
        } elseif ($type == "Dec"){
            $data_ordered_time_nodes = array_diff($ordered_time_nodes_arr, $time_nodes_arr);
        } else {
            return $data;
        }
        sort($data_ordered_time_nodes);
        $data = implode(",", $data_ordered_time_nodes);
        return $data;
    }


}