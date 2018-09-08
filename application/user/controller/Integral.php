<?php
namespace app\user\controller;
use app\common\logic\MsgLogic;
use app\user\model\TruckModel;
use app\user\logic\OrderLogic;
use app\user\model\IntegralModel;
use app\user\logic\UserLogic;
use app\common\push\Push;
use think\Cache;
use think\Config;


class Integral extends Base
{
    // 积分商城
    public function index(){
        $integral = IntegralModel::getInstance()->integralList([], "id, title, integral") ?: [];
        foreach ($integral as $key => $value){
            $imageArr = [];
            $images = TruckModel::getInstance()->certList(["main_id"=>$value["id"], "type"=> IntegralModel::CERT_TYPE], "img");
            foreach ($images as $ks => $vs){
                $imageArr[] = $vs["img"];
            }
            $integral[$key]["images"] = $imageArr;
        }
        return success_out($integral);
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

    // 积分兑换
    public function integralBuy(){
        $user_id = UserLogic::getInstance()->checkToken();
        $good_id = $this->request->post('good_id/d', 0);
        $name    = $this->request->post('name/s', "");
        $phone   = $this->request->post('phone/s', "");
        $addr    = $this->request->post('addr/s', "");
        if(!$name || !$phone || !$addr) return error_out("", MsgLogic::PARAM_MSG);
        $code = OrderLogic::getInstance()->makeCode();
        $data["code"]    = $code;
        $data["user_id"] = $user_id;
        $data["name"]    = $name;
        $data["phone"]   = $phone;
        $data["addr"]    = $addr;
        $data["good_id"] = $good_id;
        $data["status"]  = 1;
        $order = IntegralModel::getInstance()->integralOrderAdd($data);
        if($order === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("兑换成功");
    }






}
