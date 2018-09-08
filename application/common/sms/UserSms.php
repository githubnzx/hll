<?php
/**
 * Created by PhpStorm.
 * User: wangfeng
 * Date: 2018/4/28
 * Time: 上午10:30
 */

namespace app\common\sms;

/**
 * Class UserSms
 * @package app\common\sms
 * 总的参数说明:
 *
    [
        'name'			=>'',				//上课用户名称
        'date' 			=>'2018年04月28日',	//上课时间日期
        'timeNode'		=>'10:30-11:30',	//上课时间节点
        'serviceType'	=>'陪打'				//上课类型
 *      'startDate'     => '2018年04月28日' //团课开始日期
 *      'endDate'       => '2018年04月28日' //团课结束时间
    ]
 */
class UserSms
{
    static private $sign = '昊动';

    /**
     * 电子秘书全民健身 - 用户预约课程完成
     * 模版内容: ${name}您好，您已预约${date}上课时间为${timeNode}的${serviceType}课程，请打开电子秘书全民健身应用查看详情。
     * @param $phone
     * @param $message  array
     * @return stdClass
     */
    static public function subscribe($phone, $message)
    {
        return  Sms::sendSms($phone, self::$sign, 'SMS_142952382', $message);
    }

    /**
     * 昊动 - 被关联用户短息邀请注册
     * 模版内容: 手机尾号为${phone}的用户添加了你成为他“昊动”应用的亲情账号，成为亲情账号后可以共享他的会员卡哦！快来点击来点击https://qm.boringkiller.cn/loadPage.html查看。查看。
     * @param $phone
     * @param $message  array
     * @return stdClass
     */
        static public function invitation($phone, $message = [])
    {
        return  Sms::sendSms($phone, self::$sign, 'SMS_143610655', $message);
    }

    /**
     * 电子秘书全民健身 - 用户取消预约课程完成
     * 模版内容: ${name}您好，您已取消${date}上课时间为${timeNode}的${serviceType}课程，请打开电子秘书全民健身应用查看详情。
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function cancel($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142952375', $message);
    }

    /**
     * 电子秘书全民健身 - 用户改签课程完成
     * 模版内容: ${name}您好，您已改签${date}上课时间为${timeNode}的${serviceType}课程，请打开电子秘书全民健身应用查看详情。
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function endorse($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142952373', $message);
    }

    /**
     * 电子秘书全民健身 - 团课取消
     * 模版内容: 您预约的${serviceType}课程，由于人数不够不能进行开课，课程费用已退回您的原支付账户，预计1~7个工作日到账，请注意查收。
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function autoCancelTk($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142952367', $message);
    }

    /**
     * 电子秘书全民健身 - 验证码短信
     * 模版内容: 验证码${code},请在5分钟内完成登录账号或更换绑定，验证码提供给他人可能导致账号被盗，请勿泄露。
     * @param $phone
     * @param $message
     */
    static public function code($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142952362', $message);
    }

    /**
     * 电子秘书全民健身 - 用户预约团课课程完成
     * 模版内容: ${name}您好，您已预约${startDate}至${endDate}的团课课程，请打开电子秘书全民健身应用查看详情。
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function tkOrder($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142947230', $message);
    }

    /**
     * 电子秘书全民健身 - 用户取消预约团课课程完成 (改签操作用户与上课用户都收到此短信)
     * 模版内容: ${name}您好，您已取消${startDate}至${endDate}的团课课程，请打开电子秘书全民健身应用查看详情。
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function tkCancel($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142952279', $message);
    }
}