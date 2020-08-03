<?php
namespace Admin\Model;

class UnivArticleModel extends CommonModel{

    public function byCategory($category_id){
        $where = ['cat_id' => $category_id];
        $data = $this->where($where)->select();
        return $data;
    }
}