<?php
namespace CApi\Controller;
use Think\Controller;
class BusinessController extends BaseController {
	//店铺详情
	public function detail(){
		$id = I('get.id');
		if($id==0){
			$id=1;
		}
		$map = array('id'=>$id);
		$info=D('business')->where($map)->find();
		$info['head']=$this->DoUrlHandle($info['head']);
		$info['thumb']=$this->DoUrlHandle($info['thumb']);
		$info['content']=$this->UEditorUrlReplace($info['content']);
		//是否已收藏
		$info["is_collection"] = '0';
		
		if($this->UserAuthCheckLogin()){
		    $user = $this->AuthUserInfo;
		    $user_record_model = D("user_record");
		
		    $map = array("userid"=>$user["id"], "source"=>6, "type"=>1, "objectid"=>$id);
		    $record = $user_record_model->where($map)->find();
		    if ($record) {
		        $info["is_collection"] = '1';
		    }
		}
		return $info;
	}
	//店铺热门
	public function hot_list(){
		//关键字 搜索
		$keyword = I('get.keyword');
		//排序 销量 0不排序 1升序 2降序
		$sale = I('get.sale',0);
		//排序 价格 0不排序 1升序 2降序
		$price = I('get.price',0);
		//排序 好评 0不排序 1升序 2降序
		$good = I('get.good',0);
		//热门品类 是否热门品类 0否 1是
		$hot = I('get.hot',0);
		//店铺ID
		$company_id = I('get.company_id',0);
		
		//店铺信息
		$map = array('id'=>$company_id);
		$business=D('business')->where($map)->find();
		
		
		//分类ID
		$category_id = I('get.category_id');
		if($business['is_self']==1 && $category_id){
			$category_pid = D('category')->where('id='.$category_id.' AND status=1')->find();
			if($category_pid['depth']=='1'){
				$typeids = D('category')->where('category_pid='.$category_pid['id'].' AND depth=2 AND status=1')->field('id')->select();
				$typeids = array_column($typeids,'id');
				$category_id = explode(",", $category_id);
				$all_typeid = array_merge($typeids,$category_id);
				$type_id = ['in',$all_typeid];
			}else{
				$type_id = $category_id;
			}
		}elseif($business['is_self']==0 && $category_id){
			$map = array('id'=>$category_id);
			$cate_info = D('business_category')->where($map)->find();
		}
		
		
		$page = I('get.page',1);
		$row = I('get.row',10);
		
		$model = D('product');
		
		$map = array();
		if($category_id){
			if($business['is_self']==1){
				$map['mo.typeid'] = $type_id;
			}else{
				if($cate_info['pid']==0){
					if($cate_info['id']){
						$map['mo.business_pcate']=$cate_info['id'];
					}
				}else{
					$map['mo.business_cate']=$cate_info['id'];
				}
			}
		}
		
		
		if(in_array($company_id,[0,1])){
			$map['mo.company_id']=array('in','0,1');
		}else{
			$map['mo.company_id']=$company_id;
		}
		if(!empty($keyword)){
			$map['mo.title']=array('like','%'.$keyword.'%');
			//$where['_logic']='or';
			//$map['_complex']=$where;
		}
		$order = array();
		if($hot>0){
			$map['mo.business_hot']=1;
		}
		if($sale==1){
			$order[]='mo.sales asc';
		}elseif($sale==2){
			$order[]='mo.sales desc';
		}
		
		if($price==1){
			$order[]='mo.price asc';
		}elseif($price==2){
			$order[]='mo.price desc';
		}
		$model->alias('mo');
		$begin = ($page-1)*$row;
		if($good==1){
			//按好评升序
			$order[]='score asc';
			$model->field('mo.*,AVG(comment.score) score')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid');
			$map['mo.status']=1;
			$map['mo.type']=0;
			$map['a.status']=1;
			$order = implode(',',$order);
			$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
			$count = $model->alias('mo')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid')->group('mo.id')->where($map)->count();
		
		}elseif($good==2){
			//按好评降序
			$order[]='score desc';
			$model->field('mo.*,AVG(comment.score) score')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid');
			$map['mo.status']=1;
			$map['mo.type']=0;
			$map['a.status']=1;
			$order = implode(',',$order);
			$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
			$count = $model->alias('mo')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid')->group('mo.id')->where($map)->count();
		
		}else{
			$model->field('mo.*')->join('LEFT JOIN sj_product_attribute pa on mo.id=pa.productid');
			$map['mo.status']=1;
			$map['mo.type']=0;
			$map['pa.status']=1;
			$order = implode(',',$order);
			$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
			$count = $model->alias('mo')->join('LEFT JOIN sj_product_attribute pa on mo.id=pa.productid')->group('mo.id')->where($map)->count();
		}
		
		foreach($list as $k=>$v){
			$v['label']=json_decode($v['label'],true);
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$v['images']=$this->DoUrlListHandle($v['images']);
			$v['market_price']=floatval($v['market_price']);
			$list[$k]=$v;
		}
		
		$totalpage = ceil($count/$row);
		$this->SetPaginationHeader($totalpage,$count,$page,$row);
		return $list;
	}
	//店铺分类页
	public function category(){
		//店铺ID
		$company_id = I('get.company_id',0);
		
		//查询店铺信息
		$map = array('id'=>$company_id);
		$business = D('business')->where($map)->find();
		if($business['type']==1){
			//商品分类
			if($business['is_self']==1){
				//自营店铺 使用商城原本的分类
				$list = D('column')->where('status=1')->select();
				foreach($list as $k=>&$v){
					$map = array('status'=>1,'category_pid'=>0,'parentid'=>$v['id']);
					$v['child']=D('category')->where($map)->select();
					foreach($v['child'] as $key=>&$value){
						$map = array('status'=>1,'category_pid'=>$value['id'],'parentid'=>$v['id']);
						$value['child']=D('category')->where($map)->select();
						foreach($value['child'] as $kk=>&$vv){
							$vv['thumb']=$this->DoUrlHandle($vv['thumb']);
						}
					}
				}
			}else{
				//普通店铺
				$map = array('status'=>1,'pid'=>0);
				if(in_array($company_id,[0,1])){
					$map['business_id']=array('in','0,1');
				}else{
					$map['business_id']=$company_id;
				}
				$list=D('business_category')->where($map)->order('ordernum asc')->select();
				foreach($list as $k=>&$v){
					$v['thumb']=$this->DoUrlHandle($v['thumb']);
					
					$v['child_cate']=D('business_category')->where($map)->order('ordernum asc')->select();
					foreach($v['child_cate'] as $key=>&$value){
						$value['thumb']=$this->DoUrlHandle($value);
					}
					$v['child_num']=count($v['child_cate']);
				}
			}
		}elseif($business['type']==2){
			//服务分类
			
		}elseif($business['type']==3){
			//机构分类
			
		}
		return $list;
	}
}
