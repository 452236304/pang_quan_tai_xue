<?php
namespace CApi\Model;

class DiscoverNavModel extends CommonModel
{

    public function nav(){
        $data = $this->order('sort')->select();
        return $data;
    }
}