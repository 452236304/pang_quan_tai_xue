<?php
namespace Admin\Controller;
use Think\Controller;
class ProductController extends BaseController {
    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $param = $this->getMap();
		
		if($param["categoryid"]){
			$map = array("categoryid"=>$param["categoryid"]);
		}else{
			$map = array("categoryid"=>array('not in','1,5'));
		}
		
		if($param['top']!==''){
			$map['top']=$param['top'];
		}
		if($param['recommend']!==''){
			$map['recommend']=$param['recommend'];
		}
		if($param['seckill']!==''){
			$map['seckill']=$param['seckill'];
		}
		
        if($param["keyword"]){
            $where["p.title"] = array("like","%".$param["keyword"]."%");
            $where["p.subtitle"] = array("like","%".$param["keyword"]."%");
            $where["_logic"] = "or";
            $map["_complex"] = $where;
        }
		$map['p.status']=array('neq','-1');
        $count = D("product")->alias('p')->where($map)->count();
        $model = D("product")->alias("p")->join("left join sj_category as c on p.typeid=c.id")->join('left join sj_column as co on c.parentid=co.id')->field("p.*,c.name,co.name column_name");
        $data = $this->pager(array("mo"=>$model, "count"=>$count), "10", "status desc,updatetime desc,createdate desc", $map);
        $this->assign($data);
        $this->assign("map", $param);
        $this->display();
    }
    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("product");

        if($doinfo == "modify"){
			if(I('get.categoryid')==5){
				$d["categoryid"] = 5;
			}else{
				$d["categoryid"] = I("post.typeid1",0);
			}
            
            $typeid2 = I("post.typeid2",0);
            $typeid3 = I("post.typeid3",0);
            if(empty($typeid2)){
                $d["typeid"] = 0;
            }else{
                if(empty($typeid3)){
                    $d["typeid"] = $typeid2;
                }else{
                    $d["typeid"] = $typeid3;
                }
            }
            // dump($d["typeid"]);die();


            $d["type"] = I("post.type", 0);
            $d["attribute_cpid"] = I("post.attribute_cpid", 0);
            $d["attribute_czid"] = I("post.attribute_czid", 0);
            $d["status"] = I("post.status", 1);
            $d["shelf"] = I("post.shelf", 1);
            $d["types"] = I("post.types", 0);
            $d["title"] = I("post.title");
            $d["subtitle"] = I("post.subtitle");
			$d["poster_title"] = I("post.poster_title");
            $d["thumb"] = I("post.thumb");
			$d["home_label"] = I("post.home_label");
			$d["home_label_after"] = I("post.home_label_after");
            $d["guess_like"] = I("post.guess_like");
            // 分销体系
            $part1 = I('part1');
            $part2 = I('part2');
            foreach ( $part1 as &$value){
                $value = intval($value);
            }
            foreach ( $part2 as &$value){
                $value = intval($value);
            }
            $team = [
                'part1' => $part1,
                'part2' => $part2,
            ];
            $d['team'] = json_encode($team);
			if(empty($d["thumb"])){
				$this->error('请上传图片');
			}
			if(!is_http($d['thumb'])){
                if(!is_file('.'.$d['thumb'])){
                    $this->error($d["thumb"].'图片路径无效');
                }
            }

            // if(empty($d["images"])){
            //     $this->error('请上传图片');
            // }
            // if(!is_http($d['images'])){
            //     if(!is_file('.'.$d['images'])){
            //         $this->error($d["images"].'图片路径无效');
            //     }
            // }
			
            $d["images"] = I("post.images");
            $d["spec_content"] = htmlspecialchars_decode(I("post.spec_content"));
            $d["content"] = htmlspecialchars_decode(I("post.content"));
            $d["price"] = I("post.price", 0);
            $d["market_price"] = I("post.market_price", 0);
            $d["brokerage"] = I("post.brokerage", 0);
            $d["stock"] = I("post.stock", 0);
            $d["freightid"] = I("post.freightid", 0);
            $d["top"] = I("post.top", 0);
            $d["discounts"] = I("post.discounts", 0);
            $d["recommend"] = I("post.recommend", 0);
			$d["seckill"] = I("post.seckill", 0);
			$d["seckill_orderby"] = I("post.seckill_orderby", 0);
			$d["everyday_seckill"] = I("post.everyday_seckill", 0);
			$d["everyday_seckill_orderby"] = I("post.everyday_seckill_orderby", 0);
            $d["ordernum"] = I("post.ordernum", 0);
			$d["recommend_orderby"] = I("post.recommend_orderby", 0);
            $d["attribute1"] = I("post.attribute1");
            $d["attribute2"] = I("post.attribute2");
            $d["attribute3"] = I("post.attribute3");
            $d["guess_like"] = I("post.guess_like");
			$d["recommend_for_you"] = I("post.recommend_for_you", 0);
			$d["recommend_for_you_ordernum"] = I("post.recommend_for_you_ordernum", 0);
            $attr = array(
                "attr"=>I("post.attr"),
                "color"=>I("post.color")
            );
            $d["label"] = json_encode($attr);

            if ($d["categoryid"] == 1) {
                //配餐
                $d["res_type"] = I("post.res_type");
            }
			$d["updatetime"] = date("Y-m-d H:i:s");
            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i:s");
            }
			
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("Product/listad", $this->getMap());
        }
        if($id){
            $data["info"] = $model->find($id);
            $data["info"]['team'] = json_decode($data["info"]['team'], true);
            $typeid2_data = [];
            $typeid3_data = [];
            // dump($data["info"]);
            $select_pid_category = D('category')->where('id='.$data["info"]['typeid'])->find();
            if($select_pid_category['depth']==2){
                $typeid2_data = D('category')->where('depth=1 AND parentid='.$data["info"]['categoryid'])->select();
                $typeid3_data = D('category')->where('depth=2 AND category_pid='.$select_pid_category['category_pid'])->select();
            }else{
                $typeid2_data = D('category')->where('depth=1 AND parentid='.$data["info"]['categoryid'])->select();
                $typeid3_data = '';
            }

            $this->assign("select_pid_category", $select_pid_category);
            $this->assign("typeid2_data", $typeid2_data);
            $this->assign("typeid3_data", $typeid3_data);
        }
        

        $data["info"]["label"] = json_decode($data["info"]["label"], true);

        $this->assign($data);
        
        $categoryid = I("get.categoryid", 0); 
        if(!in_array($categoryid, [1, 5])){
            //商品分类
            $map = array("status"=>1);
			if($categoryid){
				$map['parentid']=$categoryid;
			}else{
				$map['parentid']=array('not in','1,5');
			}
            $category = D("column")->where($map)->select();
            $this->assign("category", $category);

            $attributeM = D("attribute");
            //产品分类
            $map = array("status"=>1, "type"=>1);
            $attribute_cp = $attributeM->where($map)->select();
            $this->assign("attribute_cp", $attribute_cp);
            //材质分类
            $map = array("status"=>1, "type"=>2);
            $attribute_cz = $attributeM->where($map)->select();
            $this->assign("attribute_cz", $attribute_cz);

            //运费模版
            $freightmodel = D("product_order_freight");
			if($this->checkpower('company/product')){
				$map=array('company_id'=>$_SESSION["manID"]);
			}else{
				$map=array('company_id'=>0);
			}
            $freight = $freightmodel->where($map)->select();
            $this->assign("freight", $freight);
        }

        $this->assign("map", $this->getMap());
    	$this->display();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("product");
    	$id = I("get.id");
		$map = array('id'=>$id);
		$entity = array('status'=>'-1');
    	$model->where($map)->save($entity);
    	$this->redirect("Product/listad", $this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("product");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("Product/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("Product/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }

    public function topad(){
        $id = I("get.id", 0);
        $d['top'] = I("get.top", 0);
        $model = D("product");
        if($id > 0){
            $model->where("id=".$id)->save($d);
        }else{
            $model->add($d);
        }
        $this->redirect("Product/listad", $this->getMap());
    }

    public function getMap(){
        $categoryid = I("get.categoryid");
        $keyword = I("get.keyword");
		$recommend = I("get.recommend");
		$seckill = I("get.seckill");
		$top = I("get.top");
        $p = I("get.p");
        $map = array("p"=>$p, "categoryid"=>$categoryid, "keyword"=>$keyword,'recommend'=>$recommend,'seckill'=>$seckill,'top'=>$top);
        return $map;
    }

    public function get_attribute(){
        $typeid1 = I('get.typeid1','');
        $typeid2 = I('get.typeid2','');
        // echo $typeid2;
        if(!empty($typeid1)){
            $first_data = D('category')->where('depth=1 AND parentid='.$typeid1)->select();
            foreach($first_data as $k=>$v){
                echo "<option value='{$v['id']}'>{$v['name']}</option>";
            }
        }
        if(!empty($typeid2)){
            $second_data = D('category')->where('category_pid='.$typeid2)->select();
            foreach($second_data as $k=>$v){
                echo "<option value='{$v['id']}'>{$v['name']}</option>";
            }
        }
        // foreach($list as $k=>$v){
        //     echo "<option value='{$v['id']}'>{$v['title']}</option>";
        // }
        exit;
    }
}