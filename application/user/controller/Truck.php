<?php
namespace app\user\controller;
use app\common\logic\MsgLogic;
use app\user\model\TruckModel;
use app\user\model\UsersModel;
use app\user\logic\UserLogic;
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

    private $truck_type = [1=>"小面包车", 2=>"中面包车", 3=>"小货车", 4=>"大货车"];
    private $truck_param= ["load"=>"吨", "long"=>"长", "wide"=>"宽", "high"=>"高", "cube"=>"方"];

    // 货车信息
    public function index()
    {
        $truckList = TruckModel::getInstance()->truckList([], "id, type, load, long, wide, high, cube") ?: [];
        foreach ($truckList as $key => $value){
            $imageArr = [];
            $truckList[$key]['title'] = $this->truck_type[$value['type']];
            $images = TruckModel::getInstance()->certList(["main_id"=>$value["id"], "type"=>TruckModel::CERT_TYPE], "img");
            foreach ($images as $ks => $vs){
                $imageArr[] = $vs["img"];
            }
            $truckList[$key]["images"] = $imageArr;
            unset($truckList[$key]['type']);
         }
        return success_out($truckList);
    }





}
