<?php
namespace CApi\Controller;
use Think\Controller;
//小程序
class XcxController extends BaseController {
	//首页
	public function index(){
		//分类图标
		$bannermodel = D("banner");
		$map = array("status"=>1, "type"=>14);
		$cate = $bannermodel->where($map)->order("ordernum asc")->select();
		foreach ($cate as $k=>$v) {
		    $v["image"] = $this->DoUrlHandle($v["image"]);
		    
		    if($v["param"]){
		        $v["param"] = json_decode($v["param"], true);
		    } else{
		        $v["param"] = array("param_type"=>"-1", "param_id"=>"");
		    }
		
			$cate[$k] = $v;
		}
		
		//轮播图
		$map = array("status"=>1, "type"=>13);
		$banner = $bannermodel->where($map)->order("ordernum asc")->select();
		foreach ($banner as $k=>$v) {
		    $v["image"] = $this->DoUrlHandle($v["image"]);
		    
		    if($v["param"]){
		        $v["param"] = json_decode($v["param"], true);
		    } else{
		        $v["param"] = array("param_type"=>"-1", "param_id"=>"");
		    }
		
			$banner[$k] = $v;
		}
		
		//中间的大小广告图
		$map = array("status"=>1, "type"=>22);
		$big_banner = $bannermodel->where($map)->order("ordernum asc")->select();
		foreach ($big_banner as $k=>$v) {
		    $v["image"] = $this->DoUrlHandle($v["image"]);
		    
		    if($v["param"]){
		        $v["param"] = json_decode($v["param"], true);
		    } else{
		        $v["param"] = array("param_type"=>"-1", "param_id"=>"");
		    }
		
			$big_banner[$k] = $v;
		}
		$map = array("status"=>1, "type"=>23);
		$small_banner = $bannermodel->where($map)->order("ordernum asc")->select();
		foreach ($small_banner as $k=>$v) {
		    $v["image"] = $this->DoUrlHandle($v["image"]);
		    
		    if($v["param"]){
		        $v["param"] = json_decode($v["param"], true);
		    } else{
		        $v["param"] = array("param_type"=>"-1", "param_id"=>"");
		    }
		
			$small_banner[$k] = $v;
		}
		
		//每日秒杀
		$service_project=D('service_project')->field('id,thumb,title,subtitle,price,market_price,createdate,top,ordernum,label,home_label,home_label_after,seckill_orderby')->where('seckill=1')->select();
		foreach($service_project as $k=>$v){
			$v['label']=json_decode($v['label'],true);
			$v['label']['attr']=$v['label']['attr1'];
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$v['type']=1;
			$v['price']=($v['price']);;
			$v['market_price']=floatval($v['market_price']);
			$service_project[$k]=$v;
		}
		$map = array('p.seckill'=>1,"p.shelf"=>1, 'p.status'=>1,'a.status'=>1,'p.type'=>0);
		$seckill_product=D('product')->alias('p')->field('p.id,p.thumb,p.title,p.subtitle,p.price,p.market_price,p.createdate,p.top,p.ordernum,p.label,p.home_label,p.home_label_after,p.seckill_orderby')->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')->group('p.id')->where($map)->select();
		foreach($seckill_product as $k=>$v){
			$v['label']=json_decode($v['label'],true);
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$v['type']=2; 
			$v['price']=($v['price']);;
			$v['market_price']=floatval($v['market_price']);
			$seckill_product[$k]=$v;
		}
		
		$map = array('p.seckill'=>1,'p.status'=>1,"p.shelf"=>1, 'a.status'=>1,'p.type'=>2);
		$reform_product=D('product')->alias('p')->field('p.id,p.thumb,p.title,p.subtitle,p.price,p.market_price,p.createdate,p.top,p.ordernum,p.label,p.seckill_orderby')->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')->group('p.id')->where($map)->select();
		foreach($reform_product as $k=>$v){
			$v['label']=json_decode($v['label'],true);
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$v['type']=3; 
			$v['price']=($v['price']);;
			$v['market_price']=floatval($v['market_price']);
			$reform_product[$k]=$v;
		}
		
		$seckill_array=array_merge($service_project,$seckill_product,$reform_product);
		orderby($seckill_array,'seckill_orderby','asc');
		orderby($seckill_array,'top','desc');
		for($i=0;$i<=2;$i++){
			$seckill[$i]=$seckill_array[$i];
		}
		
		//客服电话
		$aboutmodel = D("about");
		$map = array("status"=>1, "id"=>4);
		$about = $aboutmodel->where($map)->find();
		if($about){
			$service = array("title"=>$about["title"], "tel"=>$about["content"]);
		}
		
		//小程序首页细项
		$info = F('xcx_config');
		$info['seckill_title'] = $info['seckill_title']?:'热门抢购';
		$info['seckill_subtitle'] = $info['seckill_subtitle']?:'优质佳品,抢不停!';
		$info['recommend_title'] = $info['recommend_title']?:'热门抢购';
		$info['recommend_subtitle'] = $info['recommend_subtitle']?:'品质严选,为您把关';
		
		$xcx_config = F('xcx_config');
		
		$invitation = D('user_invitation')->where('id=1')->find();
		
		return array('cate'=>$cate,'banner'=>$banner,'seckill'=>$seckill,'service'=>$service,'info'=>$info,'big_banner'=>$big_banner,'small_banner'=>$small_banner,'online_consulting'=>$this->DoUrlHandle($xcx_config['online_consulting']),'mobile_consulting'=>$this->DoUrlHandle($xcx_config['mobile_consulting']),'share_title'=>$invitation['share_title']);
	}
	
