<?php
namespace CApi\Model;

class MomentCategoryModel extends CommonModel
{

    public function getByTag($tag){
        $where = ['tag'=> $tag];
        $data = $this->where($where)->find();
        return $data;
    }

    public function children($parent_id){
        $where = [ 'parent_id' => $parent_id ];
        $data = $this
            ->where($where)
            ->order('sort')
            ->field('id, name')
            ->select();
        return $data;
    }
}