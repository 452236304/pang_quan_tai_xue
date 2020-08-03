<?php
namespace CApi\Model;

class PublicBenefitCommentModel extends CommonModel
{

    public function getList($where, $offset = 0, $limit = 10, $sort = 'add_time', $order = 'desc')
    {
        $data['total'] = $this->alias('PBC')
            ->join('__USER__ AS U ON U.id = PBC.user_id')
            ->where($where)
            ->count();
        $field = ['U.nickname', 'U.avatar', 'PBC.*'];
        $data['rows'] = $this->alias('PBC')
            ->join('__USER__ AS U ON U.id = PBC.user_id')
            ->where($where)
            ->field($field)
            ->order( $sort . ' ' . $order )
            ->limit( $offset . ',' . $limit )
            ->select();
        return $data;
    }
    public function getLists($where, $sort = 'add_time', $order = 'desc')
    {
        $data['total'] = $this->alias('PBC')
            ->join('__USER__ AS U ON U.id = PBC.user_id')
            ->where($where)
            ->count();
        $field = ['U.nickname', 'U.avatar', 'PBC.*'];
        $data['rows'] = $this->alias('PBC')
            ->join('__USER__ AS U ON U.id = PBC.user_id')
            ->where($where)
            ->field($field)
            ->order( $sort . ' ' . $order )
            ->select();
        return $data;
    }
}