<?php
/**
 * Created by PhpStorm.
 * User: php_s
 * Date: 2018/1/30
 * Time: 16:03
 */

namespace app\user\model;

use app\user\model\EvaluateModer;
use app\user\model\OrderModel;
use think\Db;
use think\Exception;
use think\exception\HttpException;

class EvaluateModel extends BaseModel
{
    const IS_DEL = 0; //1:删除 0:未删除
    private $table = 'evaluates';

    //检查唯一标识是否存在
    public function evaluateInsert($data){
        $data["create_time"] = CURR_TIME;
        $data["update_time"] = CURR_TIME;
        return Db::table($this->table)->insert($data);
    }

    // 添加评论和修改课程是否评论
    public function evaluateAdd($data){
        Db::startTrans();
        try {
            OrderModel::getInstance()->orderEdit(["id"=>$data["order_id"]], ["is_evaluates"=>1]);
            $this->evaluateInsert($data);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

}