<?php
namespace Store\Model;
use Think\Model;
class SysuserclassStoreModel extends Model{
	/*
	 * 获取单条分组信息
	 * @param	$id		int		分组id
	 * @return	$data	array	分组信息	
	 */
    public function getOneById($id){
    	return $this->find($id);
    }

    /*
	 * 更新用户分组信息
	 * @param	$id		int		分组id
	 * @param	$data	array	分组数据模型
	 * @return	$res	bool	是否更新成功
	 */
    public function saveOneById($id,$data){
    	return $this->where("id=$id")->save($data);
    }
}
?>