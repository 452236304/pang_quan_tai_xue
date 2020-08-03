<?php
namespace CApi\Controller;
use Think\Controller;
class ProductController extends BaseController {
	
	//商城首页
	public function index(){
		//广告图
		$bannermodel = D("banner");
		$map = array("status"=>1, "type"=>2);
		$banner = $bannermodel->where($map)->select();
		foreach ($banner as $k=>$v) {
			$v["image"] = $this->DoUrlHandle($v["image"]);
            
            if($v["param"]){
                $v["param"] = json_decode($v["param"], true);
            } else{
                $v["param"] = array("param_type"=>"-1", "param_id"=>"");
            }

			$banner[$k] = $v;
		}

		$productmodel = D("product");

		$order = "recommend desc, top desc, ordernum asc, sales desc";

		//爆款推荐
		$map = array("p.status"=>1,"p.shelf"=>1, "p.type"=>0,'a.status'=>1);
		$list1 = $productmodel->alias('p')->field('p.*')->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')->group('p.id')->where($map)->order($order)->limit(2)->select();

		//适老改造
		$map = array("status"=>1,"p.shelf"=>1, "type"=>2, "categoryid"=>5);
		$list2 = $productmodel->where($map)->order($order)->limit(1)->select();

		//适老家具
		$map = array("p.status"=>1,"p.shelf"=>1, "p.type"=>0, "p.categoryid"=>2,'a.status'=>1);
		$list3 = $productmodel->alias('p')->field('p.*')->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')->group('p.id')->where($map)->order($order)->limit(2)->select();

		//适老辅具
		$map = array("p.status"=>1,"p.shelf"=>1, "p.type"=>0, "p.categoryid"=>4,'a.status'=>1);
		$list4 = $productmodel->alias('p')->field('p.*')->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')->group('p.id')->where($map)->order($order)->limit(2)->select();

        for ($i=1;$i<=4;$i++) {
            foreach (${'list'.$i} as $key => $val) {
                ${'list'.$i}[$key]["thumb"] = $this->DoUrlHandle($val["thumb"]);
                ${'list'.$i}[$key]["market_price"] = getNumberFormat(${'list'.$i}[$key]["market_price"]);
                ${'list'.$i}[$key]["price"] = getNumberFormat(${'list'.$i}[$key]["price"]);
            }
        }

		$data = array("banner"=>$banner, "list1"=>$list1, "list2"=>$list2, "list3"=>$list3, "list4"=>$list4);

		return $data;
	}
	//栏目列表
	public function column(){
		//$map['is_lock']=array('neq'=>2);
		$list=D('column')->where('is_lock != 2 AND status=1')->field('id,name,type,remark,createdate')->select();
		//$list[]=array('id'=>1,"name"=>'适老配餐',"remark"=>'','createdate'=>date('Y-m-d H:i:s'));
		//$list[]=array('id'=>5,"name"=>'适老改造',"remark"=>'','createdate'=>date('Y-m-d H:i:s'));
		return $list;
	}
	//商城栏目
	public function category(){
		//商品栏目（categoryid：1=配餐,2=家具,3=卫浴,4=辅具,5=改造）
		$categoryid = I("get.categoryid", 0);
		
		
		$model = D("product");

		$map = array("status"=>1,"shelf"=>1, "categoryid"=>$categoryid);
		$order = "top desc, recommend desc, ordernum asc, sales desc";

		//适老配餐和适老改造
		if(in_array($categoryid, [1,5])){
			$list = $model->where($map)->order($order)->select();
			foreach($list as $k=>$v){
				$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
				
				$list[$k] = $v;
			}
			$data = array("list"=>$list);
		} else{
			
			//适老家具 - 私人定制
			if($categoryid == 2){
				$custom_map = array("status"=>1,"shelf"=>1, "categoryid"=>$categoryid, "type"=>1);
				$custom = $model->where($custom_map)->order($order)->select();
				foreach($custom as $k=>$v){
					$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
					
					$custom[$k] = $v;
				}
				$data["custom"] = $custom;
			}

			$categorymodel = D("category");
			$category_map = array("status"=>1, "parentid"=>$categoryid);
			$category = $categorymodel->where($category_map)->select();
			$map = array("p.status"=>1,"p.shelf"=>1,  "p.categoryid"=>$categoryid);
            $map["p.type"] = 0;
            foreach ($category as $k=>$v) {
                $map["p.typeid"] = $v["id"];
				$map['a.status']=1;
				$list = $model->alias('p')->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')->field('p.*')->group('p.id')->where($map)->order($order)->limit(4)->select();
				foreach($list as $k1=>$v1){
					$v1['thumb'] = $this->DoUrlHandle($v1["thumb"]);

                    if(empty($v["label"])){
                        $v1["label"] = array("attr"=>"", "color"=>"");
                    } else{
                        $v1["label"] = json_decode($v1["label"], true);
                    }
                    $v1["market_price"] = getNumberFormat($v1["market_price"]);
                    $v1["price"] = getNumberFormat($v1["price"]);

                    $list[$k1] = $v1;
				}
				$data["types"][] = array("typeid"=>$v["id"], "name"=>$v["name"], "list"=>$list);
			}
		}

		return $data;
	}
	//商城栏目
	public function category_detail(){
		//商品栏目（categoryid：1=配餐,2=家具,3=卫浴,4=辅具,5=改造）
		return $data;
	}
	//商品列表
	public function lists(){
		//商品栏目（categoryid：1=配餐,2=家具,3=卫浴,4=辅具,5=改造）
		$categoryid = I("get.categoryid");
		/*if(!in_array($categoryid, [1,2,3,4,5])){
			E("请选择要查看的商品栏目");
		}*/

		//排序类型：1=综合,2=销量高,3=销量低,4=价格高,5=价格低） 默认综合排序
		$ordertype = I("get.ordertype", 1);
		//分类
		$typeid = I("get.typeid", 0);
		//关键字
		$keyword = I("get.keyword");
        //始-金额范围
        $beginamount = I("get.beginamount", 0);
        //止-金额范围
        $endamount = I("get.endamount", 0);
        //产品分类
        $attribute_cpid = I("get.attribute_cpid", 0);
        //材质分类
        $attribute_czid = I("get.attribute_czid", 0);
		//材质分类
		$seckill = I("get.seckill", 0);

		$model = D("product");

		$map = array("p.status"=>1,"p.shelf"=>1,  "p.type"=>0,'a.status'=>1);
		if($seckill>0){
			$map["p.seckill"] = $seckill;
		}
        if (is_numeric($categoryid)) {
            $map["p.categoryid"] = $categoryid;
        }
		if(!in_array($categoryid, [1,5]) && $typeid){
			$map["p.typeid"] = $typeid;
		}
		if($keyword){
			$where["p.title"] = array("like", "%".$keyword."%");
			$where["p.subtitle"] = array("like", "%".$keyword."%");
			$where["_logic"] = "or";
			$map["_complex"] = $where;
		}
        if ($beginamount > 0 && $endamount > 0) {
            $map['p.price'] = array(array('egt',$beginamount),array('elt',$endamount),'AND');
        } else if ($beginamount > 0) {
            $map['p.price'] = array('egt', $beginamount);
        } else if ($endamount > 0) {
            $map['p.price'] = array('elt', $endamount);
        }
        if ($attribute_cpid > 0) {
            $map['p.attribute_cpid'] = $attribute_cpid;
        }

        if ($attribute_czid > 0) {
            $map['p.attribute_czid'] = $attribute_czid;
        }
		$page = I("get.page", 1);
        $row = I("get.row", 10);
		$begin = ($page-1)*$row;
		
		$order = "p.top desc, p.recommend desc, p.ordernum asc, p.sales desc";
        switch ($ordertype) {
            case 2:
                $order = 'p.sales desc';
                break;
            case 3:
                $order = 'p.sales asc';
                break;
            case 4:
                $order = 'p.price desc';
                break;
            case 5:
                $order = 'p.price asc';
                break;
            default:
                break;
        }
        $count = count($model->alias('p')->field('p.*,MAX(a.price) max_price,MIN(a.price) min_price')->where($map)->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')->group('p.id')->order($order)->select());
        $totalpage = ceil($count/$row);
		$list = $model->alias('p')->field('p.*,MAX(a.price) max_price,MIN(a.price) min_price')->where($map)->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')->group('p.id')->order($order)->limit($begin, $row)->select();

		$this->SetPaginationHeader($totalpage, $count, $page, $row);

        $commentmodel = D("product_comment");

		foreach($list as $k=>$v){
            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);

			if(empty($v["label"])){
				$v["label"] = array("attr"=>"", "color"=>"");
			} else{
				$v["label"] = json_decode($v["label"], true);
			}
            $v["market_price"] = getNumberFormat($v["market_price"]);
            $v["price"] = ($v["price"]);
			//$v["max_price"] = getNumberFormat($v["max_price"]);
			//$v["min_price"] = getNumberFormat($v["min_price"]);
            
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

			$list[$k] = $v;
		}
		
		return $list;
	}

