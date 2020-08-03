<?php
namespace CApi\Service;

class BrokerageService
{

    protected $settlePeriod = 14;   //结算周期 天

    /**
     * Notes: 订单支付成功回调生成业绩日志
     * User: dede
     * Date: 2020/3/4
     * Time: 6:43 下午
     * @param $order_type  订单类型  1：机构订单  2：商品订单  3：服务订单
     * @param $order_id     订单id
     */
    public function orderSettle($order_type, $order_id){
        $product = [];
        if( $order_type == 1 ){
            // 20200610 wjk 机构订单不分佣
            return true;
            $order = D('OrgOrder')->find($order_id);
            if( !$order['amount'] ){
                return true;
            }
            // 1元参观订单不计入业绩  没有入住时间不能计算业绩
            if( $order['type'] == 1 || !$order['attribute3'] ){
                return true;
            }
            $user_id = $order['userid'];
            $product[] = [
                'order_id' => $order_id,
                'order_type' => $order_type,
                'product_id' => $order['objectid'],
                'brokerage' => $order['brokerage'],
                'quantity' => 1,
                'settle_time' => strtotime($order['attribute3']) + $this->settlePeriod * 86400,     // 入住日期
                'order_amount' => $order['amount']
            ];
        }else if( $order_type == 2 ){
            $order = D('ProductOrder')->find($order_id);
            if( !$order['amount'] ){
                return true;
            }
            $user_id = $order['userid'];
            $order_info = D('ProductOrderProduct')->where(['orderid' => $order_id])->select();
            foreach ( $order_info as $value){
                $product[] = [
                    'order_id' => $order_id,
                    'order_type' => $order_type,
                    'product_id' => $value['productid'],
                    'brokerage' => $value['brokerage'],
                    'quantity' => $value['quantity'],
                    'order_amount' => $value['price'] * $value['quantity']
                ];
            }
        }else if( $order_type == 3 ){
            $order = D('ServiceOrder')->find($order_id);
            if( !$order['amount'] ){
                return true;
            }
            $user_id = $order['userid'];
            $product[] = [
                'order_id' => $order_id,
                'order_type' => $order_type,
                'product_id' => $order['projectid'],
                'brokerage' => $order['brokerage'],
                'quantity' => 1,
                'settle_time' => strtotime($order['begintime']) + $this->settlePeriod * 86400,      // 服务开始时间
                'order_amount' => $order['amount']
            ];
			
        }
        if( !$product ){
            return true;
        }

        // 没用团队，不进行分佣
        $user = D('User')->find($user_id);
        if( !$user['is_team'] ){
            return true;
        }
        $team = array_filter(explode('-', $user['team_path']));

        array_push($team, $user_id);
        $team = array_merge($team);
        $profit_percent = D('Constant')->where(['tag' => 'team_profit'])->getField('value');
        $profit_percent = json_decode($profit_percent, true);
        // 获取分佣用户列表和比例
        if( count($team) == 1 ){
            $profit_percent = [0];

        }else if( count($team) == 2 ){
            $profit_percent = $profit_percent['part1'];

        }elseif ( count($team) > 2 ){
            $team = array_slice($team, count($team)-3, count($team));
            $profit_percent = $profit_percent['part2'];
        }


        $log = [];
        foreach ($product as $item){
            if( $order_type == 2 ){
                $profit_percent = $this->getProfitPercent($team, $item['product_id']);
            }
            foreach ( $team as $key => $value){
                $total_amount = bcmul($item['brokerage'], $item['quantity'], 2);
                $amount = bcdiv($total_amount * $profit_percent[$key], 100, 2);
                if( $amount == 0 ){
                    continue;
                }
				
                $from_grade = count($team) - $key - 1;
                $log[] = [
                    'user_id' => $value,
                    'from_id' => $user_id,
                    'from_grade' => $from_grade,
                    'order_type' => $order_type,
                    'order_id' => $order_id,
                    'product_id' => $item['product_id'],
                    'total_amount' => $total_amount,
                    'percent' => $profit_percent[$key],
                    'amount' => $amount,
                    'add_time' => time(),
                    'settle_time' => $item['settle_time'] ? $item['settle_time'] : 0,
                    'status' => 0,
                    'order_amount' => $item['order_amount']
                ];
            }
        }
		
        if( !$log ){
            return true;
        }

        $res = D('BrokerageLog')->addAll($log);
        return $res;
    }


