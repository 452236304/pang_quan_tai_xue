<?php
namespace Admin\Model;
use Think\Model;
class SysuserModel extends Model{
	/*
	 * 获取单条用户信息
	 * @param	$id			int		会员id
	 * @return	$data		array	用户信息	
	 */
    public function getOneById($id){
    	return $this->find($id);
    }

    /*
	 * 根据用户分组获取用户信息
	 * @param	$id		int		分组id
	 * @return	$res		bool	是否更新成功	
	 */
    public function getByTypeid($id){
    	return $this->where("typeid=$id")->select();
    }

    /*
	 * 更新用户信息
	 * @param	$id			int		会员id
	 * @param	$data		array	会员数据模型
	 * @return	$res		bool	是否更新成功	
	 */
    public function saveOneById($id,$data){
    	return $this->where("id=$id")->save($data);
    }

    /*
	 * 根据用户分组更新用户信息
	 * @param	$id			int		分组id
	 * @param	$data		array	会员数据模型
	 * @return	$res		bool	是否更新成功	
	 */
    public function saveByTypeid($id,$data){
    	return $this->where("typeid=$id")->save($data);
    }
}
?>