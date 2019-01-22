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
            $memberSpecsList = MemberModel::getInstance()->memberSpecsList(["member_id"=>$val["id"]], "id, validity_day, give_day, origin_price, price") ?: [];
            $val["specs"] = $memberSpecsList;
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
        $order['code']   = OrderLogic::getInstance()->makeCode();
        $order['driver_id']= $user_id;
        $order['member_id']= $member_id;
        $order['member_specs_id']= $specs_id;
        $order['status']   = 1;
        $order['title']    = $member_specs_info["title"];
        $order['type']     = $member_specs_info["type"];
        $order['back_img'] = $member_specs_info["style"];
        $order['limit_second'] = $member_specs_info["limit_second"];
        $order['up_limit_number'] = $member_specs_info["up_limit_number"];
        $order['validity_day'] = $member_specs_info["validity_day"];
        $order['give_day'] = $member_specs_info["give_day"];
        $order['origin_price'] = $member_specs_info["origin_price"];
        $order['present_price'] = $member_specs_info["price"];
        $order['pay_type'] = $pay_type;
        $order['rights'] = $member_specs_info["rights"];
        $order['notes'] = $member_specs_info["notes"];
        $order_id = MemberModel::getInstance()->memberOrderInsertGetId($order);
        if($order_id === false) return error_out('', MsgLogic::SERVER_EXCEPTION);
        if ($pay_type === 1) { // 微信支付
            $data["wxData"] = OrderLogic::getInstance()->payWx($order['code'], $order['present_price'], url('driver/pay/notifyWxMemberPay', '', true, true), "亟亟城运司机端购买会员卡");//亟亟城运会员购买
        } else {  // 支付宝支付
            $data['zfbData'] = OrderLogic::getInstance()->payZfb($order['code'], $order['present_price'], url('driver/pay/notifyZfbMemberPay', '', true, true), "亟亟城运司机端购买会员卡");
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
