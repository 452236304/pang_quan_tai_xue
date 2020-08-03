<?php
namespace CApi\Model;

class UnivNavBannerModel extends CommonModel
{

    public function nav($nav_id){
        $where = [ 'nav_id' => $nav_id, 'status' => 1 ];
        $data = $this->where($where)->order('sort')->select();
        foreach ( $data as &$item ){
            $item['param'] = json_decode($item['param'], true);
            $item['image'] = DoUrlHandle($item['image']);
        }
        return $data;
    }
}