<?php
namespace app\admin\controller;
use app\admin\model\DriverModel;
use app\common\config\DriverConfig;
use app\common\logic\MsgLogic;
use app\admin\model\EvaluateModel;
use app\common\model\CourseModel;
use app\common\push\Push;
use think\Cache;
use think\Config;


class Driver extends Base
{
    private $statusType = ["show"=>0, "hide"=>1];
    // 列表
    public function lst()
    {
        $name = request()->post("name/s", "");
        $phone = request()->post("phone/s", "");
        $audit_status = request()->post("audit_status/d", 0);
        $pageNumber = request()->post('pageNumber', '1');
        $pageSize = request()->post('pageSize', '10');
        $pages = $pageNumber . ', ' . $pageSize;
        $where = [];
        if ($name) $where["name"] = $name;
        if ($phone) $where["phone"] = $phone;
        if ($audit_status) $where["audit_status"] = $audit_status;
        $list = DriverModel::getInstance()->driverList($where, "id, name, phone, car_type, car_color, car_number, addr_info, audit_status, create_time") ?: [];
        foreach ($list as $key => &$value){
            $value["car_color"] = DriverConfig::getInstance()->getCarID($value["car_color"]);
            $value["car_type"] = DriverConfig::getInstance()->truckTypeNameId($value["car_type"]);
         }
        $total = DriverModel::getInstance()->driverCount($where);
        return json(['total' => $total, 'list' => $list, 'msg' => '']);
    }

    private function handleTotalLevel($driver_id){
        $data = [];
        if (isset($data[$driver_id])) {
            $totalLevel = $data[$driver_id];
        } else {
            $totalLevel = 0;
            $evaluateList = EvaluateModel::getInstance()->evaluateFind(["driver_id"=>$driver_id], "sum(star_level) total_level, count(id) number");
            if ($evaluateList) {
                $totalLevel = (float) bcdiv($evaluateList["total_level"], $evaluateList["number"], 2);
            }
        }
        return $totalLevel;
    }

    public function status(){
        $driver_id= request()->post('driver_id/d', 0);
        $type     = request()->post('type/s', "");
        if (!$driver_id || !$type) return error_out("", MsgLogic::PARAM_MSG);
        $result = EvaluateModel::getInstance()->evaluateDriverCount(["id"=>$driver_id], ["status"=>$this->statusType[$type]]);
        if ($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }


}
