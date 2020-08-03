<?php
namespace Admin\Controller;
use Think\Controller;
class MouldArticleController extends BaseController {
    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "ordernum asc";
        $param = $this->getMap();
        $map = array("status"=>$param["status"]);
        if($param["type"] > 0){
            $map['type'] = I("post.type", 0);
        }
        if($param["keyword"]){
            $map["title"] = array("like","%".I("post.keyword")."%");
        }
        $data = $this->pager("mould_article", "10", $order, $map, $param);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->assign("types", $this->getTypes());
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("mould_article");
        $data["info"] = $model->find($id);

        $user_id = $data["info"]['userid'];

        if($doinfo == "modify"){
            $d["type"] = I("post.type");
			$category_info=D('article_category')->where('id='.$d['type'])->find();
			$d['type_name']=$category_info['title'];
			$d["column"] = I("post.column");
			$column_info=D('article_column')->where('id='.$d['column'])->find();
			$d['pcolumn']=$column_info['pid'];
			$d['column_name']=$column_info['title'];
            $d["status"] = I("post.status", 1);
            $d["title"] = I("post.title");
            $d["name"] = I("post.name");
            $d["intro"] = I("post.intro");
			$d["relevant"] = I("post.relevant");
            $d["content"] = htmlspecialchars_decode(I("post.content"));
            $d["source"] = I("post.source");
            $d["share_count"] = I("post.share_count", 0);
            $d["collection_count"] = I("post.collection_count", 0);
            $d["good_count"] = I("post.good_count", 0);
            $d["ordernum"] = I("post.ordernum", 0);
            $d["browse_count"] = I("post.browse_count", 0);
            $d["hot"] = I("post.hot", 0);
            $d["file_link"] = I("post.file_link");
			$d["thumb"] = I("post.thumb");
            $file = $this->uploadfile("article", array("gif","pptx","ppt","pdf","jpg","jpeg","png","xls","xlsx","doc","zip","rar","7z","txt"));
            if($file){
                $avatar = $file["avatar"]["savename"];
                if($avatar){
                    $d["avatar"] = "/upload/article/".$avatar;
                }
            }
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
				$d["createdate"] = date("Y-m-d H:i");
                $model->add($d);
            }
            
