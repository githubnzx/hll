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
    static private $sign = '亟亟城运';

    /**
     * 电子秘书全民健身 - 验证码短信
     * 模版内容: 验证码${code},请在5分钟内完成登录账号或更换绑定，验证码提供给他人可能导致账号被盗，请勿泄露。
     * @param $phone
     * @param $message
     */
    static public function code($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_152283220', $message);
    }
}