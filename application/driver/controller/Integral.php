<?php
namespace app\driver\controller;
use app\common\logic\MsgLogic;
use app\common\logic\PageLogic;
use app\driver\logic\DriverLogic;
use app\driver\model\DriverModel;
use app\user\logic\UserLogic;
use app\user\model\TruckModel;
use app\driver\logic\OrderLogic;
use app\driver\model\IntegralModel;
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
        $user_id = DriverLogic::getInstance()->checkToken();
        $good_id = $this->request->post('good_id/d', 0);
        $name    = $this->request->post('name/s', "");
        $phone   = $this->request->post('phone/s', "");
        $addrInfo= $this->request->post('addr_info/s', "");
        if(!$good_id || !$name || !$phone || !$addrInfo) return error_out("", MsgLogic::PARAM_MSG);
        if (!UserLogic::getInstance()->check_mobile($phone)) {
            return error_out('', UserLogic::USER_SMS_SEND);
        }
        // 检测商品剩余数量
        $surplusNumber = Cache::store('integral')->get('goods_id:' . $good_id);
        if($surplusNumber > 0) return error_out("", MsgLogic::INTEGRAL_SURPLUS_NUMBER);
        // 查看是否兑换过
        $id = IntegralModel::getInstance()->userIntegralGoodFind(["user_id"=>$user_id, "goods_id"=>$good_id, "user_type"=>DriverModel::USER_TYPE_USER], "id")["id"] ?: 0;
        if($id) return error_out("", MsgLogic::INTEGRAL_CONVERTIBILITY);
        // 获取积分商品
        $integral = IntegralModel::getInstance()->integralFind(["id"=>$good_id], "integral")["integral"] ?: 0;
        $code = OrderLogic::getInstance()->makeCode();
        $data["code"]    = $code;
        $data["user_id"] = $user_id;
        $data["name"]    = $name;
        $data["phone"]   = $phone;
        $data["addr_info"]= $addrInfo;
        $data["goods_id"] = $good_id;
        $data["integral"] = $integral;
        $data["user_type"]= DriverModel::USER_TYPE_USER;
        $data["date"]     = currZeroDateToTime();
        $data["status"]   = 1;
        $order = IntegralModel::getInstance()->integralOrderAdd($data);
        if($order === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::INTEGRAL_DH_SUCCESS);
    }

    // 兑换订单
    public function integralOrder(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $page = PageLogic::getInstance()->getPages();
        $status  = $this->request->post('status/d', 1);
        $order = IntegralModel::getInstance()->integralOrderSelect(["status"=>$status, "user_type"=>DriverModel::USER_TYPE_USER], "o.id order_id, g.id goods_id, g.title, g.integral", $page);
        foreach ($order as $key => $val){
            $order[$key]["images"] = TruckModel::getInstance()->certFind(["main_id"=>$val["goods_id"]], "img", "create_time asc")["img"] ?: "";
        }
        return success_out($order);
    }

    // 确认收货
    public function confirmReceipt(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $order_id= $this->request->post('order_id/d', 0);
        if(!$order_id) return error_out("", MsgLogic::PARAM_MSG);
        // 验证用户订单
        $orderStatus = IntegralModel::getInstance()->integralOrderFind(["user_id"=>$user_id, "id"=>$order_id], "status")["status"] ?: 0;
        if($orderStatus !== 1) return error_out("", MsgLogic::INTEGRAL_RECEIPT_MSG);
        $order = IntegralModel::getInstance()->integralOrderEdit(["id"=>$order_id], ["status"=>2]);
        if($order === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }






}
