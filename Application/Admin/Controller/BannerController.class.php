<?php
namespace Admin\Controller;
use Think\Controller;
class BannerController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("banner");
        $notice = I('get.notice', 0);
        $type = I('get.type', 0);
        $map = array('type'=>$type);
        // dump($notice);
        $show_notice = [];
        if($notice!=0){
            $show_notice['notice'] = array('neq','');
        }else{
            $show_notice['image'] = array('neq', '');
        }

        $data = $model->where($map)->where($show_notice)->order("ordernum asc")->select();
		$params=$this->GetParam();
		foreach($data as $k=>$v){
			$v['param']=json_decode($v['param'],true);
			foreach($params as $kk=>$vv){
				if($vv['type']==$v['param']['param_type']){
					$v['param']['param_type']=$vv['title'];
				}
			}
			$data[$k]=$v;
		}
		
        $this->assign("data", $data);
        $this->assign("notice", $notice);
		$this->assign("params", $params);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $notice = I('get.notice', 0);
        $model = D("banner");
        $data["info"] = $model->find($id);
        // dump($notice);

        $params = $this->GetParam();

        if($doinfo == "modify"){
            $d["type"] = I("get.type");
			$d["title"] = I("post.title",'');
			$d["subtitle"] = I("post.subtitle",'');
            $d["status"] = I("post.status", 1);
            $d["image"] = I("post.image");
            $d["ordernum"] = I("post.ordernum", 0);
            $d["remark"] = I("post.remark");
            $d["notice"] = I("post.notice");

            //有公告的时候可以没有图片
            if(empty($d["notice"])){
                if(empty($d["image"])){
                    $this->error('请上传图片');
                }
            }
			
			if(!is_http($d['image'])){
				if(!is_file('.'.$d['image'])){
					$this->error($d["image"].'图片路径无效');
				}
			}
            $param = array("param_type"=>I("param_type", 0), "param_id"=>I("param_id", 0));
            $current = $params["param_".$param["param_type"]];
            if($current){
                if($current["type"] != 10){
                    $d["link"] = $current["link"].$param["param_id"];
                } else{
                    $d["link"] = $param["param_id"];
                }
            }
            $d["param"] = json_encode($param);

            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            if($d["type"]!=12){
				$this->redirect("Banner/listad", $this->getMap());
			}else{
				$this->redirect("Banner/modifyad?type=12&id=50");
			}
        }

        $data["info"]["param"] = json_decode($data["info"]["param"], true);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->assign("notice", $notice);
        $this->assign("param", $params);
    	$this->show();
    }

    private function GetParam(){
        $data = array(
            "param_1"=>array("title"=>"商品详情", "type"=>"1", "link"=>"/pages/ShoppingMall/product/product?id="),
            "param_2"=>array("title"=>"配餐详情", "type"=>"2", "link"=>"/pages/ShoppingMall/Letter_details/Letter_details?type=0&id="),
            "param_3"=>array("title"=>"1元上门定制适老家具", "type"=>"3", "link"=>"/pages/ShoppingMall/Customized/Customized?id="),
            "param_4"=>array("title"=>"适老改造详情", "type"=>"4", "link"=>"/pages/ShoppingMall/Letter_details/Letter_details?type=2&id="),
            "param_5"=>array("title"=>"社交详情", "type"=>"5", "link"=>"/pagesB/search/search?id="),
            "param_6"=>array("title"=>"机构详情", "type"=>"6", "link"=>"/pagesA/organ/organDetail/organDetail?id="),
            "param_7"=>array("title"=>"家护师详情", "type"=>"7", "link"=>"/pagesA/AquaFresh/AquaFresh_details/AquaFresh_details?id="),
            "param_8"=>array("title"=>"服务项目详情", "type"=>"8", "link"=>"/pages/chaperonage/SetMeal/SetMeal?id="),
            "param_9"=>array("title"=>"健康详情", "type"=>"9", "link"=>"/pages/health/classify/classify?type="),
            "param_10"=>array("title"=>"其它链接", "type"=>"10", "link"=>""),
			"param_11"=>array("title"=>"资讯详情", "type"=>"11", "link"=>"/pages/classDetalis/details/details?id="),
			"param_12"=>array("title"=>"健康管理页", "type"=>"12", "link"=>"/pages/health/health/health?id="),
			"param_13"=>array("title"=>"服务项目列表", "type"=>"13", "link"=>"/pages/index/server/server?id="),
			"param_19"=>array("title"=>"养老科普", "type"=>"19", "link"=>"/pagesB/search-list/search-list?pid=0&id=1&searchid=1&oTitle=养老科普&id="),
			"param_20"=>array("title"=>"老人大学", "type"=>"20", "link"=>"/pagesB/search-list/search-list?searchid=2&oTitle=老人大学&id="),
			"param_21"=>array("title"=>"优惠券", "type"=>"21", "link"=>"/pagesA/myself/myCoupon/myCoupon?id="),
			"param_22"=>array("title"=>"邀请好友", "type"=>"22", "link"=>"/pages/index/invitation/invitation?id="),
			"param_23"=>array("title"=>"招聘", "type"=>"23", "link"=>"/pages/recruit/recruit?id="),
			"param_24"=>array("title"=>"应用内链接", "type"=>"24", "link"=>""),
			"param_25"=>array("title"=>"小程序商品详情", "type"=>"25", "link"=>"/pagesB/ShoppingMall/product/product?id="),
			"param_26"=>array("title"=>"小程序配餐详情", "type"=>"26", "link"=>"/pagesB/ShoppingMall/Letter_details/Letter_details?type=0&id="),
			"param_27"=>array("title"=>"小程序1元上门定制适老家具", "type"=>"27", "link"=>"/pagesB/ShoppingMall/Customized/Customized?id="),
			"param_28"=>array("title"=>"小程序适老改造详情", "type"=>"28", "link"=>"/pagesB/ShoppingMall/Letter_details/Letter_details?type=2&id="),
			"param_29"=>array("title"=>"小程序商品分类", "type"=>"29", "link"=>""),
			"param_30"=>array("title"=>"小程序服务分类", "type"=>"30", "link"=>""),
			"param_31"=>array("title"=>"小程序推荐活动详情", "type"=>"31", "link"=>"/pagesB/benefits/benIndex/benIndex?id="),
        );
        return $data;
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("banner");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("Banner/listad", $this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("banner");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("Banner/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("Banner/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }

    public function getMap(){
        $type = I("get.type");
        $notice = I("get.notice");
        $p = I("get.p");
        $map = array("type"=>$type,"p"=>$p,"notice"=>$notice);
        return $map;
    }
}