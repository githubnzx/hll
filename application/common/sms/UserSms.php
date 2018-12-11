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
    static private $sign = '河北泰乐网络科技有限公司';

    /**
     *  注册 - 验证码短信
     * 模版内容: 您正在申请手机注册，验证码为：${code}，5分钟内有效！
     * @param $phone
     * @param $message
     */
    static public function code($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_152510162', $message);
    }

    /**
     *  找回密码 - 验证码短信
     * 模版内容: 您的动态码为：${code}，您正在进行密码重置操作，如非本人操作，请忽略本短信！
     * @param $phone
     * @param $message
     */
    static public function retrievePassCode($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_152425921', $message);
    }
}