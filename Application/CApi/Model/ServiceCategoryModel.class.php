<?php
namespace CApi\Model;

class ServiceCategoryModel extends CommonModel
{

    public function serviceCategory(){
        $where = [
            'type' => 1,
            'status' => 1,
        ];
        $data = $this->where($where)->order('ordernum')->select();
        return $data;
    }

}