	//首页精品推荐
	public function index_recommend(){
		$row = I('get.row',6);
		$page = I('get.page',1);
		$begin = ($page-1)*$row;
		//精品推荐
		$service_project=D('service_project')->where('recommend=1 and status = 1')->select();
		foreach($service_project as $k=>$v){
			$v['label']=json_decode($v['label'],true);
			$v['label']['attr']=$v['label']['attr1'];
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$v['type']=1;
			$v['price']=($v['price']);;
			$v['market_price']=floatval($v['market_price']);
			$service_project[$k]=$v;
		}
		
		$map = array('p.recommend'=>1,'p.status'=>1,"p.shelf"=>1, 'a.status'=>1,'p.type'=>0);
		$recommend_product=D('product')->alias('p')->field('p.*')->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')->group('p.id')->where($map)->select();
		foreach($recommend_product as $k=>$v){
			$v['label']=json_decode($v['label'],true);
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$v['type']=2; 
			$v['price']=($v['price']);;
			$v['market_price']=floatval($v['market_price']);
			$recommend_product[$k]=$v;
		}
		$recommend=array_merge($service_project,$recommend_product);
		orderby($recommend,'recommend_orderby','asc');
		orderby($recommend,'top','desc');
		//for($i=$begin;$i<=$begin+$row;$i++){
			$list=$recommend;
		//}
		
		//查询插播的广告图
		$bannermodel = D("banner");
		$map = array("status"=>1, "type"=>24);
		$banner_begin = $page-1;
		$banner = $bannermodel->where($map)->limit($banner_begin,1)->order("ordernum asc")->select();
		foreach ($banner as $k=>$v) {
		    $v["image"] = $this->DoUrlHandle($v["image"]);
		    $v['type'] = 4;
		    if($v["param"]){
		        $v["param"] = json_decode($v["param"], true);
		    } else{
		        $v["param"] = array("param_type"=>"-1", "param_id"=>"");
		    }
			$v['recommend_orderby'] = $v['ordernum'];
			$banner[$k] = $v;
		}
		$count = count($recommend);
		$totalpage = ceil($count/$row);
		$list = array_merge($list,$banner); 
		orderby($list,'recommend_orderby','asc');
		//$this->SetPaginationHeader($totalpage,$count,$page,$row);
		return $list;
	}
	