    /**
     * Notes: 订单退款后重新生成业绩日志
     * User: dede
     * Date: 2020/3/5
     * Time: 2:39 下午
     * @param $order_type
     * @param $refund_order_id
     * @return bool
     */
    public function orderRefund($order_type, $refund_order_id){
        $order_id = 0;
        if( $order_type == 1 ) {
            $refund_order = D('OrgOrderRefund')->find($refund_order_id);
            $order_id = $refund_order['orderid'];
            $order = D('OrgOrder')->find($order_id);
            // 1元参观订单不计入业绩  没有入住时间不能计算业绩
            if( $order['type'] == 1 || !$order['attribute3'] ){
                return true;
            }
        }else if( $order_type == 2 ) {
            $refund_order = D('ProductOrder')->find($refund_order_id);
            $order_id = $refund_order['orderid'];
        }else if( $order_type == 3 ) {
            $refund_order = D('ServiceOrderRefund')->find($refund_order_id);
            $order_id = $refund_order['orderid'];
        }

        // 出账账单回扣
        $where = [
            'order_type' => $order_type,
            'order_id' => $order_id,
            'status' => 1,
        ];
        $log = D('BrokerageLog')->where($where)->select();
        foreach ( $log as &$item ){
            $item['refund_order_id'] = $refund_order_id;
            $item['add_time'] = time();
            $item['total_amount'] = -$item['total_amount'];
            $item['amount'] = -$item['amount'];
            unset($log['id']);
        }
        $res = D('BrokerageLog')->addAll($log);
        if( !$res ){
            return false;
        }

        // 未出账账单设置无效
        $where = [
            'order_type' => $order_type,
            'order_id' => $order_id,
            'status' => 0,
        ];
        $log = D('BrokerageLog')->where($where)->select();
        if( $log ){
            $save = ['status'=>-1, 'refund_order_id' => $refund_order_id];
            $res = D('BrokerageLog')->where($where)->save($save);
            if( !$res ){
                return false;
            }
        }
        return true;
    }

    /**
     * Notes: 订单收货更新出账日期
     * User: dede
     * Date: 2020/3/5
     * Time: 6:49 下午
     * @param $order_id
     */
    public function receive($order_id){
        $order = D('ProductOrder')->find($order_id);
        if( !$order ){
            return false;
        }
        $receive_time = strtotime($order['shipping_receive_date']);
        if( !$receive_time ){
            return false;
        }
        $where = [
            'order_type' => 2,
            'order_id' => $order_id,
        ];
        $save = [
            'settle_time' => $receive_time + $this->settlePeriod * 86400
        ];
        $res = D('BrokerageLog')->where($where)->save($save);
        return $res;
    }

    /**
     * Notes: 按照出账日期进行出账
     * User: dede
     * Date: 2020/3/5
     * Time: 5:16 下午
     */
    public function settle(){
        $where = [
            'status' => 0,
            'settle_time' => [
                [ 'gt',  0 ],
                [ 'elt',  time() ],
            ]
        ];

        $log = D('BrokerageLog')->where($where)->select();
        foreach ( $log as $value ){
            D()->startTrans();
            // 修改为入账
            $save = [
                'id' => $value['id'],
                'status' => 1,
                'effective_time' => time(),
            ];
            $res = D('BrokerageLog')->save($save);
            if( !$res ){
                D()->rollback();
            }
            // 修改用户余额
            $remark = '';
            if( $value['refund_order_id'] ){
                // 退款
                if( $value['order_type'] == 1 ){
                    $remark = '机构订单'.$value['order_id'].'退款';
                }else if( $value['order_type'] == 2 ){
                    $remark = '商品订单'.$value['order_id'].'退款';
                }else if( $value['order_type'] == 3 ){
                    $remark = '服务订单'.$value['order_id'].'退款';
                }
            }else{
                if( $value['order_type'] == 1 ){
                    $remark = '机构订单'.$value['order_id'];
                }else if( $value['order_type'] == 2 ){
                    $remark = '商品订单'.$value['order_id'];
                }else if( $value['order_type'] == 3 ){
                    $remark = '服务订单'.$value['order_id'];
                }
            }
            $res = D('WalletLog', 'Service')->addLog($value['user_id'], $value['amount'], $remark);
            if( !$res ){
                D()->rollback();
            }
            D()->commit();
        }
        return true;
    }

    /**
     * Notes: 今日收益
     * User: dede
     * Date: 2020/3/10
     * Time: 10:10 下午
     * @param $user_id
     */
    public function earningsToday($user_id){
        $where = [
            'user_id' => $user_id,
            'settle_time' => [
                ['lt', strtotime(date('Y-m-d')) + 86400],
                ['egt', strtotime(date('Y-m-d'))],
            ],
            'status' => 1,
        ];
        $amount = D('BrokerageLog')->where($where)->SUM('amount');
        return floatval($amount);
    }

