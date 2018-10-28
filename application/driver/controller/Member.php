<?php
namespace app\driver\controller;
use app\common\logic\MsgLogic;
use app\driver\logic\MsgLogic as DriverMsgLogic;
use app\driver\model\DriverModel;
use app\driver\logic\DriverLogic;
use app\driver\logic\OrderLogic;
use app\driver\model\MemberModel;
use app\user\logic\UserLogic;
use app\common\push\Push;
use think\Cache;
use think\Config;


class Member extends Base
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

    // 会员列表
    public function memberList(){
        //$user_id = DriverLogic::getInstance()->checkToken();
        $memberList = MemberModel::getInstance()->memberList([], "id, title, style, recommend, rights, notes") ?: [];
        foreach ($memberList as $key => &$val) {
            $val["style"] = handleImgPath($val["style"]);
        }
        return success_out($memberList);
    }
    // 会员卡详情
    public function memberInfo(){
        $member_id = $this->request->post('member_id/d', 0);
        if (!$member_id) return error_out("", MsgLogic::PARAM_MSG);
        // 会员卡信息
        $memberInfo = MemberModel::getInstance()->memberFind([], "title") ?: [];
        if(!$memberInfo) return error_out("", DriverMsgLogic::MEMBER_NOT_EXISTS);
        $memberSpecsList = MemberModel::getInstance()->memberSpecsList(["member_id"=>$member_id], "id, validity_day, give_day, origin_price, price") ?: [];
        $memberInfo["specs"] = $memberSpecsList;
        return success_out($memberInfo);
    }

    // 会员卡购买
    public function memberBuy(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $member_id= $this->request->param('member_id/d', 0);
        $specs_id = $this->request->param('member_specs_id/d', 0);
        $pay_type = $this->request->param('pay_type/d', 0); // 1微信 2支付宝
        if(!$member_id || !$specs_id |!$pay_type) return error_out('', '参数错误');
        // 获取当前购买会员卡规格
        $field = "m.title, m.type, m.style, m.rights, m.notes, m.limit_second, m.up_limit_number, m_s.validity_day, m_s.give_day, m_s.origin_price, m_s.price";
        $member_specs_info = MemberModel::getInstance()->memberJoinSpecsFind(['m_s.id'=>$specs_id], $field);
        if(!$member_specs_info) return error_out('', MsgLogic::SERVER_EXCEPTION);
        $data['code']   = OrderLogic::getInstance()->makeCode();
        $data['driver_id']= $user_id;
        $data['member_id']= $member_id;
        $data['member_specs_id']= $specs_id;
        $data['status']   = 1;
        $data['title']    = $member_specs_info["title"];
        $data['type']     = $member_specs_info["type"];
        $data['back_img'] = $member_specs_info["style"];
        $data['limit_second'] = $member_specs_info["limit_second"];
        $data['up_limit_number'] = $member_specs_info["up_limit_number"];
        $data['validity_day'] = $member_specs_info["validity_day"];
        $data['give_day'] = $member_specs_info["give_day"];
        $data['origin_price'] = $member_specs_info["origin_price"];
        $data['present_price'] = $member_specs_info["price"];
        $data['pay_type'] = $pay_type;
        $data['rights'] = $member_specs_info["rights"];
        $data['notes'] = $member_specs_info["notes"];
        $order_id = MemberModel::getInstance()->memberOrderInsertGetId($data);
        if($order_id === false) return error_out('', MsgLogic::SERVER_EXCEPTION);
        if ($pay_type === 1) { // 微信支付
            $data["wxData"] = OrderLogic::getInstance()->payWx($data['code'], $data['present_price'], url('user/pay/notifyWx', '', true, true), "APP");//亟亟城运会员购买
        } else {  // 支付宝支付

        }
        return success_out($data);
    }

    public function memebrReplace(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $member_id= $this->request->param('member_id/d', 0);
        if (!$member_id) return error_out("", MsgLogic::PARAM_MSG);
        // 查看是否是升级会员
        $myMemberID = MemberModel::getInstance()->memberUserFind(["driver_id"=>$user_id], "member_id")["member_id"] ?: 0;
        $data["status"] = $myMemberID != $member_id ? 1 : 0;
        return success_out($data);
    }


}