	//商品详情
	public function detail(){
		$id = I("get.id", 0);

		$model = D("product");

		$map = array("status"=>1,"shelf"=>1,  "id"=>$id);
		$detail = $model->where($map)->find();
		if(empty($detail)){
			E("商品不存在");
		}

        $detail["thumb"] = $this->DoUrlHandle($detail["thumb"]);
        $detail["images"] = $this->DoUrlListHandle($detail["images"]);
        $detail["content"] = $this->UEditorUrlReplace($detail["content"]);
        $detail["spec_content"] = $this->UEditorUrlReplace($detail["spec_content"]);
        $detail["market_price"] = getNumberFormat($detail["market_price"]);
        
        $detail["freight"] = 0;
        if($detail["freightid"] > 0){
            $freight = D("product_order_freight")->find($detail["freightid"]);
            if($freight){
                $detail["freight"] = $freight["money"];
            }
        }

        //是否已收藏
        $detail["is_collection"] = '0';

        $detail["shopping_cart_count"] = '0';

        if($this->UserAuthCheckLogin()){
            $user = $this->AuthUserInfo;
            $user_record_model = D("user_record");

            $map = array("userid"=>$user["id"], "source"=>2, "type"=>1, "objectid"=>$id);
            $record = $user_record_model->where($map)->find();
            if ($record) {
                $detail["is_collection"] = '1';
            }

            $shopping_cart_model = D("shopping_cart");

            $map = array("userid"=>$user["id"]);
            $shopping_cart_count = $shopping_cart_model->alias("sc")->join("left join sj_product as p on sc.productid=p.id")->where($map)->count();

            $detail["shopping_cart_count"] = $shopping_cart_count;
        }


        $comment = D("product_comment");
        $map = array("status"=>1, "productid"=>$id);
        //好评数
        $map['score'] = array('egt',80);
        $goodcomment = $comment->where($map)->count();
        //中评数
        $map['score'] = array(array('egt',60),array('lt',80),'AND');
        $mediancomment = $comment->where($map)->count();
        //差评数
        $map['score'] = array('lt',60);
        $badcomment = $comment->where($map)->count();

        $order = "createdate desc";
        $map = array("status"=>1, 'productid'=>$id);
        $commentlist = $comment->where($map)->order($order)->limit(0, 2)->select();

        foreach($commentlist as $k=>$v){
            $v["avatar"] = $this->DoUrlHandle($v["avatar"]);
            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $v["images"] = $this->DoUrlListHandle($v["images"]);

            $v['star'] = $this->calcstar($v['score']);

            $commentlist[$k] = $v;
        }

        $detail['goodcomment'] = $goodcomment;
        $detail['mediancomment'] = $mediancomment;
        $detail['badcomment'] = $badcomment;
        $detail['commentlist'] = $commentlist;

        $detail['comment_count'] = ($goodcomment+$mediancomment+$badcomment);

        //商品套餐列表
        $attributemodel = D("product_attribute");
        $map = array("status"=>1, "productid"=>$id);
        $attribute = $attributemodel->where($map)->order("price asc")->select();
        foreach ($attribute as $k=>$v) {
			$v['price']=floatval($v['price']);
            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $attribute[$k] = $v;
        }
        $detail["attribute"] = $attribute;

		//店铺
		if($detail['company_id']==0){
			$company_id=1;
		}else{
			$company_id=$detail['company_id'];
		}
		$map = array('id'=>$company_id);
		$company=D('business')->where($map)->find();
		$detail['company']=$company['title'];
		$detail['company_thumb']=$this->DoUrlHandle($company['thumb']);
		
        return $detail;
	}

