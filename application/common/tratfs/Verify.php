<?php
/**
 * Created by PhpStorm.
 * User: wangfeng
 * Date: 2018/5/29
 * Time: 上午10:16
 */

namespace app\common\tratfs;


use app\coach\logic\CoachAuthLogic;
use app\coach\logic\ScheduleLogic;
use app\coach\model\CoachModel;
use app\coach\model\CourseModel;
use think\exception\HttpException;
use think\Validate;

trait Verify
{
    private $requestErrorMsg    = '请求参数错误';
    private $authErrorMsg       = '没有服务权限';
    private $dateFormatMsg      = '日期格式错误或不可选择';
    private $timeNodeMsg        = '不可选排期时间段';
    private $currDateNodeMsg    = '时间已经过去了, 不能选择';
    private $notRightTimeNode   = '预留时间不足一个小时, 不可选择';

    /**
     * 验证教练资质: 在排期业务中请求服务类型必须教练通过资质保持一致
     * @param $type
     * @param $uid
     * @return mixed
     */
    public function verify($type, $uid)
    {
        $scheduleLogic = ScheduleLogic::getInstance();
        if (!$type || !$scheduleLogic->simpleCheckAuth($type)) throw new HttpException(200, $this->requestErrorMsg);
        $auth = CoachAuthLogic::getInstance()->get($uid);
        if (!$auth) throw new HttpException(200, $this->authErrorMsg);
        $authInfo = CoachAuthLogic::getInstance()->getLevelList($auth);
        //资质类型验证
        if (!$scheduleLogic->checkCoachAuth($authInfo, $scheduleLogic->formatInputType($type))) throw new HttpException(200, $this->authErrorMsg);
        return $authInfo;
    }

    /**
     * 验证用户的日期以及排期有效性
     * @param string $date      日期
     * @param string $timeNode  选择的时间节点
     * @param int $currZeroTime 当天零点[目的: 简化重复调用零点函数]
     */
    public function periods($date = '', $timeNode = '', $currZeroTime = 0)
    {
        if (!$date || !$timeNode || !$currZeroTime) throw new HttpException(200, $this->requestErrorMsg);
        $dateTime = strtotime($date);
        if (!Validate::dateFormat($date, 'Y-m-d') || $dateTime < $currZeroTime) throw new HttpException(200, $this->dateFormatMsg);
        if (preg_match('~[^0-9,]+~is', $timeNode)) throw new HttpException(200, $this->timeNodeMsg);
        $timeNdoes = explode(",", $timeNode);
        $timeNdoes = array_unique($timeNdoes);
        sort($timeNdoes);
        if ($currZeroTime == $dateTime) {
            if ((current($timeNdoes) < (currDateDelayTimeNode() + 1)) || (end($timeNdoes) > MAX_TIMENODE)) throw new HttpException(200, $this->currDateNodeMsg);
        }
        if ((current($timeNdoes) < MIN_TIMENODE) || (end($timeNdoes) > MAX_TIMENODE) ) throw new HttpException(200, $this->timeNodeMsg);
        //每个排期间隔时间必须满足一个小时原则
        $timeNodeGroup = timeNodeSingleListArrDivideGroup($timeNdoes);
        foreach ($timeNodeGroup as $value) {
            if (count($value) < 2) throw new HttpException(200, $this->notRightTimeNode);
        }
    }

    /**
     * 验证用户的日期以及排期有效性
     * @param string $date      日期
     * @param string $timeNode  选择的时间节点
     * @param int $currZeroTime 当天零点[目的: 简化重复调用零点函数]
     */
    public function teachingTheme($coach_id, $authInfo)
    {
        // 获取教练服务类型
        $coachInfo = CoachModel::getInstance()->getCoachInfo($this->uid, "service_type, resume, sports_ids");
        if (!$coachInfo || $coachInfo["resume"] != 1) return error_out("", $this->authErrorMsg);
        if (isset($authInfo["pd"]) && $authInfo["pd"] == $coachInfo["service_type"]) {
            CourseModel::getInstance()->courseJoinCourseCoachSelect(["cc.coach_id" => $coach_id, "c.professional_type_id" => $coachInfo["sports_ids"]], "");
        }



//        $coachInfo = CoachModel::getInstance()->getCoachInfo($this->uid, "service_type, resume, sports_ids");
//        if (!$coachInfo || $coachInfo["resume"] != 1) return error_out("", $this->authErrorMsg);
//        $where["coach_id"] = $this->uid;
//        $where["type_id"]  = $this->serviceType[$coachInfo["service_type"]];
//        $where["sport_id"] = $coachInfo["sports_ids"];
//        $authRight = CoachAuthLogic::getInstance()->getAuthFind($where, "type_id, level_id, sport_id");
//        if (!$authRight) return error_out("", $this->authErrorMsg);
//        $data['serviceType'] = $coachInfo["service_type"] === 1 ? 2 : 1; // 服务状态
    }
}
