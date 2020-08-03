<?php
namespace CApi\Model;

class SpecialV3Model extends CommonModel
{

    public function getList($where, $offset = 0, $limit = 10, $sort = 'S.id', $order = 'desc')
    {
        $data['total'] = $this->alias('S')
            ->join('__SPECIAL_CATEGORY__ AS SC ON SC.id = S.category_id')
            ->join('__SPECIAL_AUTHOR__ AS SA ON SA.id = S.author_id', 'left')
            ->where($where)
            ->count();
        $data['rows'] = $this->alias('S')
            ->join('__SPECIAL_CATEGORY__ AS SC ON SC.id = S.category_id')
            ->join('__SPECIAL_AUTHOR__ AS SA ON SA.id = S.author_id', 'left')
            ->where($where)
            ->field('S.id, S.title, S.type, S.thumb, S.read_num, S.add_time, SC.title as category_name, name as author_name, avatar, SA.id author_id')
            ->order($sort.' '.$order)
            ->limit($offset. ',' . $limit)
            ->select();
        return $data;
    }

}