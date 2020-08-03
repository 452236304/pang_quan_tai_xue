<?php
namespace CApi\Model;

class PublicBenefitModel extends CommonModel
{

    public function getList($where, $offset, $limit){
        $data['total'] = $this
            ->where($where)
            ->count();
        $data['rows'] = $this
            ->where($where)
            ->order('add_time DESC')
            ->limit( $offset . ',' . $limit )
            ->select();
        return $data;
    }
}