            $this->redirect("MouldArticle/listad", $this->getMap());
        }
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->assign("types", $this->getTypes());
		$this->assign("column", $this->getColumn());
    	$this->show();
    }
	
    private function getTypes(){
        $list=D('article_category')->select();
    	return $list;	
    }
	private function getColumn(){
	    $list=D('article_column')->select();
		return $list;	
	}
    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("mould_article");
    	$id = I("get.id");
        $model->delete($id);
        
        //删除与该文章交互的用户记录
        D("user_record")->where("articleid=".$id)->delete();

    	$this->redirect("MouldArticle/listad", $this->getMap());
    }

    /**
     * [statusAd]
     * @return [type] [description]
     */
    public function topad(){
        $id = I("get.id", 0);
        $d['top'] = I("get.top", 0);
        $model = D("mould_article");
        if($id > 0){
            $model->where("id=".$id)->save($d);
        }else{
            $model->add($d);
        }
        $this->redirect("MouldArticle/listad", $this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("mould_article");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("MouldArticle/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("MouldArticle/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }

    public function getMap(){
        $p = I("get.p");
        $type = I("post.type", 0);
        $keyword = I("post.keyword");
        if(empty($keyword)){
            $keyword = mb_convert_encoding($_GET["keyword"], "UTF-8", "gb2312");
        }
        $status = I("get.status", 1);
        $map = array("p"=>$p, "type"=>$type, "keyword"=>$keyword, "status"=>$status);
        return $map;
    }

    public function upFuJian(){
        $file = $this->uploadfile("article", array("pptx","ppt","pdf","jpg","jpeg","png","xls","xlsx","doc","zip","rar","7z","txt","apk",'avi','mov','rmvb','flv','mp4','3gp','mp3','wma','rm','wav','midi','ape','flac'));
        $url = array();
        if ($file["file_link"]) {
            if ($file["file_link"]["size"] > 20971520) {
                $url["is_yl"] = 1;
            }else{
                $file_link = $file["file_link"]["savename"];

                $url["is_yl"] = 0;
                $url["file_link"] = "/upload/article/" . $file_link;
                $url["online_url"] = "http://ow365.cn/?i=17473&n=2&furl=http://".$_SERVER['SERVER_NAME']."/upload/article/" . $file_link;
                $url["online_url_info"] = "http://ow365.cn/?i=17473&info=0&furl=http://".$_SERVER['SERVER_NAME']."/upload/article/" . $file_link;
            }
            $url["file_name"] = $file["file_link"]['name'];
        }
        $this->ajaxReturn($url, "json");
    }

    /*
     * 删除附件
     * */
    public function delfile()
    {
        $file = I('post.file');
        $id = I('post.id',0,'int');
        $is = 0;
        if ($file) {
            $file = explode('/',$file);
            //$this->ajaxReturn($file, "json");
            unset($file[0]);
            unset($file[1]);
            unset($file[2]);
            $fine_url = implode('/',$file);
            $path = __DIR__.'/../../../upload/userfiles/'.$fine_url;
            if (file_exists($path)) {
                $ret = unlink($path);
                if ($ret) {
                    $is = 1;
                    if (is_numeric($id) && $id) {
                        $save = array();
                        $save['file_link'] = null;
                        $save['online_url'] = null;
                        $save['online_url_page'] = null;
                        D("mould_article")->where('id='.$id)->save($save);
                    }
                }
            }
        }
        $info = array();
        $info['is'] = $is;
        $this->ajaxReturn($info, "json");
    }

    public function wzsh()
    {
        $id = I("get.id", 0);
        if (empty($id)) {
            $this->error('缺少id');
        }
        $model = D("mould_article");
        $is = $model->where('id='.$id)->save(array('status'=>1));
        if ($is) {
            $data = $model->find($id);

            $user_id = $data['userid'];
            if ($user_id) {
                //审核通过，奖励金币
                $user_model = D("user");
                $article_user = $user_model->find($user_id);

                if($article_user){
                    $consume_model = D("user_consume");
                    $gold = 2;
                    $map = array("userid"=>$user_id, "orderid"=>$id, "income"=>2, "type"=>3);
                    $check = $consume_model->where($map)->find();

                    if(empty($check)){
                        $balance = $article_user["user_money"] + $gold;
                        $entity = array("user_money"=>$balance);
                        $user_model->where("id=".$article_user["id"])->save($entity);

                        //用户消费记录
                        $entity = array(
                            "userid"=>$user_id, "orderid"=>$id, "type"=>3, "income"=>2, "title"=>$data["title"],
                            "amount"=>$gold, "balance"=>$balance, "createdate"=>date("Y-m-d H:i:s")
                        );
                        $consume_model->add($entity);
                    }
                }
            }
        }
        $this->redirect("MouldArticle/listad", $this->getMap());

    }

    public function filesize()
    {
        $file = I("post.file_url");
        if (empty($file)) {
            $this->ajaxError('缺少参数');
        }
        $file = explode('/',$file);
        unset($file[0]);
        unset($file[1]);
        unset($file[2]);
        $fine_url = implode('/',$file);
        $path = __DIR__.'/../../../upload/userfiles/'.$fine_url;

        $ret = array();
        $ret['size'] = '';
        $ret['is_yl'] = 0;
        if (file_exists($path)) {
            $size = filesize($path);
            if ($size > 20971520) {
                $ret['is_yl'] = 1;
            }
            $ret['size'] = $size;
        }else{
            $this->ajaxError('文件不存在');
        }

        $this->ajaxReturn($ret, "json");
    }
	//https请求(支持GET和POST)
	public function http_request($url,$data = null){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		if(!empty($data)){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		//var_dump(curl_error($curl));
		curl_close($curl);
		return $output;
	}
	function subtitle(){
		$where['subtitle']=array(array('EXP','IS NULL'),array('eq',''),'or');
		
		$list=D('mould_article')->where($where)->select();
		$result=array();
		foreach($list as $v){
			if($v['file_link']){
				$ow_info=$this->http_request("http://ow365.cn/?i=17473&info=0&furl=http://".$_SERVER['SERVER_NAME'].$v["file_link"]);
				$ow_info=json_decode($ow_info,true);
				if($ow_info['Text']==null){
					if($ow_info['SlideNames']==''){
						$txt=file_get_contents("http://ow365.cn/?i=17473&info=0&furl=http://".$_SERVER['SERVER_NAME'].$v["file_link"]);
						if($txt){
							$result[]=$txt;
						}else{
							$result[]=$v['id'];
						}
					}else{
						$result[]=$d['subtitle']=$ow_info['SlideNames'];
						
					}
				}else{
					$result[]=$d['subtitle']=$ow_info['Text'];
				}
				
				D('mould_article')->where("id=".$v['id'])->save($d);
			}
		}
		var_dump($result);
	}
	public function good(){
		$order = "";
		$param = $this->getMap();
		$map=array();
		$map['g.articleid']=I('get.articleid');
		$count=D('article_good')->alias('g')->join('LEFT JOIN sj_product p on g.good_id=p.id')->where($map)->count();
		$model=D('article_good')->field('g.id,g.createtime,g.status,p.title')->alias('g')->join('LEFT JOIN sj_product p on g.good_id=p.id');
		$data = $this->pager(array('mo'=>$model,'count'=>$count), "10", null, $map, $param);
		$this->assign($data);
		$this->assign("map", $this->getMap());
		$this->assign("types", $this->getTypes());
		$this->assign('articleid',I('get.articleid'));
		$this->show();
	}
	public function goodad(){
		$id = I("get.id", 0);
		$doinfo = I("get.doinfo");
		$articleid=I('get.articleid');
		$model = D("article_good");
		$data["info"] = $model->find($id);
		if($doinfo == "modify"){
			$d["articleid"] = $articleid;
		    $d["good_id"] = I("post.good_id");
			$d["status"] = I("post.status");
			
		    if($id > 0){
		        $model->where("id=".$id)->save($d);
		    }else{
				$d["createtime"] = date("Y-m-d H:i:s");
		        $model->add($d);
		    }
		    $this->redirect("MouldArticle/good?articleid=".$articleid);
		}
		$where=array();
		$where['p.status']=1;
		$where['pa.status']=1;
		$good=D('product')->alias('p')->field('p.id,p.title')->join('LEFT JOIN sj_product_attribute pa on p.id=pa.productid')->group('p.id')->where($where)->select();
		$this->assign('good',$good);
		$this->assign($data);
		$this->assign('articleid',$articleid);
		$this->assign("map", $this->getMap());
		$this->assign("types", $this->getTypes());
		$this->assign("column", $this->getColumn());
		$this->show();
	}
	public function gooddel(){
		$model = D("article_good");
		$id = I("get.id");
	    $model->delete($id);
		$this->redirect("MouldArticle/good", $this->getMap());
	}
	
	public function column(){
		$order = "";
		$param = $this->getMap();
		$map=array();
		$map['status']=1;
		$count=D('article_column')->where($map)->count();
		$model=D('article_column');
		$data = $this->pager(array('mo'=>$model,'count'=>$count), "10", null, $map, $param);
		$this->assign($data);
		$this->assign("map", $this->getMap());
		$this->assign("types", $this->getTypes());
		$this->show();
	}
	public function columnad(){
		$id = I("get.id", 0);
		$doinfo = I("get.doinfo");
		$model = D("article_column");
		$data["info"] = $model->find($id);
		if($doinfo == "modify"){
			$d["title"] = I("post.title");
			$d["subtitle"] = I("post.subtitle");
			$d["thumb"] = I("post.thumb");
			$d["image"] = I("post.image");
			
		    if($id > 0){
		        $model->where("id=".$id)->save($d);
		    }
		    
		    $this->redirect("MouldArticle/column", $this->getMap());
		}
		$where=array();
		$where['status']=1;
		$good=D('product')->field('id,title')->where($where)->select();
		$this->assign('good',$good);
		$this->assign($data);
		$this->assign("map", $this->getMap());
		$this->assign("types", $this->getTypes());
		$this->assign("column", $this->getColumn());
		$this->show();
	}
	public function columndel(){
		$model = D("article_column");
		$id = I("get.id");
	    $model->delete($id);
		$this->redirect("MouldArticle/good", $this->getMap());
	}
}
