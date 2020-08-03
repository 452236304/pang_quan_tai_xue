<?php
namespace CApi\Model;

class BrokerageLogModel extends CommonModel
{

    /**
     * Notes: 30天订单笔数
     * User: dede
     * Date: 2020/3/10
     * Time: 10:27 下午
     * @param $user_id
     */
    public function orderNumbsMonth($user_id){
        $where = [
            'user_id' => $user_id,
            'settle_time' => ['egt', strtotime(date('Y-m', strtotime('-30 day')))],
            'status' => 1,
        ];
        $orders = $this->where($where)->getField('order_id', true);
        $number = count(array_unique(array_filter($orders)));
        return intval($number);
    }

    /**
     * Notes: 30天订单金额
     * User: dede
     * Date: 2020/3/10
     * Time: 10:37 下午
     * @param $user_id
     * @return mixed
     */
    public function orderAmountMonth($user_id){
        $where = [
            'user_id' => $user_id,
            'settle_time' => ['egt', strtotime(date('Y-m', strtotime('-30 day')))],
            'status' => 1,
        ];
        $amount = $this->where($where)->sum('order_amount');
        return round($amount, 2);
    }

    /**
     * Notes: 30天收益
     * User: dede
     * Date: 2020/3/10
     * Time: 10:38 下午
     * @param $user_id
     */
    public function earningsMonth($user_id){
        $where = [
            'user_id' => $user_id,
            'settle_time' => ['egt', strtotime(date('Y-m', strtotime('-30 day')))],
            'status' => 1,
        ];
        $amount = $this->where($where)->sum('amount');
        return round($amount, 2);
    }

    /**
     * Notes: 累计订单笔数
     * User: dede
     * Date: 2020/3/10
     * Time: 10:27 下午
     * @param $user_id
     */
    public function orderNumbs($user_id){
        $where = [
            'user_id' => $user_id,
            'status' => 1,
        ];
        $orders = $this->where($where)->getField('order_id', true);
        $number = count(array_unique(array_filter($orders)));
        return intval($number);
    }

    /**
     * Notes: 累计订单金额
     * User: dede
     * Date: 2020/3/10
     * Time: 10:37 下午
     * @param $user_id
     * @return mixed
     */
    public function orderAmount($user_id){
        $where = [
            'user_id' => $user_id,
            'status' => 1,
        ];
        $amount = $this->where($where)->sum('order_amount');
        return round($amount, 2);
    }

    /**
     * Notes: 累计收益
     * User: dede
     * Date: 2020/3/10
     * Time: 10:38 下午
     * @param $user_id
     */
    public function earnings($user_id){
        $where = [
            'user_id' => $user_id,
            'status' => 1,
        ];
        $amount = $this->where($where)->sum('amount');
        return round($amount, 2);
    }
    

}