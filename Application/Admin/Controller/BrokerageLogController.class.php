<?php
namespace Admin\Controller;
use Think\Controller;

class BrokerageLogController extends BaseController {

    /**
     * Notes: 商品订单分佣明细
     * User: dede
     * Date: 2020/6/10
     * Time: 5:35 下午
     */
    public function productIndex(){
        $where = [
            'order_type' => 2,
        ];

        $field = ['BL.*', 'PO.sn', 'PO.nickname', 'PO.consignee', 'PO.mobile', 'PO.province', 'PO.city', 'PO.region', 'PO.address', 'PO.amount as order_amount', 'U.mobile as user_mobile'];
        $list = D('BrokerageLog')->alias('BL')
            ->join('sj_product_order AS PO ON BL.order_id = PO.id')
            ->join('sj_user AS U ON PO.userid = U.id')
            ->where($where)
            ->field($field)
            ->order('id desc')
            ->limit(0, 15)
            ->select();
        $grade = [1 => '直推合伙人', 2 => '间推合伙人'];
        $status = [0 => '未生效', 2 => '已生效', '-1' => '已取消'];
        foreach ( $list as &$item ){
            $item['status_text'] = $status[$item['status']];
            $item['grade'] = $grade[$item['from_grade']];
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
            $item['settle_time'] = $item['settle_time'] ? date('Y-m-d H:i:s', $item['settle_time']) : '';
            $item['effective_time'] = $item['effective_time'] ? date('Y-m-d H:i:s', $item['effective_time']) : '';
        }
        $this->assign('data', $list);
        $this->display();
    }


    public function serviceIndex(){
        $where = [
            'order_type' => 3,
        ];

        $field = ['BL.*', 'SO.sn', 'SO.nickname', 'SO.mobile', 'SO.province', 'SO.city', 'SO.region', 'SO.address', 'SO.amount as order_amount', 'U.mobile as user_mobile'];
        $list = D('BrokerageLog')->alias('BL')
            ->join('sj_service_order AS SO ON BL.order_id = SO.id')
            ->join('sj_user AS U ON SO.userid = U.id')
            ->where($where)
            ->field($field)
            ->order('id desc')
            ->limit(0, 15)
            ->select();
        $grade = [1 => '直推合伙人', 2 => '间推合伙人'];
        $status = [0 => '未生效', 2 => '已生效', '-1' => '已取消'];
        foreach ( $list as &$item ){
            $item['status_text'] = $status[$item['status']];
            $item['grade'] = $grade[$item['from_grade']];
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
            $item['settle_time'] = $item['settle_time'] ? date('Y-m-d H:i:s', $item['settle_time']) : '';
            $item['effective_time'] = $item['effective_time'] ? date('Y-m-d H:i:s', $item['effective_time']) : '';
        }
        $this->display('productIndex');
    }
}