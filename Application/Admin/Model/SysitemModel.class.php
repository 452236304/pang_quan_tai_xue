<?php
namespace Admin\Model;
use Think\Model;
class SysitemModel extends Model{
	/*
	 * 获取管理用户的管理菜单
	 * @param	$id		int		会员id
	 * @return	$data	array	用户信息
	 */
    public function getMenuListByUserid($id){ 
    	$sysuser = D("sysuser");
        $userinfo = $sysuser->find($id);
    	if($userinfo["types"]==0){
    		return $this->where("ismenu=1")->order("orderby asc")->select();
    	}else{
            $sysuserclassmodel = D("sysuserclass");
            $sysuserclass = $sysuserclassmodel->getOneById($userinfo["typeid"]);
            
            $power = empty($sysuserclass["power"])?0:$sysuserclass["power"];
            return $this->where("ismenu=1 and id in(".$power.")")->order("orderby asc")->select();
        }
    }

    /*
     * 获取管理菜单
     * @param   $id     int     会员id
     * @return  $data   array   信息列表    
     */
    public function getMenuList(){
        $data = $this->where("bid=0")->order("orderby asc")->select();
        foreach($data as $k=>$v){
            $data[$k]["child"] = $this->where("bid=".$v["id"])->order("orderby asc")->select();
        }
        return $data;
    }

    /*
     * 通过Url获取
     * @param   $url    string  控制器地址
     * @return  $data   array   信息列表    
     */
    public function getOneByUrl($url){
        return $this->where("url='$url'")->find();
    }
}
?>