<?php
namespace SApi\Controller;
use Think\Controller;
class ServiceController extends BaseController {

	//所有服务栏目
	public function category(){
		$model = D("service_category");

		$map = array("status"=>1);
		$list = $model->where($map)->order("ordernum asc")->select();

		foreach($list as $k=>$v){
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);

			$list[$k] = $v;
		}

		$list[] = array(
			"id"=>-1, "title"=>"送餐服务", "subtitle"=>"送餐服务", "ordernum"=>99, "role"=>2
		);

		return $list;
	}

	//家护师服务栏目
	public function fcategory(){
		$model = D("service_category");

		$map = array("status"=>1, "role"=>3);
		$list = $model->where($map)->order("ordernum asc")->select();

		foreach($list as $k=>$v){
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);

			$list[$k] = $v;
		}

		return $list;
	}

	//康复师服务项目（技师/体检）
	public function projecthealth(){
		$categorymodel = D("service_category");
		$map = array("status"=>1, "role"=>4);
		$category = $categorymodel->where($map)->order("ordernum asc")->select();
		foreach($category as $k=>$v){
			$categoryids[] = $v["id"];
		}
		if(empty($categoryids)){
			return [];
		}

		$model = D("service_project");

		$order = "top desc, ordernum asc, sales desc, browser_count desc";
		$map = array("status"=>1, "categoryid"=>array("in", $categoryids));
		$list = $model->where($map)->order($order)->select();

		foreach($list as $k=>$v){
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);

			if(empty($v["label"])){
				$v["label"] = array("attr1"=>"", "attr2"=>"");
			} else{
				$v["label"] = json_decode($v["label"], true);
			}
            $v["market_price"] = getNumberFormat($v["market_price"]);
            $v["price"] = ($v["price"]);

			$list[$k] = $v;
		}

		return $list;
	}

	//适老配餐
	public function product(){
		$model = D("product");

		$order = "top desc, recommend desc, ordernum asc, sales desc";
		$map = array("status"=>1, "categoryid"=>1);
		$list = $model->where($map)->order($order)->select();

		foreach($list as $k=>$v){
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
			
			$list[$k] = $v;
		}

		return $list;
	}

	//医生项目
	public function projectdoctor(){
		$categorymodel = D("service_category");
		$map = array("status"=>1, "role"=>5);
		$category = $categorymodel->where($map)->order("ordernum asc")->select();
		foreach($category as $k=>$v){
			$categoryids[] = $v["id"];
		}
		if(empty($categoryids)){
			return [];
		}

		$model = D("service_project");

		$order = "top desc, ordernum asc, sales desc, browser_count desc";
		$map = array("status"=>1, "categoryid"=>array("in", $categoryids));
		$list = $model->where($map)->order($order)->select();

		foreach($list as $k=>$v){
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);

			if(empty($v["label"])){
				$v["label"] = array("attr1"=>"", "attr2"=>"");
			} else{
				$v["label"] = json_decode($v["label"], true);
			}
            $v["market_price"] = getNumberFormat($v["market_price"]);
            $v["price"] = ($v["price"]);

			$list[$k] = $v;
		}

		return $list;
	}

	//护士项目
	public function projectnurse(){
		$categorymodel = D("service_category");
		$map = array("status"=>1, "role"=>6);
		$category = $categorymodel->where($map)->order("ordernum asc")->select();
		foreach($category as $k=>$v){
			$categoryids[] = $v["id"];
		}
		if(empty($categoryids)){
			return [];
		}

		$model = D("service_project");

		$order = "top desc, ordernum asc, sales desc, browser_count desc";
		$map = array("status"=>1, "categoryid"=>array("in", $categoryids));
		$list = $model->where($map)->order($order)->select();

		foreach($list as $k=>$v){
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);

			if(empty($v["label"])){
				$v["label"] = array("attr1"=>"", "attr2"=>"");
			} else{
				$v["label"] = json_decode($v["label"], true);
			}
            $v["market_price"] = getNumberFormat($v["market_price"]);
            $v["price"] = ($v["price"]);

			$list[$k] = $v;
		}

		return $list;
	}

    //服务项目
    public function servicelists(){
        $categoryid = I("get.categoryid", 0);
        if(empty($categoryid)){
            E("请选择要查看的服务项目栏目");
        }

        $model = D("service_project");

        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;

        $order = "top desc, ordernum asc, sales desc, browser_count desc";
        $map = array("status"=>1, "categoryid"=>$categoryid);
        $count = $model->where($map)->count();
        $totalpage = ceil($count/$row);
        $list = $model->where($map)->order($order)->limit($begin, $row)->select();

        $this->SetPaginationHeader($totalpage, $count, $page, $row);

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

            $list[$k] = $v;
        }

        return $list;
    }

    //服务详情
    public function servicedetail(){
        $id = I("get.id", 0);

        $model = D("service_project");

        $map = array("status"=>1, "id"=>$id);
        $detail = $model->where($map)->find();
        if(empty($detail)){
            E("服务项目不存在");
        }

        $detail["thumb"] = $this->DoUrlHandle($detail["thumb"]);
		$detail["images"] = $this->DoUrlListHandle($detail["images"]);
		$detail["content"] = $this->UEditorUrlReplace($detail["content"]);
		$detail["tips_content"] = $this->UEditorUrlReplace($detail["tips_content"]);
		$detail["market_price"] = getNumberFormat($detail["market_price"]);
		$detail["price"] = ($detail["price"]);

		//时长类型 - 月
		if($detail["time_type"] == 3){
			$detail["time"] = 1;
		}

        return $detail;
    }

	//餐厅
    public function restaurantlist(){
        $model = D("restaurant");
        $data = $model->where('status=1')->order("ordernum asc")->select();
        return $data;
    }

    //商品详情
    public function productdetail(){
        $id = I("get.id", 0);

        $model = D("product");

        $map = array("status"=>1, "id"=>$id);
        $detail = $model->where($map)->find();
        if(empty($detail)){
            E("商品不存在");
        }

        $detail["thumb"] = $this->DoUrlHandle($detail["thumb"]);
        $detail["images"] = $this->DoUrlListHandle($detail["images"]);
		$detail["market_price"] = getNumberFormat($detail["market_price"]);
		$detail["price"] = ($detail["price"]);

        $detail["content"] = preg_replace('/(<img.+?src=")(.*?)/','$1http://'.$_SERVER['SERVER_NAME'].'$2', $detail["content"]);

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
        $commentlist = $comment->order($order)->limit(0, 2)->select();

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

        return $detail;
	}
	
	public function service_detail(){
		$type=I('get.type');
		$map['id']=$type;
		$detail=D('service_detail')->where($map)->find();
		$detail["content"] = $this->UEditorUrlReplace($detail["content"]);
		return $detail;
	}
}