	//精品推荐
	public function recommend(){
		//类型 0全部 1服务 2商品
		$type = I('get.type',0);
		//关键字 搜索
		$keyword = I('get.keyword');
		//排序 销量 0不排序 1升序 2降序
		$sale = I('get.sale',0);
		//排序 价格 0不排序 1升序 2降序
		$price = I('get.price',0);
		//排序 好评 0不排序 1升序 2降序
		$good = I('get.good',0);
		
		$page = I('get.page',1);
		$row = I('get.row',10);
		
		if($type==1){
			$model = D('service_project');
		}elseif($type==2){
			$model = D('product');
		}else{
			
			$map = array('mo.recommend'=>1,"mo.shelf"=>1, 'mo.status'=>1,'mo.type'=>0,'a.status'=>1);
			if(!empty($keyword)){
				$map['mo.title']=array('like','%'.$keyword.'%');
			}
			$product=D('product')->alias('mo')->where($map)->group('mo.id')->field('mo.*,AVG(comment.score) score')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid')->select();
			foreach($product as $k=>$v){
				$v['label']=json_decode($v['label'],true);
				$v['thumb']=$this->DoUrlHandle($v['thumb']);
				$v['images']=$this->DoUrlListHandle($v['images']);
				$v['price']=($v['price']);;
				$v['market_price']=floatval($v['market_price']);
				$v['type']=2;
				$product[$k]=$v;
			}
			$map = array('mo.recommend'=>1,'mo.status'=>1);
			if(!empty($keyword)){
				$map['mo.title']=array('like','%'.$keyword.'%');
				//$where['_logic']='or';
				//$map['_complex']=$where;
			}
			$service = D('service_project')->alias('mo')->group('mo.id')->field('mo.*,AVG(comment.score) score')->join('left join sj_service_order sorder on mo.id=sorder.projectid')->join('left join sj_service_comment comment on sorder.id=comment.orderid')->where($map)->select();
			foreach($service as $k=>$v){
				$v['label']=json_decode($v['label'],true);
				$v['label']['attr']=$v['label']['attr1'];
				$v['thumb']=$this->DoUrlHandle($v['thumb']);
				$v['images']=$this->DoUrlListHandle($v['images']);
				$v['price']=($v['price']);;
				$v['market_price']=floatval($v['market_price']);
				$v['type']=1;
				$service[$k]=$v;
			}
			$list = array_merge($product,$service);
			//排序
			orderby($list,'recommend_orderby','asc');
			orderby($list,'top','desc');
			if($sale>0){
				if($sale==1){ 	//asc
					orderby($list,'sales','asc');
				}else{   		//desc
					orderby($list,'sales','desc');
				}
			}
			if($price>0){
				if($price==1){ 	//asc
					orderby($list,'price','asc');
				}else{   		//desc
					orderby($list,'price','desc');
				}
			}
			if($good>0){
				if($good==1){ 	//asc
					orderby($list,'score','asc');
				}else{   		//desc
					orderby($list,'score','desc');
				}
			}
			return $list;
		}
		
		$map = array();
		$map['mo.recommend'] = 1;
		if(!empty($keyword)){
			$map['mo.title']=array('like','%'.$keyword.'%');
			//$where['_logic']='or';
			//$map['_complex']=$where;
		}
		
		$order = array();
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
			if($type==1){
				$order[]='score asc';
				$model->field('mo.*,AVG(comment.score) score')->join('left join sj_service_order sorder on mo.id=sorder.projectid')->join('left join sj_service_comment comment on sorder.id=comment.orderid');
				$order[]='mo.recommend_orderby asc';
				$order = implode(',',$order);
				$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
				$count = $model->alias('mo')->join('left join sj_service_order sorder on mo.id=sorder.projectid')->join('left join sj_service_comment comment on sorder.id=comment.orderid')->group('mo.id')->where($map)->count();
			}else{
				$order[]='score asc';
				$model->field('mo.*,AVG(comment.score) score')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid');
				$map['mo.status']=1;
				$map['mo.shelf']=1;
				$map['mo.type']=0;
				$map['a.status']=1;
				$order[]='mo.recommend_orderby asc';
				$order = implode(',',$order);
				$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
				$count = $model->alias('mo')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid')->group('mo.id')->where($map)->count();
			}
		}elseif($good==2){
			//按好评降序
			if($type==1){
				$order[]='score desc';
				$model->field('mo.*,AVG(comment.score) score')->join('left join sj_service_order sorder on mo.id=sorder.projectid')->join('left join sj_service_comment comment on sorder.id=comment.orderid');
				$order[]='mo.recommend_orderby asc';
				$order = implode(',',$order);
				$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
				$count = $model->alias('mo')->join('left join sj_service_order sorder on mo.id=sorder.projectid')->join('left join sj_service_comment comment on sorder.id=comment.orderid')->group('mo.id')->where($map)->count();
			}else{
				$order[]='score desc';
				$model->field('mo.*,AVG(comment.score) score')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid');
				$map['mo.status']=1;
				$map['mo.shelf']=1;
				$map['mo.type']=0;
				$map['a.status']=1;
				$order[]='mo.recommend_orderby asc';
				$order = implode(',',$order);
				$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
				$count = $model->alias('mo')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid')->group('mo.id')->where($map)->count();
			}
		}else{
			if($type==1){
				$order[]='mo.recommend_orderby asc';
				$order = implode(',',$order);
				$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
				$count = $model->alias('mo')->where($map)->count();
			}else{
				$model->field('mo.*')->join('LEFT JOIN sj_product_attribute pa on mo.id=pa.productid');
				$map['mo.status']=1;
				$map['mo.shelf']=1;
				$map['mo.type']=0;
				$map['pa.status']=1;
				$order[]='mo.recommend_orderby asc';
				$order = implode(',',$order);
				$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
				$count = $model->alias('mo')->join('LEFT JOIN sj_product_attribute pa on mo.id=pa.productid')->group('mo.id')->where($map)->count();
			}
		}
		
