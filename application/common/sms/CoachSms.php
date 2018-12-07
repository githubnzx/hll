<?php
/**
 * Created by PhpStorm.
 * User: wangfeng
 * Date: 2018/4/28
 * Time: 上午10:30
 * 参数说明
 *
 * name: 教练昵称; sereviceTyp: 陪打、私教、团课
 *
 * *
    [
        'name'			=>'',				//上课用户名称
        'date' 			=>'2018年04月28日',	//上课时间日期
        'timeNode'		=>'10:30-11:30',	//上课时间节点
        'serviceType'	=>'陪打'				//上课类型
 *      'startDate'     => '2018年04月28日' //团课开始日期
 *      'endDate'       => '2018年04月28日' //团课结束时间

 */

namespace app\common\sms;


class CoachSms
{
    static private $sign = '昊动教练';

    /**
     * 电子秘书全民教练 - 教练资格审批通过通知
     * 模版内容: ${name}您好，您已获得电子秘书全民教练应用${serviceType}教练${levelName}级别认证，请打开电子秘书全民教练应用查看详情哦!
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function authThrough($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142947295', $message);
    }

    /**
     * 电子秘书全民教练 - 教练资格审批未通过通知
     * 模版内容: ${name}您好，您的电子秘书全民教练应用${serviceType}教练认证未通过，请试试其他认证。
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function authNotThrough($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142952348', $message);
    }

    /**
     * 电子秘书全民教练 - 教练课件审批通过通知
     * 模版内容: ${name}您好，您的${serviceType}课件已经通过认证，请打开电子秘书全民教练应用添加课程排期哦！
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function courseThrough($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142947284', $message);
    }

    /**
     * 电子秘书全民教练 - 教练课件审批未通过通知
     * 模版内容: ${name}您好，您的${serviceType}课件未通过认证，请试试添加其他课件。
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function courseNotThrough($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142947274', $message);
    }

    /**
     * 电子秘书全民教练 - 用户预约教练陪练/私教/团课课程教练端通知
     * 模版内容: ${name}您好，您的${serviceType}课程于${date}被预约，详情请打开电子秘书全民教练应用查看。
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function userOrdered($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142952325', $message);
    }

    /**
     * 电子秘书全民教练 - 教练提交提现操作通知
     * 模版内容: ${name}您好，您有一笔金额{$money}提现业务已成功受理，预计1~7个工作日到账，请注意查收。
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function withdrawal($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142947251', $message);
    }

    /**
     * 电子秘书全民教练 - 团课取消
     * 模版内容: ${name}您好，您开设的${serviceType}课程由于预约人数不够已取消，请打开电子秘书全民健身应用查看详情。
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function autoCancelTk($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142952319', $message);
    }

    /**
     * 电子秘书全民教练 - 验证码短信
     * 模版内容: 验证码${code},请在5分钟内完成登录账号或更换绑定，验证码提供给他人可能导致账号被盗，请勿泄露
     * @param $phone
     * @param $message
     */
    static public function code($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142952309', $message);
    }

    /**
     * 电子秘书全民教练 - 教练资质升级成功
     * 模版内容: ${name}您好，您的电子秘书全民教练应用${serviceType}教练升级通过，请打开应用查看。
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function authUpgradeSuccess($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142952296', $message);
    }

    /**
     * 电子秘书全民教练 - 资质升级失败
     * 模版内容: ${name}您好，您的电子秘书全民教练应用${serviceType}教练升级未通过，请试试其他资质升级。
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function authUpgradeFail($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142952299', $message);
    }

    /**
     * 电子秘书全民教练 - 用户取消私教/陪打
     * 模版内容: ${name}您好，用户已取消您${startDate}上课时间为${timeNode}的${serviceType}课程，请打开电子秘书全民健身应用查看详情
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function userNotGroupClassCancel($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142947227', $message);
    }

    /**
     * 电子秘书全民教练 - 用户取消团课
     * 模版内容: ${name}您好，用户已取消${startDate}至${endDate}团课课程，请打开电子秘书全民健身应用查看详情。。
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function userGroupClassCancel($phone, $message)
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_142947217', $message);
    }
}
