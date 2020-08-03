<?php
namespace CApi\Controller;
use Think\Controller;
class HomeController extends BaseController {

	//首页
	public function index(){
		//顶部广告图
		$bannermodel = D("banner");
		$map = array("status"=>1, "type"=>1);
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

		//照护课堂
		$infomodel = D("information");
		$order = "top desc, ordernum asc, browser_count desc, collection_count desc, good_count desc, share_count desc";
		//热门资讯
		$map = array("status"=>1, "type"=>1);
		$info1 = $infomodel->where($map)->order($order)->find();
		if($info1){
			$info1["thumb"] = $this->DoUrlHandle($info1["thumb"]);
		}
		//课堂资讯
		$map = array("status"=>1, "type"=>2);
		$info2 = $infomodel->where($map)->order($order)->find();
		if($info2){
			$info2["thumb"] = $this->DoUrlHandle($info2["thumb"]);
		}
		$information = array(
			"info1"=>$info1, "info2"=>$info2
		);
		
		//推荐服务
		$service_project=D('service_project')->field('id,thumb,title,subtitle,price,market_price')->where('recommend=1')->limit(0,6)->select();
		foreach($service_project as $k=>$v){
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$service_project[$k]=$v;
		}
		
		//星选家护师
		$usermodel = D("user_role");
		$order = "up.recommend desc, up.top desc, up.comment_percent desc";
		$map = array("ur.role"=>3, "u.status"=>200, "up.status"=>1);
        //剔除爽约
		$plane_time = date("Y-m-d H:i:s", strtotime("-3 month", time()));
		$map["up.plane_time"] = array(
			array("exp", "is null"),
			array("lt", $plane_time),
			"or"
		);
		$user = $usermodel->alias("ur")->join("left join sj_user as u on ur.userid=u.id")->join("left join sj_user_profile as up on ur.userid=up.userid")
			->field("u.id,ur.role,u.avatar,up.realname,up.gender,up.birth,up.mobile,up.major_level,up.service_level,up.work_year,up.education,up.major,up.language,up.comment_percent")->where($map)->order($order)->limit(6)->select();
		foreach ($user as $k=>$v) {
			$v["avatar"] = $this->DoUrlHandle($v["avatar"]);
			
			//性别
			switch($v['gender']){
				case 0:
					$v['gender']='保密';
					break;
				case 1:
					$v['gender']='男';
					break;
				case 2:
					$v['gender']='女';
					break;
			}
			
			//年龄
			$v["age"] = getAgeMonth($v["birth"]);

			$user[$k] = $v;
		}

		//星选机构
		$orgmodel = D("org");
		$map = array("status"=>1);
		$order = "top desc, ordernum asc";
		$org = $orgmodel->where($map)->order($order)->limit(4)->select();
		foreach ($org as $k=>$v) {
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);

			$org[$k] = $v;
		}

		//客服电话
		$aboutmodel = D("about");
		$map = array("status"=>1, "id"=>4);
		$about = $aboutmodel->where($map)->find();
		if($about){
			$service = array("title"=>$about["title"], "tel"=>$about["content"]);
		}

		$data = array(
			"banner"=>$banner, "information"=>$information,
			"user"=>$user, "org"=>$org, "service"=>$service,"service_project"=>$service_project
		);

