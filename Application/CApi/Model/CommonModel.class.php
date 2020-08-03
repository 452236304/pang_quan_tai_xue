<?php
namespace CApi\Model;
use Think\Model;

class CommonModel extends Model {

    public function getList($where, $offset = 0, $limit = 10, $sort = 'id', $order = 'desc'){
        //$where['del_time'] = 0;
        $list['total'] = $this->where($where)->count();
        $list['rows'] = $this->where($where)->order( $sort . ' ' . $order)->limit($offset . ',' . $limit)->select();
        return $list;
    }

    public function getOne($id){
        return $this->find($id);
    }

    public function addOne($data){
        $data['add_time'] = time();
        return $this->add($data);
    }

    public function update($id, $data){
        $data['id'] = $id;
        return $this->save($data);
    }

    public function remove($id){
        return $this->where('id='.$id)->delete();
    }
}