<?php
namespace app\common\push;

use think\Loader;
use think\Config;
use app\user\model\UsersModel;
Loader::import('push.Push');


/**
 * Created by PhpStorm.
 * User: niuzhenxiang
 * Date: 2017/10/19
 * Time: 上午11:33
 */
class Push extends BaseLogic
{
    private $client_service_type= [0 => 'test', 1=> 'service'];
    private $client_device_type = [1 => 'ios',  2=> 'android'];
    private $client_user_type   = [1 => 'user', 2=> 'driver'];

    /**
     * @param string $TargetValue  设备id
     * @param $title
     * @param $content
     * @param string $ext       扩展|"{\"k1\":\"android\",\"k2\":\"v2\"}"
     * @param string $pushTime  推送时间|格式strtotime|默认立即发送
     * @param string $Target    推送目标|账号
     * @return mixed
     */
    public function pushMsgIos($user_id, $user_type, $title, $content, $ext = "", $TargetValue = "", $Target = "DEVICE", $pushTime = "")
    {
        try {
            $config = $this->getConfigPush($user_id, $user_type, $TargetValue);
            if(!$config) return true;
            \Push::init($config);
            $res = \Push::pushMsgIos($title, $content, $config['TargetValue'], $ext, $Target, $pushTime);
            if(!$res->MessageId) return false;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    private function getConfigPush($user_id, $user_type, $TargetValue){
        if(!$user_id || !$user_type) return false;
        $client_push_info = UsersModel::clientPushFind(['user_id'=>$user_id, 'user_type'=>$user_type]);
        if(!$client_push_info) return [];
        $service_type= $this->client_service_type[$client_push_info['service_type']];
        $device_type = $this->client_device_type[$client_push_info['device_type']];
        $user_type   = $this->client_user_type[$client_push_info['user_type']];
        $config = config::get("push." . $service_type . "_" . $device_type . "_" . $user_type);
        $config['TargetValue'] = !empty($TargetValue) ? "All" : $client_push_info['device_number'];
        $config['device_type'] = $client_push_info['device_type'];
        return $config;
    }
    /**
     * @param string $TargetValue  设备id
     * @param $title
     * @param $content
     * @param string $ext       扩展|"{\"k1\":\"android\",\"k2\":\"v2\"}"
     * @param string $pushTime  推送时间|格式strtotime|默认立即发送
     * @param string $Target    推送目标|账号
     * @param string $OpenType  点击通知后动作
     * @param string $OpenUrl   跳转地址
     * @return mixed
     */
    public function pushMsgAndroid($user_id, $user_type, $title, $content, $ext = "", $TargetValue = "", $Target = "DEVICE", $pushTime = "", $OpenType = "NONE", $OpenUrl = "")
    {
        try {
            $config = $this->getConfigPush($user_id, $user_type, $TargetValue);
            if(!$config) return true;
            \Push::init($config);
            $res = \Push::pushMsgAndroid($title, $content, $TargetValue, $ext, $Target, $pushTime, $OpenType, $OpenUrl);
            if(!$res->MessageId) return false;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * @param string $TargetValue  设备id
     * @param $title
     * @param $content
     * @param string $ext       扩展|"{\"k1\":\"android\",\"k2\":\"v2\"}"
     * @param string $pushTime  推送时间|格式strtotime|默认立即发送
     * @param string $Target    推送目标|账号
     * @return mixed|SimpleXMLElement
     * $user_id, $user_type, $title, $content, $ext = "", $TargetValue = "", $Target = "DEVICE", $pushTime = ""
     */
    public function pushMsgAll($user_id, $user_type, $title, $content, $ext = "", $TargetValue = "", $Target = "DEVICE", $pushTime = "", $OpenType = "NONE", $OpenUrl = "")
    {
        try {
            $config = $this->getConfigPush($user_id, $user_type, $TargetValue);
            if(!$config) return true;
            \Push::init($config);
            if($config['device_type'] == 1){
                $res = \Push::pushMsgIos($title, $content, $config['TargetValue'], $ext, $Target, $pushTime);
            } else {
                $res = \Push::pushMsgAndroid($title, $content, $config['TargetValue'], $ext, $Target, $pushTime, $OpenType, $OpenUrl);
            }
            if(!$res->MessageId)  return false;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

}