		return $data;
	}

    public function search(){
        $keyword = I("get.keyword");
		
        $begin = ($page-1)*$row;
        $list = array();
		//综合商品
		$model = D('product');
		$order = "p.top desc, p.recommend desc, p.ordernum asc, p.sales desc";
		$map = array("p.status"=>1,"p.shelf"=>1);
		if($keyword){
			$where=array();
		    $where["p.title"] = array("like","%".$keyword."%");
		    $where["p.subtitle"] = array("like","%".$keyword."%");
		    $where["_logic"] = "or";
		    $map["_complex"] = $where;
		}
		$map['pa.status']=1;
		$product = $model->alias('p')->field('p.*')->join('LEFT JOIN sj_product_attribute pa on p.id=pa.productid')->group('p.id')->where($map)->order($order)->limit(0, 10)->select();
		
		$commentmodel = D("product_comment");
		foreach($product as $k=>$v){
		    $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
		
			if(empty($v["label"])){
				$v["label"] = array("attr"=>"", "color"=>"");
			} else{
				$v["label"] = json_decode($v["label"], true);
			}
		    $v["market_price"] = getNumberFormat($v["market_price"]);
		    $v["price"] = ($v["price"]);
		    
		    $map = array("status"=>1, "productid"=>$v["id"]);
		    //评论数量
		    $commentcount = $commentmodel->where($map)->count();
		    $v["comment_count"] = $commentcount;
		
		    //好评度
		    $map["socre"] = array("egt", 80);
		    $goodcomment = $commentmodel->where($map)->count();
		    if($commentcount > 0){
		        $v["comment_percent"] = ceil($goodcomment/$commentcount*100);
		    } else{
		        $v["comment_percent"] = 100;
		    }
			$v['search_type']=3;
		    $product[$k] = $v;
		}
		$list['unite']['product'] = $product;
		
		//综合项目
		$map = array("status"=>1);
		if($keyword){
			$where=array();
		    $where["title"] = array("like", "%".$keyword."%");
		    $where["subtitle"] = array("like", "%".$keyword."%");
		    $where["_logic"] = "or";
		    $map["_complex"] = $where;
		}
		$model = D('service_project');
		$order = "top desc, ordernum asc, sales desc, browser_count desc";
		
		$service = $model->group('id')->where($map)->order($order)->limit(0,10)->select();
		foreach($service as $k=>$v){
		    $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
		
			if(empty($v["label"])){
				$v["label"] = array("attr1"=>"", "attr2"=>"");
			} else{
				$v["label"] = json_decode($v["label"], true);
			}
		    $v["market_price"] = getNumberFormat($v["market_price"]);
		    $v["price"] = ($v["price"]);
			
			//时长类型 - 月
			if($v["time_type"] == 3){
				$v["time"] = 1;
			}
			$v['search_type']=1;
		    $service[$k] = $v;
		}
		$list['unite']['service_project'] = $service;
		//综合家护师
		$usermodel = D("user_role");
		$order = "up.recommend desc, up.top desc, up.comment_percent desc";
		$map = array("ur.role"=>3, "u.status"=>200, "up.status"=>1);
		if($keyword){
		    $map["up.realname"] = array("like", "%".$keyword."%");
		}
		//剔除爽约
		$plane_time = date("Y-m-d H:i:s", strtotime("-3 month", time()));
		$map["up.plane_time"] = array(
			array("exp", "is null"),
			array("lt", $plane_time),
			"or"
		);
		$user = $usermodel->alias("ur")->join("left join sj_user as u on ur.userid=u.id")->join("left join sj_user_profile as up on ur.userid=up.userid")
			->field("u.id,ur.role,u.avatar,up.realname,up.gender,up.birth,up.mobile,up.major_level,up.service_level,up.work_year,up.education,up.major,up.language,up.comment_percent")->where($map)->order($order)->limit(0,10)->select();
		foreach ($user as $k=>$v) {
			$v["avatar"] = $this->DoUrlHandle($v["avatar"]);
			
			//性别
			switch($v['gender']){
				case 0:
					$v['gender']='保密';
					break;
				case 1:
					$v['gender']='男';
					break;
				case 2:
					$v['gender']='女';
					break;
			}
			
			//年龄
			$v["age"] = getAgeMonth($v["birth"]);
		
			$user[$k] = $v;
		}
		$list['unite']['user'] = $user;
		
        //商品
		$map = array("p.status"=>1);
		if($keyword){
			$where=array();
		    $where["p.title"] = array("like","%".$keyword."%");
		    $where["p.subtitle"] = array("like","%".$keyword."%");
		    $where["_logic"] = "or";
		    $map["_complex"] = $where;
		}
		$map['pa.status']=1;
        $model = D('product');
        $order = "p.top desc, p.recommend desc, p.ordernum asc, p.sales desc";
		
        $product = $model->alias('p')->field('p.*')->join('LEFT JOIN sj_product_attribute pa on p.id=pa.productid')->group('p.id')->where($map)->order($order)->select();
		
        $commentmodel = D("product_comment");
        foreach($product as $k=>$v){
            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);

			if(empty($v["label"])){
				$v["label"] = array("attr"=>"", "color"=>"");
			} else{
				$v["label"] = json_decode($v["label"], true);
			}
            $v["market_price"] = getNumberFormat($v["market_price"]);
            $v["price"] = ($v["price"]);
            
            $map = array("status"=>1, "productid"=>$v["id"]);
            //评论数量
            $commentcount = $commentmodel->where($map)->count();
            $v["comment_count"] = $commentcount;

            //好评度
            $map["socre"] = array("egt", 80);
            $goodcomment = $commentmodel->where($map)->count();
            if($commentcount > 0){
                $v["comment_percent"] = ceil($goodcomment/$commentcount*100);
            } else{
                $v["comment_percent"] = 100;
            }
			$v['search_type']=3;
            $product[$k] = $v;
        }
        $list['product'] = $product;

        //机构
        $model = D('org');
        $order = "top desc, ordernum asc";
		$map = array("status"=>1);
		if($keyword){
			$where=array();
		    $where["title"] = array("like", "%".$keyword."%");
		    $where["subtitle"] = array("like", "%".$keyword."%");
		    $where["_logic"] = "or";
		    $map["_complex"] = $where;
		}
        $org = $model->where($map)->order($order)->select();
        foreach($org as $k=>$v){
            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
			$v['search_type']=2;
            $org[$k] = $v;
        }
        $list['org'] = $org;

        //项目
        $map = array("status"=>1);
        if($keyword){
			$where=array();
            $where["title"] = array("like", "%".$keyword."%");
            $where["subtitle"] = array("like", "%".$keyword."%");
            $where["_logic"] = "or";
            $map["_complex"] = $where;
        }
        $model = D('service_project');
        $order = "top desc, ordernum asc, sales desc, browser_count desc";

        $service = $model->group('id')->where($map)->order($order)->select();
        foreach($service as $k=>$v){
            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);

			if(empty($v["label"])){
				$v["label"] = array("attr1"=>"", "attr2"=>"");
			} else{
				$v["label"] = json_decode($v["label"], true);
			}
            $v["market_price"] = getNumberFormat($v["market_price"]);
            $v["price"] = ($v["price"]);
			
			//时长类型 - 月
			if($v["time_type"] == 3){
				$v["time"] = 1;
			}
			$v['search_type']=1;
            $service[$k] = $v;
        }
        $list['service_project'] = $service;
		
		//家护师
		$usermodel = D("user_role");
		$order = "up.recommend desc, up.top desc, up.comment_percent desc";
		$map = array("ur.role"=>3, "u.status"=>200, "up.status"=>1);
		if($keyword){
		    $map["up.realname"] = array("like", "%".$keyword."%");
		}
		//剔除爽约
		$plane_time = date("Y-m-d H:i:s", strtotime("-3 month", time()));
		$map["up.plane_time"] = array(
			array("exp", "is null"),
			array("lt", $plane_time),
			"or"
		);
		$user = $usermodel->alias("ur")->join("left join sj_user as u on ur.userid=u.id")->join("left join sj_user_profile as up on ur.userid=up.userid")
			->field("u.id,ur.role,u.avatar,up.realname,up.gender,up.birth,up.mobile,up.major_level,up.service_level,up.work_year,up.education,up.major,up.language,up.comment_percent")->where($map)->order($order)->select();
		foreach ($user as $k=>$v) {
			$v["avatar"] = $this->DoUrlHandle($v["avatar"]);
			
			//性别
			switch($v['gender']){
				case 0:
					$v['gender']='保密';
					break;
				case 1:
					$v['gender']='男';
					break;
				case 2:
					$v['gender']='女';
					break;
			}
			
			//年龄
			$v["age"] = getAgeMonth($v["birth"]);
		
			$user[$k] = $v;
		}
		$list['user'] = $user;
		
		//记录热搜
		$keyword=trim($keyword);
		if($keyword){
			$map = array('keyword'=>$keyword);
			$hot_search=D('hot_search')->where($map)->find();
			if($hot_search){
				D('hot_search')->where($map)->setInc('weight',1);
			}else{
				$entity = array('keyword'=>$keyword,'weight'=>1);
				D('hot_search')->add($entity);
			}
			if($this->UserAuthCheckLogin()){
				$user = $this->AuthUserInfo;
				$map = array('keyword'=>$keyword,'user_id'=>$user['id']);
				$user_search=D('user_search')->where($map)->find();
				if($user_search){
					D('user_search')->where($map)->setInc('weight',1);
				}else{
					$entity = array('keyword'=>$keyword,'weight'=>1,'user_id'=>$user['id']);
					D('user_search')->add($entity);
				}
			}
		}
        return $list;
    }

    //商品
    public function productlist(){
        $keyword = I("get.keyword");
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;
        $map = array("p.status"=>1,"p.shelf"=>1, 'pa.status'=>1);
        if($keyword){
            $where["p.title"] = array("like", "%".$keyword."%");
            $where["p.subtitle"] = array("like", "%".$keyword."%");
            $where["_logic"] = "or";
            $map["_complex"] = $where;
        }
        $order = "p.top desc, p.recommend desc, p.ordernum asc, p.sales desc";
        $model = D('product');
        $count = $model->alias('p')->where($map)->join('LEFT JOIN sj_product_attribute pa on p.id=pa.productid')->count();
		
        $totalpage = ceil($count/$row);
        $list = $model->alias('p')->field('p.*')->where($map)->join('LEFT JOIN sj_product_attribute pa on p.id=pa.productid')->group('p.id')->order($order)->limit($begin, $row)->select();
        foreach($list as $k=>$v){
            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);

			if(empty($v["label"])){
				$v["label"] = array("attr"=>"", "color"=>"");
			} else{
				$v["label"] = json_decode($v["label"], true);
			}
            $v["market_price"] = getNumberFormat($v["market_price"]);
            $v["price"] = ($v["price"]);
			$v['search_type']=3;
			$map = array('productid'=>$v['id']);
			$v['comment_count']=D('product_comment')->where($map)->count();
			$score=D('product_comment')->field('AVG(score) score')->where($map)->find();
			$v['comment_percent']=ceil($score['score']?:0);
            $list[$k] = $v;
        }
        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        return $list;

    }

    //机构
    public function orglist(){
        $keyword = I("get.keyword");
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;
        $map = array("status"=>1);
        if($keyword){
            $where["title"] = array("like", "%".$keyword."%");
            $where["subtitle"] = array("like", "%".$keyword."%");
            $where["_logic"] = "or";
            $map["_complex"] = $where;
        }
        $order = "top desc, ordernum asc";
        $model = D('org');
        $count = $model->where($map)->count();
        $totalpage = ceil($count/$row);
        $list = $model->where($map)->order($order)->limit($begin, $row)->select();
        foreach($list as $k=>$v){
            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
			$v['search_type']=2;
            $list[$k] = $v;
        }
        $this->SetPaginationHeader($totalpage, $count, $page, $row);
		
		//记录热搜
		$keyword=trim($keyword);
		if($keyword){
			$map = array('keyword'=>$keyword);
			$hot_search=D('hot_search')->where($map)->find();
			if($hot_search){
				D('hot_search')->where($map)->setInc('weight',1);
			}else{
				$entity = array('keyword'=>$keyword,'weight'=>1);
				D('hot_search')->add($entity);
			}
			if($this->UserAuthCheckLogin()){
				$user = $this->AuthUserInfo;
				$map = array('keyword'=>$keyword,'user_id'=>$user['id']);
				$user_search=D('user_search')->where($map)->find();
				if($user_search){
					D('user_search')->where($map)->setInc('weight',1);
				}else{
					$entity = array('keyword'=>$keyword,'weight'=>1,'user_id'=>$user['id']);
					D('user_search')->add($entity);
				}
			}
		}
        return $list;

    }

    //服务项目
    public function serviceprojectlist(){
        $keyword = I("get.keyword");
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;
        $map = array("status"=>1);
        if($keyword){
            $where["title"] = array("like", "%".$keyword."%");
            $where["subtitle"] = array("like", "%".$keyword."%");
            $where["_logic"] = "or";
            $map["_complex"] = $where;
        }
        $model = D('service_project');
        $order = "top desc, ordernum asc, sales desc, browser_count desc";

        $count = $model->where($map)->count();

        $totalpage = ceil($count/$row);
        $list = $model->group('id')->where($map)->order($order)->limit($begin, $row)->select();

        foreach($list as $k=>$v){
            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);

			if(empty($v["label"])){
				$v["label"] = array("attr1"=>"", "attr2"=>"");
			} else{
				$v["label"] = json_decode($v["label"], true);
			}
            $v["market_price"] = getNumberFormat($v["market_price"]);
            $v["price"] = ($v["price"]);
			
			//时长类型 - 月
			if($v["time_type"] == 3){
				$v["time"] = 1;
			}
			$v['search_type']=1;
            $list[$k] = $v;
        }
        $this->SetPaginationHeader($totalpage, $count, $page, $row);
		//记录热搜
		$keyword=trim($keyword);
		if($keyword){
			$map = array('keyword'=>$keyword);
			$hot_search=D('hot_search')->where($map)->find();
			if($hot_search){
				D('hot_search')->where($map)->setInc('weight',1);
			}else{
				$entity = array('keyword'=>$keyword,'weight'=>1);
				D('hot_search')->add($entity);
			}
			if($this->UserAuthCheckLogin()){
				$user = $this->AuthUserInfo;
				$map = array('keyword'=>$keyword,'user_id'=>$user['id']);
				$user_search=D('user_search')->where($map)->find();
				if($user_search){
					D('user_search')->where($map)->setInc('weight',1);
				}else{
					$entity = array('keyword'=>$keyword,'weight'=>1,'user_id'=>$user['id']);
					D('user_search')->add($entity);
				}
			}
		}
        return $list;

    }
	//热搜
	public function hot_search(){
		$list=D('hot_search')->order('weight desc')->limit('0,10')->select();
		return $list;
	}
	//历史搜索
	public function history_search(){
		if($this->UserAuthCheckLogin()){
			$user = $this->AuthUserInfo;
			$map = array('user_id'=>$user['id']);
			$keyword = I('get.keyword');
			if($keyword){
				$map['keyword']=array('like',$keyword.'%');
			}
			
			$hot=D('hot_search')->order('weight desc')->limit('0,10')->select();
			$hotarray=array();
			foreach($hot as $k=>$v){
				$hotarray[]=$v['keyword'];
			}
			$map = array('user_id'=>$user['id']);
			if($keyword){
				$map['_complex']['keyword']=array('like','%'.$keyword.'%');
			}
			$map['keyword']=array('not in',$hotarray);
			
			$user_search=D('user_search')->where($map)->order('weight desc')->limit(0,10)->select();
			return $user_search;
		}else{
			return ;
		}
	}
	//清楚历史搜索记录
	public function clear_history_search(){
		if($this->UserAuthCheckLogin()){
			$user = $this->AuthUserInfo;
			$map = array('user_id'=>$user['id']);
			D('user_search')->where($map)->delete();
		}
		return ;
	}
}