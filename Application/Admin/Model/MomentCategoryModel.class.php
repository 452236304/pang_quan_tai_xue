<?php
namespace Admin\Model;

class MomentCategoryModel extends CommonModel {


    public function getAll(){
        $data = $this->getField('id, name, parent_id', true);
        return $data;
    }

    public function children($parent_id){
        $where = [ 'parent_id' => $parent_id ];
        $data = $this->where($where)->select();
        return $data;
    }
}