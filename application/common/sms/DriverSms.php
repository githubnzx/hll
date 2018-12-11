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


class DriverSms
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
     *  亟亟诚运 - 司机提交审核通知
     * 模版内容: 感谢您对亟亟城运的支持，我行将尽快审核您的申请，您可拨打客服热线0310-6030906查询申请进度。
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function auditNotice($phone, $message = [])
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_152288082', $message);
    }

    /**
     *  亟亟诚运 - 司机通过审核通知
     * 模版内容: 感谢您对亟亟城运的支持，您的申请已通过，接单赚钱吧。
     * @param $phone
     * @param $message
     * @return stdClass
     */
    static public function auditPass($phone, $message = [])
    {
        return Sms::sendSms($phone, self::$sign, 'SMS_152283226', $message);
    }

}