	//商品评论列表
	public function comment(){
		$productid = I("get.productid", 0);
		$score = I("get.score", 0);
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;

        $model = D("product_comment");
        
        $order = "createdate desc";
        $map = array("status"=>1, "productid"=>$productid);
        switch($score){
            case 1:
                $map['score'] = array('egt',80);
                break;
            case 2:
                $map['score'] = array(array('egt',60),array('lt',80),'AND');
                break;
            case 3:
                $map['score'] = array('lt',60);
                break;
        }
        $count = $model->where($map)->count();
        $totalpage = ceil($count/$row);
        $list = $model->where($map)->order($order)->limit($begin, $row)->select();
		
		$this->SetPaginationHeader($totalpage, $count, $page, $row);

		foreach($list as $k=>$v){
			$v["avatar"] = $this->DoUrlHandle($v["avatar"]);
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $v["images"] = $this->DoUrlListHandle($v["images"]);
            
            $v["createdate"] = time_tranx($v["createdate"]);

            $v['star'] = $this->calcstar($v['score']);

			$list[$k] = $v;
		}

		return $list;
	}

	//商品筛选列表
    public function attribute(){
        $model = D("attribute");
        $map = array('status'=>1);
        $data = $model->where($map)->order("ordernum asc")->select();
        $list = array();
        foreach ($data as $key => $val) {
            $list['type_'.$val['type']][] = $val;
        }

        return $list;
    }

