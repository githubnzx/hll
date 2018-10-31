<?php
namespace app\admin\controller;
use app\common\logic\MsgLogic;
use app\common\logic\PageLogic;
use app\admin\model\TruckModel;
use app\admin\model\IntegralModel;
use think\Cache;
use think\Config;


class Integral extends Base
{
    // 列表
    public function lst(){
        $goodTitle = request()->post('title/s', '');
        $integral  = request()->post('integral/s', '');
        $pageNumber= request()->post('pageNumber/d', 1);
        $pageSize  = request()->post('pageSize/d', 10);
        $pages = $pageNumber . ', ' . $pageSize;
        //if (!$goodTitle || !$integral) return error_out("", MsgLogic::PARAM_MSG);
        $where = [];
        if ($goodTitle) $where["title"] = ["like", "%". $goodTitle ."%"];
        if ($integral) $where["integral"] = $integral;
        $list = IntegralModel::getInstance()->integralList($where, "id, title, integral", $pages) ?: [];
        foreach ($list as $key => $value){
            $images = TruckModel::getInstance()->certFind(["main_id"=>$value["id"], "type"=> IntegralModel::CERT_TYPE], "img", "id asc");
            $list[$key]["images"] = $images["img"];
        }
        return success_out($list);
    }

    // 积分详情
    public function info(){
        $good_id = $this->request->post('good_id/d', 0);
        if(!$good_id) return error_out("", MsgLogic::PARAM_MSG);
        $integralInfo = IntegralModel::getInstance()->integralFind(["id"=>$good_id], "id, title, integral, surplus_number") ?: [];
        $imageArr = [];
        $images = TruckModel::getInstance()->certList(["main_id"=>$integralInfo["id"], "type"=> IntegralModel::CERT_TYPE], "img");
        foreach ($images as $ks => $vs){
            $imageArr[] = $vs["img"];
        }
        $integralInfo["images"] = $imageArr;
        return success_out($integralInfo);
    }

    // 编辑
    public function edit(){
        $good_id = $this->request->post('good_id/d', 0);
        $data["title"]   = $this->request->post('title/s', "");
        $data["number"]  = $this->request->post('number/s', "");
        $data["surplus_number"]= $this->request->post('surplus_number/s', "");
        $data["integral"] = $this->request->post('integral/s', "");
        $image = $this->request->post('image/arr', []);
        if(!$good_id) return error_out("", MsgLogic::PARAM_MSG);
        foreach ($data as $key => $val) {
            if (empty($val)) unset($data[$key]);
        }
        $order = IntegralModel::getInstance()->integralGoodEdit($good_id, $data, $image);
        if($order === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }

    // 添加
    public function add(){
        $data["title"]   = $this->request->post('title/s', "");
        $data["number"]  = $this->request->post('number/d', 0);
        $data["surplus_number"]= $this->request->post('surplus_number/d', 0);
        $data["integral"] = $this->request->post('integral/s', "");
        $image = $this->request->post('image/a', []);
        if(!$data["title"] || !$data["number"] || !$data["surplus_number"] || !$data["integral"]) return error_out("", MsgLogic::PARAM_MSG);
        $order = IntegralModel::getInstance()->integralGoodAdd($data, $image);
        if($order === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }

    // 删除
    public function del(){
        $id = $this->request->post('good_id/s', "");
        if(!$id) return error_out("", MsgLogic::PARAM_MSG);
        $result = IntegralModel::getInstance()->integralEdit(["id"=>$id], ["is_del"=>1]);
        if($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }





}
