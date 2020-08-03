<?php
namespace CApi\Service;

class PublicBenefitOrderService
{

    public function create($data){
        // 判断会员是否可以免费
        $info = D('public_benefit')->find($data['public_benefit_id']);
        $user = D('User')->find($data['userid']);
        if( $user['level'] && $info['vip_free'] ){
            $data['discount_amount'] = -$data['amount'];
            $data['status'] = 1;
            $data['pay_date'] = date('Y-m-d H:i:s');
        }else{
            $data['discount_amount'] = 0;
        }
        $data['total_amount'] = $data['amount'] + $data['discount_amount'];
        $data['createdate'] = date('Y-m-d H:i:s');
        $order_id = D('public_benefit')->add($data);
        return $order_id;
    }
}