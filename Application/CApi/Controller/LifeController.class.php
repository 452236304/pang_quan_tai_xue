<?php
namespace CApi\Controller;
use Think\Controller;
//生活
class LifeController extends BaseController {
    /**
     * 首页
     * @return array
     */
    public function index(){
        //生活首页分类图标
        $cate=D('column')->where('is_lock != 2 AND status=1')->select();
		foreach($cate as $k=>&$v){
			$v['thumb'] = $this->DoUrlHandle($v['thumb']);
		}

        // 轮播图
		$bannermodel = D('banner');
        $map = array("status"=>1, "type"=>21);
        $banner = $bannermodel->where($map)->order("ordernum asc")->select();
        foreach ($banner as $k=>&$v) {
            $v["image"] = $this->DoUrlHandle($v["image"]);

            if($v["param"]){
                $v["param"] = json_decode($v["param"], true);
            } else{
                $v["param"] = array("param_type"=>"-1", "param_id"=>"");
            }
        }
		
		//公告
		$map = array('notice'=>array('neq',''),'status'=>1);
		$notice_data = $bannermodel->where($map)->order("ordernum asc")->select();
		foreach ($notice_data as $k=>&$v) {
		    $v["image"] = $this->DoUrlHandle($v["image"]);
		
		    if($v["param"]){
		        $v["param"] = json_decode($v["param"], true);
		    } else{
		        $v["param"] = array("param_type"=>"-1", "param_id"=>"");
		    }
		
		}

        // 热门抢购
        $productmodel = D('product');
        $map = ['seckill'=>1];
        $product = $productmodel->where($map)->order('seckill_orderby ASC')->limit(3)->select();
        foreach($product as $k=>&$v){
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

        }

        // 精品优质(中间的大小广告图)
        $map = array("status"=>1, "type"=>22);
        $big_banner = $bannermodel->where($map)->order("ordernum asc")->select();
        foreach ($big_banner as $k=>&$v) {
            $v["image"] = $this->DoUrlHandle($v["image"]);

            if($v["param"]){
                $v["param"] = json_decode($v["param"], true);
            } else{
                $v["param"] = array("param_type"=>"-1", "param_id"=>"");
            }

        }
        $map = array("status"=>1, "type"=>23);
        $small_banner = $bannermodel->where($map)->order("ordernum asc")->select();
        foreach ($small_banner as $k=>&$v) {
            $v["image"] = $this->DoUrlHandle($v["image"]);

            if($v["param"]){
                $v["param"] = json_decode($v["param"], true);
            } else{
                $v["param"] = array("param_type"=>"-1", "param_id"=>"");
            }

        }

        $map = array('p.recommend_for_you'=>1,'p.status'=>1,"p.shelf"=>1, 'a.status'=>1,'p.type'=>0);
        $recommendedProduct=D('product')->alias('p')->field('p.*')->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')->group('p.id')->where($map)->order('p.recommend_orderby')->select();
        foreach($recommendedProduct as $k=>&$v){
            $v['label']=json_decode($v['label'],true);
            $v['thumb']=$this->DoUrlHandle($v['thumb']);
            $v['market_price']=floatval($v['market_price']);
        }

        return array('cate'=>$cate,'banner'=>$banner, 'product'=>$product, 'big_banner'=>$big_banner, 'small_banner'=>$small_banner, 'recommendedProduct'=>$recommendedProduct,'notices'=>$notice_data);
    }

    /**
     * 为你推荐
     * @return array
     */
    public function recommended_product(){
        $row = I('get.row',6);
        $page = I('get.page',1);
        $begin = ($page-1)*$row;
		
        $map = array('p.recommend_for_you'=>1,'p.status'=>1,"p.shelf"=>1, 'a.status'=>1,'p.type'=>0);
        $recommend_product=D('product')->alias('p')->field('p.*')->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')->group('p.id')->where($map)->select();
        foreach($recommend_product as $k=>$v){
            $v['label']=json_decode($v['label'],true);
            $v['thumb']=$this->DoUrlHandle($v['thumb']);
            $v['type']=2;
            $v['price']=($v['price']);;
            $v['market_price']=floatval($v['market_price']);
            $recommend_product[$k]=$v;
        }
        $recommend=$recommend_product;
        orderby($recommend,'recommend_orderby','asc');
        orderby($recommend,'top','desc');
        $list = [];
        for($i=$begin+1;$i<=$begin+$row;$i++){
//        $list=$recommend;
            array_push($list, $recommend[$i]);
        }

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
        $this->SetPaginationHeader($totalpage,$count,$page,$row);
        return $list;
    }
	
	
}