<?php
namespace CApi\Controller;
use Think\Controller;
//搜索
class Searchontroller extends BaseController {
	/* 
	 * 历史搜索记录 未登陆时查询总搜索记录内热门的
	 */
	public function history(){
		$keyword=I('get.keyword');
		$map = array();
		$map['keyword']=array('like','%'.$keyword.'%');
		$list=D('hot_search')->where($map)->limit(0,10)->order('weight desc')->select();
		return $list;
	}
}