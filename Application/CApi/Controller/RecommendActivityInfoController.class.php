<?php
namespace CApi\Controller;
use Think\Controller;
//推荐活动
class RecommendActivityInfoController extends BaseController {
	/**
	 * Notes: 活动详情
	 * User: LH
	 * Date: 2020/05/06
	 * Time: 16:06
	 */
	public function detail(){
		$user = $this->AuthUserInfo;
		$id = I('get.id');
		$map = array('id'=>$id,'status'=>1);
		$info = D('recommend_activity')->where($map)->find();
		if(empty($info)){
			E('活动已下架');
		}
		$info['com']=array('status'=>0);
		if($info['starttime']>date('Y-m-d H:i:s')){
			$info['com']=array('title'=>'活动未开始','status'=>1);
		}
		if($info['endtime']<date('Y-m-d H:i:s')){
			$info['com']=array('title'=>'活动已结束','status'=>1);
		}
		$info['price'] = floatval($info['price']);
		$info['thumb'] = $this->DoUrlHandle($info['thumb']);
		$info['post_image'] = $this->DoUrlHandle($info['post_image']);
		$info['examine_image'] = $this->DoUrlHandle($info['examine_image']);
		$info['content'] = $this->UEditorUrlReplace($info['content']);
		$info['group_image']=$this->DoUrlHandle('/Public/Home/img/20200507165912.png');//群二维码		$info['official_image']=$this->DoUrlHandle('/Public/Home/img/publicaccount.jpg');//公众号
		return $info;
	}
} 