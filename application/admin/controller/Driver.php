<?php
namespace app\admin\controller;
use app\admin\model\DriverModel;
use app\admin\model\TruckModel;
use app\common\config\DriverConfig;
use app\common\logic\MsgLogic;
use app\admin\model\EvaluateModel;
use app\common\model\CourseModel;
use app\common\push\Push;
use app\driver\logic\DriverLogic;
use think\Cache;
use think\Config;

ob_clean();

class Driver extends Base
{
    private $statusType = ["show"=>0, "hide"=>1];
    private $auditStatus = ["adopt"=>2, "nopass"=>3];
    private $cerType = [3 => "info", 4 => "id_number", 5 => "id_number", 6 => "travel", 7 => "car"];

    // 列表
    public function lst()
    {
        $name = request()->post("name/s", "");
        $phone = request()->post("phone/d", "");
        $id_number = request()->post("id_number/s", "");
        //$car_type = request()->post("car_type/d", 0);
        $audit_status = request()->post("audit_status/d", 0);
        $pageNumber = request()->post('pageNumber', '1');
        $pageSize = request()->post('pageSize', '10');
        $pages = $pageNumber . ', ' . $pageSize;
        $where = [];
        if ($name) $where["name"] = ["like", "%". $name ."%"];
        if ($phone) $where["phone"] = ["like", $phone ."%"];;
        if ($id_number) $where["id_number"] = ["like", "%". $id_number ."%"];
        //if ($car_type) $where["car_type"] = $car_type;
        if ($audit_status) {
            $where["audit_status"] = $audit_status;
        } else {
            $where["audit_status"] = 1;
        }
        $list = DriverModel::getInstance()->driverList($where, "id, name, phone, car_type, car_color, car_number, addr_info, audit_status, create_time", $pages, "id desc") ?: [];
        foreach ($list as $key => &$value){
            $value["car_color"] = DriverConfig::getInstance()->getCarID($value["car_color"]);
            $value["car_type"] = DriverConfig::getInstance()->truckTypeNameId($value["car_type"]);
         }
        $total = DriverModel::getInstance()->driverCount($where);
        return json(['total' => $total, 'list' => $list, 'msg' => '成功']);
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

    // 审核状态
    public function examine(){
        $driver_id= request()->post('driver_id/d', 0);
        $status   = request()->post('status/s', ""); // 状态 adopt 通过 nopass 不通过
        if (!$driver_id || !$status) return error_out("", MsgLogic::PARAM_MSG);
        $driverInfo = DriverModel::getInstance()->driverFind(["id"=>$driver_id], "id, phone");
        if (!$driverInfo) return error_out("", DriverLogic::DRIVER_NOT_EXISTS);
        $result = DriverModel::getInstance()->driverAuditEdit($driver_id, $this->auditStatus[$status], $driverInfo["phone"]);
        if ($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }

    // 照片
    public function photo(){
        $driver_id= request()->post('driver_id/d', 0);
        if (!$driver_id) return error_out("", MsgLogic::PARAM_MSG);
        $certList = TruckModel::getInstance()->certList(["main_id" => $driver_id], "type, img");
        $data = [];
        foreach ($certList as $key => $value){
            if(isset($this->cerType[$value["type"]])) {
                $data[$this->cerType[$value["type"]]] = handleImgPath($value["img"]);
            }
        }
        return success_out($data, MsgLogic::SUCCESS);
    }


}