    public function history($user_id, $where = [] ){
        $data = [];
        $where['user_id'] = $user_id;
        // 机构订单
        $org_order = $this->orgOrder($where);
        if( $org_order ){
            $data = $org_order;
        }
        // 商品订单
        $product_order = $this->productOrder($where);
        if( $data ){
            $data = array_merge($data, $product_order);
        }else{
            $data = $product_order;
        }

        // 服务订单
        $service_order = $this->serviceOrder($where);
        if( $data ){
            $data = array_merge($data, $service_order);
        }else{
            $data = $service_order;
        }
        return $data;
    }

    public function orgOrder($where){
        $where['order_type'] = 1;
        if( $where['createdate'] ){
            $where['OO.createdate'] = $where['createdate'];
            unset($where['createdate']);
        }
        if( $where['keyword'] ){
            $where[] = [
                    'U.nickname' => ['like', '%'.$where['keyword'].'%'],
                    'U.mobile' => ['like', '%'.$where['keyword'].'%'],
                    'OO.title' => ['like', '%'.$where['keyword'].'%'],
                    '_logic' => 'OR',
                ];
            unset($where['keyword']);
        }
        $field = 'U.nickname, U.avatar, BL.amount, BL.`status`, BL.from_grade, OO.title, OO.sn, OO.createdate, OO.amount AS price';
        $data = D('BrokerageLog')->alias('BL')
            ->join('sj_org_order AS OO ON OO.id = BL.order_id')
            ->join('sj_user AS U ON U.id = BL.from_id')
            ->where($where)
            ->field($field)
            ->order('createdate DESC')
            ->select();
        return $data;
    }

    public function productOrder($where){
        $where['order_type'] = 2;
        if( $where['keyword'] ){
            $where[] = [
                    'U.nickname' => ['like', '%'.$where['keyword'].'%'],
                    'U.mobile' => ['like', '%'.$where['keyword'].'%'],
                    'P.title' => ['like', '%'.$where['keyword'].'%'],
                    '_logic' => 'OR',
                ];
            unset($where['keyword']);
        }
        $field = 'U.nickname, U.avatar, BL.amount, BL.`status`, BL.from_grade, P.title, PO.sn, PO.createdate, POP.price * POP.quantity AS price';
        $data = D('BrokerageLog')->alias('BL')
            ->join('sj_product_order_product AS POP ON POP.orderid = BL.order_id AND BL.product_id = POP.productid ')
            ->join('sj_product_order AS PO ON PO.id = POP.orderid')
            ->join('sj_product AS P ON P.id = POP.productid')
            ->join('sj_user AS U ON U.id = BL.from_id')
            ->where($where)
            ->field($field)
            ->order('createdate DESC')
            ->select();
        return $data;
    }

    public function serviceOrder($where){
        $where['order_type'] = 3;
        if( $where['createdate'] ){
            $where['SO.createdate'] = $where['createdate'];
            unset($where['createdate']);
        }
        if( $where['keyword'] ){
            $where[] = [
                    'U.nickname' => ['like', '%'.$where['keyword'].'%'],
                    'U.mobile' => ['like', '%'.$where['keyword'].'%'],
                    'SO.title' => ['like', '%'.$where['keyword'].'%'],
                    '_logic' => 'OR',
                ];
            unset($where['keyword']);
        }
        $field = 'U.nickname, U.avatar, BL.amount, BL.`status`, BL.from_grade, SO.title, SO.sn, SO.createdate, SO.amount AS price';
        $data = D('BrokerageLog')->alias('BL')
            ->join('sj_service_order AS SO ON BL.order_id = SO.id')
            ->join('sj_user AS U ON U.id = BL.from_id')
            ->where($where)
            ->field($field)
            ->order('createdate DESC')
            ->select();
        return $data;
    }

    /**
     * Notes: 商品订单可以设置单个商品的分销比例
     * 优先使用商品设置的分佣比例
     * User: dede
     * Date: 2020/6/10
     * Time: 4:08 下午
     * @param $team
     * @param $product_id
     */
    protected function getProfitPercent($team, $product_id){
        $product = D('Product')->find($product_id);
        $profit_percent = $product['team'];
        $profit_percent = json_decode($profit_percent, true);
        if( !$profit_percent || ($profit_percent['part1'][0] == 0
            && $profit_percent['part1'][1] == 0
            && $profit_percent['part2'][0] == 0
            && $profit_percent['part2'][1] == 0
            && $profit_percent['part2'][2] == 0
            )
        ){
            $profit_percent = D('Constant')->where(['tag' => 'team_profit'])->getField('value');
            $profit_percent = json_decode($profit_percent, true);
        }
        // 获取分佣用户列表和比例
        if( count($team) == 1 ){
            $profit_percent = [0];

        }else if( count($team) == 2 ){
            $profit_percent = $profit_percent['part1'];

        }elseif ( count($team) > 2 ){
            $profit_percent = $profit_percent['part2'];
        }
        return $profit_percent;
    }


}