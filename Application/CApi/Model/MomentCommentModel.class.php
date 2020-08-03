<?php
namespace CApi\Model;

class MomentCommentModel extends CommonModel
{

    public function getList($where, $offset = 0, $limit = 10, $sort = 'add_time', $order = 'desc')
    {
        $field = ['U.nickname', 'U.avatar', 'MC.*'];
        $data = $this->alias('MC')
            ->join('__USER__ AS U ON U.id = MC.user_id')
            ->where($where)
            ->field($field)
            ->order( $sort . ' ' . $order )
            ->limit( $offset . ',' . $limit )
            ->select();
        return $data;
    }
}