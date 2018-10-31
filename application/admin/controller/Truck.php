<?php
namespace app\admin\controller;
use app\common\config\DriverConfig;
use app\common\logic\MsgLogic;
use app\admin\model\TruckModel;
use app\common\push\Push;
use think\Cache;
use think\Config;


class Truck extends Base
{
    /**
     * 函数的含义说明
     *
     * @access  public
     * @author  niuzhenxiang
     * @param mixed token  用户token
     * @return array
     * @date  2018/02/09
     */
    private $truck_param= ["load"=>"吨", "length"=>"长", "wide"=>"宽", "high"=>"高", "cube"=>"方"];

    // 列表
    public function lst()
    {
        //$pageNumber = request()->post('pageNumber', '1');
        //$pageSize = request()->post('pageSize', '10');
        //$pages = $pageNumber . ', ' . $pageSize;
        $list = TruckModel::getInstance()->truckList([], "id, type, load, length, wide, high, cube") ?: [];
        foreach ($list as $key => $value){
            $imageArr = [];
            $list[$key]['title'] = DriverConfig::getInstance()->truckTypeNameId($value['type']);
            $images = TruckModel::getInstance()->certList(["main_id"=>$value["id"], "type"=>TruckModel::CERT_TYPE], "img");
            foreach ($images as $ks => $vs){
                $imageArr[] = $vs["img"];
            }
            $list[$key]["images"] = $imageArr;
            unset($list[$key]['type']);
         }
        $total = TruckModel::getInstance()->truckTotal();
        return json(['total' => $total, 'list' => $list, 'msg' => '']);
    }

    // 添加
    public function add(){
        $data["type"] = request()->post('type/d', 0);
        $data["load"] = request()->post('load/s', "");
        $data["length"] = request()->post('length/s', "");
        $data["wide"] = request()->post('wide/s', "");
        $data["high"] = request()->post('high/s', "");
        $data["cube"] = request()->post('cube/s', "");
        if (!$data["type"] || !$data["load"] || !$data["length"] || !$data["wide"] || !$data["high"] || !$data["cube"]) return error_out("", "参数错误");
        $result = TruckModel::getInstance()->truckAdd($data);
        if ($result === false) return error_out("", "服务器异常");
        return success_out("", MsgLogic::SUCCESS);
    }

    // 修改
    public function edit(){
        $id = request()->post('id/d', 0);
        $data["type"] = request()->post('type/d', 0);
        $data["load"] = request()->post('load/s', "");
        $data["length"] = request()->post('length/s', "");
        $data["wide"] = request()->post('wide/s', "");
        $data["high"] = request()->post('high/s', "");
        $data["cube"] = request()->post('cube/s', "");
        if (!$id) return error_out("", "参数错误");
        foreach ($data as $key => $val) {
            if (empty($val)) unset($data[$key]);
        }
        $result = TruckModel::getInstance()->truckEdit(["id"=>$id], $data);
        if ($result === false) return error_out("", "服务器异常");
        return success_out("", MsgLogic::SUCCESS);
    }

    public function del(){
        $id = request()->post('id/d', 0);
        if (!$id) return error_out("", "参数错误");
        $result = TruckModel::getInstance()->truckEdit(["id"=>$id], ["is_del"=>1]);
        if ($result === false) return error_out("", "服务器异常");
        return success_out("", MsgLogic::SUCCESS);
    }




}