		foreach($list as $k=>$v){
			$v['label']=json_decode($v['label'],true);
			if($type==1){
				$v['label']['attr']=$v['label']['attr1'];
			}
				
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$v['images']=$this->DoUrlListHandle($v['images']);
			$v['price']=($v['price']);;
			$v['market_price']=floatval($v['market_price']);
			$list[$k]=$v;
		}
		
		$totalpage = ceil($count/$row);
		$this->SetPaginationHeader($totalpage,$count,$page,$row);
		return $list;
	}
	
	//秒杀
	public function seckill(){
		//类型 0全部 1服务 2商品 3改造
		$type = I('get.type',0);
		//关键字 搜索
		$keyword = I('get.keyword');
		//排序 销量 0不排序 1升序 2降序
		$sale = I('get.sale',0);
		//排序 价格 0不排序 1升序 2降序
		$price = I('get.price',0);
		//排序 好评 0不排序 1升序 2降序
		$good = I('get.good',0);
		
		$page = I('get.page',1);
		$row = I('get.row',10);
		
		if($type==1){
			$model = D('service_project');
		}elseif($type==2 || $type==3){
			$model = D('product');
		}else{
			
			$map = array('mo.seckill'=>1,'mo.status'=>1,'mo.shelf'=>1,'mo.type'=>0,'a.status'=>1);
			if(!empty($keyword)){
				$map['mo.title']=array('like','%'.$keyword.'%');
			}
			$product=D('product')->alias('mo')->where($map)->group('mo.id')->field('mo.*,AVG(comment.score) score')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid')->select();
			foreach($product as $k=>$v){
				$v['label']=json_decode($v['label'],true);
				$v['thumb']=$this->DoUrlHandle($v['thumb']);
				$v['images']=$this->DoUrlListHandle($v['images']);
				$v['price']=($v['price']);;
				$v['market_price']=floatval($v['market_price']);
				$v['type']=2;
				$product[$k]=$v;
			}
			$map = array('mo.seckill'=>1,'mo.status'=>1);
			if(!empty($keyword)){
				$map['mo.title']=array('like','%'.$keyword.'%');
				//$where['_logic']='or';
				//$map['_complex']=$where;
			}
			$service = D('service_project')->alias('mo')->group('mo.id')->field('mo.*,AVG(comment.score) score')->join('left join sj_service_order sorder on mo.id=sorder.projectid')->join('left join sj_service_comment comment on sorder.id=comment.orderid')->where($map)->select();
			foreach($service as $k=>$v){
				$v['label']=json_decode($v['label'],true);
				$v['label']['attr']=$v['label']['attr1'];
				$v['thumb']=$this->DoUrlHandle($v['thumb']);
				$v['images']=$this->DoUrlListHandle($v['images']);
				$v['price']=($v['price']);;
				$v['market_price']=floatval($v['market_price']);
				$v['type']=1;
				$service[$k]=$v;
			}
			
			$map = array('mo.seckill'=>1,'mo.status'=>1,'mo.shelf'=>1,'mo.type'=>2,'a.status'=>1);
			if(!empty($keyword)){
				$map['mo.title']=array('like','%'.$keyword.'%');
			}
			$reform=D('product')->alias('mo')->where($map)->group('mo.id')->field('mo.*,AVG(comment.score) score')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid')->select();
			foreach($reform as $k=>$v){
				$v['label']=json_decode($v['label'],true);
				$v['thumb']=$this->DoUrlHandle($v['thumb']);
				$v['images']=$this->DoUrlListHandle($v['images']);
				$v['price']=($v['price']);;
				$v['market_price']=floatval($v['market_price']);
				$v['type']=2;
				$reform[$k]=$v;
			}
			
			$list = array_merge($product,$service,$reform);
			//排序
			orderby($list,'seckill_orderby','asc');
			orderby($list,'top','desc');
			if($sale>0){
				if($sale==1){ 	//asc
					orderby($list,'sales','asc');
				}else{   		//desc
					orderby($list,'sales','desc');
				}
			}
			if($price>0){
				if($price==1){ 	//asc
					orderby($list,'price','asc');
				}else{   		//desc
					orderby($list,'price','desc');
				}
			}
			if($good>0){
				if($good==1){ 	//asc
					orderby($list,'score','asc');
				}else{   		//desc
					orderby($list,'score','desc');
				}
			}
			return $list;
		}
		
		$map = array();
		$map['mo.seckill'] = 1;
		if(!empty($keyword)){
			$map['mo.title']=array('like','%'.$keyword.'%');
			//$where['_logic']='or';
			//$map['_complex']=$where;
		}
		
		$order = array();
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
			if($type==1){
				$order[]='score asc';
				$model->field('mo.*,AVG(comment.score) score')->join('left join sj_service_order sorder on mo.id=sorder.projectid')->join('left join sj_service_comment comment on sorder.id=comment.orderid');
				$order[]='mo.seckill_orderby asc';
				$order = implode(',',$order);
				$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
				$count = $model->alias('mo')->join('left join sj_service_order sorder on mo.id=sorder.projectid')->join('left join sj_service_comment comment on sorder.id=comment.orderid')->group('mo.id')->where($map)->count();
			}else{
				if($type==2){
					$map['mo.type']=0;
				}elseif($type==3){
					$map['mo.type']=2;
				}
				$order[]='score asc';
				$model->field('mo.*,AVG(comment.score) score')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid');
				$map['mo.status']=1;
				$map['mo.shelf']=1;
				$map['a.status']=1;
				$order[]='mo.seckill_orderby asc';
				$order = implode(',',$order);
				$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
				$count = $model->alias('mo')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid')->group('mo.id')->where($map)->count();
			}
		}elseif($good==2){
			//按好评降序
			if($type==1){
				$order[]='score desc';
				$model->field('mo.*,AVG(comment.score) score')->join('left join sj_service_order sorder on mo.id=sorder.projectid')->join('left join sj_service_comment comment on sorder.id=comment.orderid');
				$order[]='mo.seckill_orderby asc';
				$order = implode(',',$order);
				$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
				$count = $model->alias('mo')->join('left join sj_service_order sorder on mo.id=sorder.projectid')->join('left join sj_service_comment comment on sorder.id=comment.orderid')->group('mo.id')->where($map)->count();
			}else{
				if($type==2){
					$map['mo.type']=0;
				}elseif($type==3){
					$map['mo.type']=2;
				}
				$order[]='score desc';
				$model->field('mo.*,AVG(comment.score) score')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid');
				$map['mo.status']=1;
				$map['mo.shelf']=1;
				$map['mo.type']=0;
				$map['a.status']=1;
				$order[]='mo.seckill_orderby asc';
				$order = implode(',',$order);
				$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
				$count = $model->alias('mo')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid')->group('mo.id')->where($map)->count();
			}
		}else{
			if($type==1){
				$order[]='mo.seckill_orderby asc';
				$order = implode(',',$order);
				$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
				$count = $model->alias('mo')->where($map)->count();
			}else{
				if($type==2){
					$map['mo.type']=0;
				}elseif($type==3){
					$map['mo.type']=2;
				}
				$model->field('mo.*')->join('LEFT JOIN sj_product_attribute pa on mo.id=pa.productid');
				$map['mo.status']=1;
				$map['mo.shelf']=1;
				$map['mo.type']=0;
				$map['pa.status']=1;
				$order[]='mo.seckill_orderby asc';
				$order = implode(',',$order);
				$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
				$count = $model->alias('mo')->join('LEFT JOIN sj_product_attribute pa on mo.id=pa.productid')->group('mo.id')->where($map)->count();
			}
		}
		
		foreach($list as $k=>$v){
			$v['label']=json_decode($v['label'],true);
			if($type==1){
				$v['label']['attr']=$v['label']['attr1'];
			}
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$v['images']=$this->DoUrlListHandle($v['images']);
			$v['price']=($v['price']);;
			$v['market_price']=floatval($v['market_price']);
			$list[$k]=$v;
		}
		
		$totalpage = ceil($count/$row);
		$this->SetPaginationHeader($totalpage,$count,$page,$row);
		return $list;
	}
	//小程序商品列表的分类
	public function category(){
		$type = I('get.type');
		if($type==1){//服务
			$map = array('status'=>1);
			$list=D('service_category')->where($map)->order('ordernum asc')->select();
			array_unshift($list,array('id'=>'0','title'=>'全部'));
		}elseif($type==2){//商品
			$map = array('status'=>1);
			$list=D('category')->where($map)->select();
			//$list[]=array('id'=>'0','name'=>'全部');
			array_unshift($list,array('id'=>'0','name'=>'全部'));
		}else{
			$map = array('status'=>1);
			$service_list=D('service_category')->where($map)->select();
			foreach($service_list as $k=>$v){
				$v['type']=1;
				$service_list[$k]=$v;
			}
			$map = array('status'=>1);
			$product_list=D('category')->where($map)->select();
			foreach($product_list as $k=>$v){
				$v['type']=2;
				$product_list[$k]=$v;
			}
			$list=array_merge($service_list,$product_list);
		}
		return $list;
	}
	//服务人员动态
	public function moment(){
		$userid=I('get.userid');
		$page=I('get.page');
		$row=I('get.row');
		$begin=($page-1)*$row;
		
		$map = array('user_id'=>$userid);
		$count=D('moment')->where($map)->count();
		$list=D('moment')->where($map)->limit($begin,$row)->select();
		$totalpage=ceil($count/$row);
		$this->SetPaginationHeader($totalpage,$count,$page,$row);
		
		return $list;
	}
	
	//生活优品 - 首页
	public function quality_live(){
		//轮播图
		$bannermodel = D("banner");
		$map = array("status"=>1, "type"=>21);
		$c['image'] = array('neq','');
		$banner = $bannermodel->where($map)->where($c)->order("ordernum asc")->select();
		foreach ($banner as $k=>$v) {
		    $v["image"] = $this->DoUrlHandle($v["image"]);

		    if($v["param"]){
		        $v["param"] = json_decode($v["param"], true);
		    } else{
		        $v["param"] = array("param_type"=>"-1", "param_id"=>"");
		    }
		
			$banner[$k] = $v;
		}
		//商品栏目 1级分类

		$map = array("status"=>1);
		$column=D('column')->where($map)->order('ordernum asc')->select();
		foreach($column as $k=>$v){
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
			$column[$k] = $v;
		}

		$w['notice'] = array('neq','');
		$notice_data = $bannermodel->where($map)->where($w)->order("ordernum asc")->select();
		foreach ($notice_data as $k=>$v) {
		    $v["image"] = $this->DoUrlHandle($v["image"]);

		    if($v["param"]){
		        $v["param"] = json_decode($v["param"], true);
		    } else{
		        $v["param"] = array("param_type"=>"-1", "param_id"=>"");
		    }
		
			$notice_data[$k] = $v;
		}

		// dump($column);
		
		return array('banner'=>$banner,'column'=>$column,'notices'=>$notice_data);
	}
	//生活优品 - 分类、抢购
	public function quality_live_give_type(){
		// $typeid = I('get.typeid',0);//category表的ID
		$categoryid = I('get.categoryid',0);//column传分类ID'parentid='.$categoryid
		
		$select_category = D('category')->where(array('status'=>1,'parentid'=>$categoryid))->select();

		$first_cate_data = D('category')->where(array('status'=>1,'parentid'=>$categoryid,'depth'=>1))->field('thumb,name,remark,id,depth')->select();
		$map = array();

		if($categoryid!=0){
			$map['p.categoryid'] = $categoryid;
		}
		foreach ($first_cate_data as $key => $value) {

			$first_cate_data[$key]['second_data'] = D('category')->where(array('status'=>1,'category_pid'=>$value['id']))->field('thumb,name,remark,depth,id')->select();
			foreach ($first_cate_data[$key]['second_data'] as $k => $v) {
				$v['thumb']=$this->DoUrlHandle($v['thumb']);
				$first_cate_data[$key]['second_data'][$k]=$v; 
			}
			//查询当前分类下的所有子分类
			$where = array('category_pid'=>$value['id'],'status'=>1);
			$child_cate=D('category')->field('id')->where($where)->select();
			$child_array=array();
			foreach($child_cate as $k=>$v){
				$child_array[]=$v['id'];
			}
			$map['p.everyday_seckill']=1;//'typeid='.$value['id'].' AND seckill=1';
			if($child_array){
				$map['_string']='(p.typeid = '.$value['id'].') OR (p.typeid in ('.implode(',',$child_array).'))';
			}else{
				$map['_string']='(p.typeid = '.$value['id'].')';
			}
			
			$first_cate_data[$key]['thumb'] = $this->DoUrlHandle($v['thumb']);
			
			$first_cate_data[$key]['select_seckill'] = D('product')->alias('p')->field('p.id,p.thumb,p.title,p.subtitle,p.price,p.market_price')->join('left join sj_product_attribute pa on p.id=pa.productid')->where($map)->limit(3)->group('p.id')->order('p.everyday_seckill_orderby asc')->select();
			foreach ($first_cate_data[$key]['select_seckill'] as $kt => $vt) {
				$vt['thumb']=$this->DoUrlHandle($vt['thumb']);
				$first_cate_data[$key]['select_seckill'][$kt]=$vt;
			}
		}
		
		return $first_cate_data;
	}
	//生活优品 - 商品列表
	public function quality_live_list(){
		$page = I('get.page');
		$row = I('get.row');
		$typeid = I('get.typeid',0);//category表的ID
		$all_title = I('get.alltitle',0);//查询标题|副标题
		$categoryid = I('get.categoryid',0);//column传分类ID
		$begin = ($page-1)*$row;
		$recommend = I('get.recommend',0);//为你推荐
		$map = array();
		if($typeid){
			// $map['p.typeid'] = $typeid;
			$category_pid = D('category')->where(array('id'=>$typeid))->find();
			if($category_pid['depth']=='1'){
				$typeids = D('category')->where('category_pid='.$category_pid['id'].' AND depth=2 AND status=1')->field('id')->select();
				$typeids = array_column($typeids,'id');
				$typeid = explode(",", $typeid);
				$all_typeid = array_merge($typeids,$typeid);
				$map['p.typeid'] = ['in',$all_typeid];
			}else{
				$map['p.typeid'] = $typeid;
			}
		}
		$order = '';
		if($recommend){
			$map['p.recommend_for_you']=1;
			$order='p.recommend_for_you_ordernum asc';
			
		}
		if($categoryid!=0){
			$map['p.categoryid'] = $categoryid;
		}

		if($all_title!==0){
			$where['p.title'] = array("like","%".$all_title."%"); 
			$where['p.subtitle'] = array("like","%".$all_title."%"); 
			$where['_logic']='or';
			$map['_complex'] = $where;
		}
		
		$map['p.shelf']=1;
		$map['p.status']=1;
		$count = D('product')->alias('p')->where($map)->count();
		$totalpage = ceil($count/$row);
		$product = D('product')->alias('p')->field('p.id,p.thumb,p.title,p.subtitle,p.price,p.guess_like')->join('left join sj_product_attribute pa on p.id=pa.productid')->where($map)->limit($begin,$row)->order($order)->group('p.id')->select();
		// $product
		foreach($product as $k=>$v){
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$product[$k]=$v;
		}
		$this->SetPaginationHeader($totalpage,$count,$page,$row);
		return $product;
	}
	public function quality_live_guess_like(){
		$page = I('get.page');
		$row = I('get.row');
		// $typeid = I('get.typeid',0);//category表的ID
		$all_title = I('get.alltitle',0);//查询标题|副标题
		// $categoryid = I('get.categoryid',0);//column传分类ID
		$begin = ($page-1)*$row;
		
		$map = array();
		if($all_title!==0){
			$where['p.title'] = array("like","%".$all_title."%"); 
			$where['p.subtitle'] = array("like","%".$all_title."%"); 
			$where['_logic']='or';
			$map['_complex'] = $where;
		}
		$map['p.guess_like'] = array('eq',1);
		$map['p.shelf']=1;
		$map['p.status']=1;
		$count = D('product')->alias('p')->where($map)->count();
		$totalpage = ceil($count/$row);
		$product = D('product')->alias('p')->field('p.id,p.thumb,p.title,p.subtitle,p.price,p.guess_like')->join('left join sj_product_attribute pa on p.id=pa.productid')->where($map)->limit($begin,$row)->group('p.id')->select();
		// $product
		foreach($product as $k=>$v){
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$product[$k]=$v;
		}
		$this->SetPaginationHeader($totalpage,$count,$page,$row);
		return $product;
	}
}