    /**
     * Notes: V3 服务-适老商城
     * User: dede
     * Date: 2020/3/2
     * Time: 3:16 下午
     */
    public function shop(){
        //广告图
        $nav_id = 12;
        $banner = D('UnivNavBanner')->nav($nav_id);
        // 限时秒杀
        $seckill = D('Product', 'Service')->seckill();

        $discounts = D('Product', 'Service')->discounts();

        // 分类栏目
        $list=D('column')->where('is_lock != 2 AND status=1')->field('id,name,type,remark,createdate')->select();
        $product = D('Product', 'Service')->categoryProduct($list[0]['id']);
        $data = [
            'banner' => $banner,
            'seckill'=> $seckill['rows'],
            'discounts' => $discounts['rows'],
            'category' => $list,
            'product' => $product['rows'],
        ];
        return $data;
    }

    /**
     * Notes: 优惠选购
     * User: dede
     * Date: 2020/3/17
     * Time: 4:33 下午
     */
    public function discounts(){
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $discounts = D('Product', 'Service')->discounts($offset, $row);
        $count = $discounts['total'];
        $totalpage = $count / $row;

        $this->SetPaginationHeader($totalpage, $count, $page, $row);
        $data = [
            'product' => $discounts['rows']
        ];
        return $data;
    }
	//商品列表
	public function demo(){
		$array=[
		    "75",
		    "146",
		    "101",
		    "87",
		    "102",
		    "103",
		    "80",
		    "94",
		    "95",
		    "81",
		    "97",
		    "98",
		    "100",
		    "86",
		    "124",
		    "136",
		    "113",
		    "148",
		    "125",
		    "137",
		    "76",
		    "114",
		    "149",
		    "89",
		    "127",
		    "138",
		    "77",
		    "115",
		    "150",
		    "91",
		    "128",
		    "104",
		    "140",
		    "79",
		    "116",
		    "90",
		    "93",
		    "129",
		    "105",
		    "141",
		    "117",
		    "130",
		    "106",
		    "142",
		    "119",
		    "96",
		    "131",
		    "107",
		    "143",
		    "82",
		    "120",
		    "132",
		    "109",
		    "144",
		    "83",
		    "121",
		    "133",
		    "110",
		    "145",
		    "84",
		    "122",
		    "99",
		    "134",
		    "111",
		    "85",
		    "123",
		    "135",
		    "112",
		    "147"
		];
		$map = array('id'=>array('not in',$array),'category'=>array('not in',[1,5]),'status'=>1,'type'=>0);
		$list=D('product')->field('title')->where($map)->select();
		return $list;
		exit;
		//商品栏目（categoryid：1=配餐,2=家具,3=卫浴,4=辅具,5=改造）
		$categoryid = I("get.categoryid");
		/*if(!in_array($categoryid, [1,2,3,4,5])){
			E("请选择要查看的商品栏目");
		}*/
	
		//排序类型：1=综合,2=销量高,3=销量低,4=价格高,5=价格低） 默认综合排序
		$ordertype = I("get.ordertype", 1);
		//分类
		$typeid = I("get.typeid", 0);
		//关键字
		$keyword = I("get.keyword");
	    //始-金额范围
	    $beginamount = I("get.beginamount", 0);
	    //止-金额范围
	    $endamount = I("get.endamount", 0);
	    //产品分类
	    $attribute_cpid = I("get.attribute_cpid", 0);
	    //材质分类
	    $attribute_czid = I("get.attribute_czid", 0);
		//材质分类
		$seckill = I("get.seckill", 0);
	
		$model = D("product");
	
		$map = array("p.status"=>1, "p.type"=>0,'a.status'=>1,'p.seckill'=>$seckill);
	    if (is_numeric($categoryid)) {
	        $map["p.categoryid"] = $categoryid;
	    }
		if(!in_array($categoryid, [1,5]) && $typeid){
			$map["p.typeid"] = $typeid;
		}
		if($keyword){
			$where["p.title"] = array("like", "%".$keyword."%");
			$where["p.subtitle"] = array("like", "%".$keyword."%");
			$where["_logic"] = "or";
			$map["_complex"] = $where;
		}
	    if ($beginamount > 0 && $endamount > 0) {
	        $map['p.price'] = array(array('egt',$beginamount),array('elt',$endamount),'AND');
	    } else if ($beginamount > 0) {
	        $map['p.price'] = array('egt', $beginamount);
	    } else if ($endamount > 0) {
	        $map['p.price'] = array('elt', $endamount);
	    }
	    if ($attribute_cpid > 0) {
	        $map['p.attribute_cpid'] = $attribute_cpid;
	    }
	
	    if ($attribute_czid > 0) {
	        $map['p.attribute_czid'] = $attribute_czid;
	    }
		$page = I("get.page", 1);
	    $row = I("get.row", 10);
		$begin = ($page-1)*$row;
		
		$order = "p.top desc, p.recommend desc, p.ordernum asc, p.sales desc";
	    switch ($ordertype) {
	        case 2:
	            $order = 'p.sales desc';
	            break;
	        case 3:
	            $order = 'p.sales asc';
	            break;
	        case 4:
	            $order = 'p.price desc';
	            break;
	        case 5:
	            $order = 'p.price asc';
	            break;
	        default:
	            break;
	    }
	    $count = $model->alias('p')->where($map)->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')->count();
	    $totalpage = ceil($count/$row);
		$list = $model->alias('p')->field('p.id')->where($map)->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')->group('p.id')->order($order)->select();
	
		//$this->SetPaginationHeader($totalpage, $count, $page, $row);
	
	    $commentmodel = D("product_comment");
	
		foreach($list as $k=>$v){
			$v['price']=floatval($v['price']);
			$list[$k] = $v['id'];
		}
		
		return $list;
	}
	
