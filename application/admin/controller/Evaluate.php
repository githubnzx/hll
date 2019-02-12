<?php
namespace app\admin\controller;
use app\common\config\DriverConfig;
use app\common\logic\MsgLogic;
use app\admin\model\EvaluateModel;
use app\common\push\Push;
use think\Cache;
use think\Config;

ob_clean();

class Evaluate extends Base
{
    private $statusType = ["show"=>0, "hide"=>1];
    // 列表
    public function lst()
    {
        $name = request()->post("name/s", "");
        $pageNumber = request()->post('pageNumber', '1');
        $pageSize = request()->post('pageSize', '10');
        $pages = $pageNumber . ', ' . $pageSize;
        $where = [];
        if ($name) $where["d.name"] = $name;
        $list = EvaluateModel::getInstance()->evaluateDriverList($where, "e.id, d.id driver_id, d.name, e.star_level, e.content", $pages) ?: [];
        foreach ($list as $key => &$value){
             $value["total_level"] = $this->handleTotalLevel($value["driver_id"]);
         }
        $total = EvaluateModel::getInstance()->evaluateDriverCount($where);
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
        $result = EvaluateModel::getInstance()->evaluateEdit(["id"=>$driver_id], ["status"=>$this->statusType[$type]]);
        if ($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }


}
