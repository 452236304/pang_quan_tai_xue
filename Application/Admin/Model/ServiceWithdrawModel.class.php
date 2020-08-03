<?php
namespace Admin\Model;

class ServiceWithdrawModel extends CommonModel
{

    public function getList($where, $offset = 0, $limit = 10, $sort = 'id', $order = 'desc')
    {
        $data['total'] = $this->alias('WW')
            ->join('__USER__ AS U ON U.id = WW.user_id', 'left')
            ->where($where)
            ->count();
        $field = ['U.nickname', 'WW.*'];
        $data['rows'] = $this->alias('WW')
            ->join('__USER__ AS U ON U.id = WW.user_id', 'left')
            ->where($where)
            ->field($field)
            ->order( $sort . ' ' . $order )
            ->limit( $offset . ',' . $limit )
            ->select();
        return $data;
    }
}