	//生活商品列表
	public function orderby_list(){
		//类型 0全部 1服务 2商品
		$type = 2;
		//关键字 搜索
		$keyword = I('get.keyword');
		//排序 销量 0不排序 1升序 2降序
		$sale = I('get.sale',0);
		//排序 价格 0不排序 1升序 2降序
		$price = I('get.price',0);
		//排序 好评 0不排序 1升序 2降序
		$good = I('get.good',0);
		
		//0全部 1为你推荐 2每日抢购 
		$type = I('get.type',0);
		
		$page = I('get.page',1);
		$row = I('get.row',10);
		
		$model = D('product');
		$map = array();
		$order = array();
		
		if(!empty($keyword)){
			$map['mo.title']=array('like','%'.$keyword.'%');
		}
		
		
		if($sale==1){
			$order[]='mo.sales asc';
		}elseif($sale==2){
			$order[]='mo.sales desc';
		}
		
		if($price==1){
			$order[]='a.price asc';
		}elseif($price==2){
			$order[]='a.price desc';
		}
		$model->alias('mo');
		$begin = ($page-1)*$row;
		if($good==1){
			//按好评升序
			$order[]='score asc';
			$model->field('mo.*,AVG(comment.score) score')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid');
			$map['mo.status']=1;
			$map['mo.shelf']=1;
			$map['mo.type']=0;
			$map['a.status']=1;
			switch($type){
				case 1:
					$map['mo.recommend_for_you'] = 1;
					$order[]='mo.recommend_orderby asc';
					break;
				case 2:
					$map['mo.seckill'] = 1;
					$order[]='mo.seckill_orderby asc';
					break;
			}
			$order = implode(',',$order);
			$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
			$count = $model->alias('mo')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid')->group('mo.id')->where($map)->count();
		}elseif($good==2){
			//按好评降序
			
			$order[]='score desc';
			$model->field('mo.*,AVG(comment.score) score')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid');
			$map['mo.status']=1;
			$map['mo.shelf']=1;
			$map['mo.type']=0;
			$map['a.status']=1;
			switch($type){
				case 1:
					$map['mo.recommend_for_you'] = 1;
					$order[]='mo.recommend_orderby asc';
					break;
				case 2:
					$map['mo.seckill'] = 1;
					$order[]='mo.seckill_orderby asc';
					break;
			}
			$order = implode(',',$order);
			$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
			$count = $model->alias('mo')->join('left join sj_product_comment comment on mo.id=comment.productid')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid')->group('mo.id')->where($map)->count();
		}else{
			$model->field('mo.*')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid');
			$map['mo.status']=1;
			$map['mo.shelf']=1;
			$map['mo.type']=0;
			$map['a.status']=1;
			switch($type){
				case 1:
					$map['mo.recommend_for_you'] = 1;
					$order[]='mo.recommend_orderby asc';
					break;
				case 2:
					$map['mo.seckill'] = 1;
					$order[]='mo.seckill_orderby asc';
					break;
			}
			$order = implode(',',$order);
			$list = $model->where($map)->order($order)->group('mo.id')->limit($begin,$row)->select();
			$count = $model->alias('mo')->join('LEFT JOIN sj_product_attribute a on mo.id=a.productid')->group('mo.id')->where($map)->count();
		
		}
		
		foreach($list as $k=>$v){
			$v['label']=json_decode($v['label'],true);
				
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
}