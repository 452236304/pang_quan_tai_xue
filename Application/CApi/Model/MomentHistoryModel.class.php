<?php
namespace CApi\Model;

class MomentHistoryModel extends CommonModel
{

    public function getList($where, $offset = 0, $limit = 10, $sort = 'id', $order = 'desc')
    {
        $where['M.del_time'] = 0;
        $field = ['M.*'];
        $data['total'] = $this->alias('MH')
            ->join('__MOMENT__ AS M ON M.id = MH.moment_id')
            ->where($where)
            ->count();
        $data['rows'] = $this->alias('MH')
            ->join('__MOMENT__ AS M ON M.id = MH.moment_id')
            ->where($where)
            ->field($field)
            ->order( $sort . ' ' . $order)
            ->limit($offset . ',' . $limit)
            ->select();
        return $data;
    }

}