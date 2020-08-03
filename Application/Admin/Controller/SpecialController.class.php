<?php
namespace Admin\Controller;
use Think\Controller;
class SpecialController extends BaseController {
    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "ordernum asc";
		$type=I('get.type');
        if($type > 0){
            $map['type'] =$type;
        }
        $data = $this->pager("special", "10", $order, $map, $param);
        $this->assign($data);
        $this->assign("map", $this->getMap());
        $this->assign("types", $this->getTypes());
        $this->display();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("special");
        $data["info"] = $model->find($id);
        if($doinfo == "modify"){
            $d["type"] = I("post.type");
            $d["status"] = I("post.status", 1);
            $d["title"] = I("post.title");
            $d["content"] = htmlspecialchars_decode(I("post.content"));
            $d["ordernum"] = I("post.ordernum", 0);
            $d["video_file"] = I("post.file_link");
			if($read_time=I("post.read_time")){
				$d["read_time"] = $read_time;
			}
			$d["thumb"] = I("thumb");
			if(empty($d["thumb"])){
				$this->error('请上传图片');
			}
			if(!is_http($d['thumb'])){
				if(!is_file('.'.$d['thumb'])){
					$this->error($d["thumb"].'图片路径无效');
				}
			}
			$d["author_id"] = I("author_id", 0, 'intval');
			if(empty($d["author_id"])){
				$this->error('请选择作者');
			}
            $d["category_id"] = I("category_id", 0, 'intval');
            if(empty($d["category_id"])){
                $this->error('请选择分类');
            }
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
				$d["createtime"] = date("Y-m-d H:i:s");
                $model->add($d);
            }
            
            $this->redirect("Special/listad",'type='.$d['type']);
        }
        $this->assign($data);
        $this->assign("map", $this->getMap());

        $author = D('SpecialAuthor')->field('id, name')->select();
        $this->assign('author', $author);
        $category = D('SpecialCategory')->field('id, title')->order('sort')->select();
        $this->assign('category', $category);
    	$this->display();
    }
	public function audiolist(){
	    $order = "ordernum asc";
		$id=I('get.id');
	    if($id > 0){
	        $map['special_id'] =$id;
	    }
	    $data = $this->pager("special_audio", "10", $order, $map, $param);
	    $this->assign($data);
	    $this->assign("map", $this->getMap());
	    $this->assign("types", $this->getTypes());
	    $this->display();
	}
	public function audioad(){
	    $id = I("get.id", 0);
		$doinfo = I("get.doinfo");
	    $model = D("special_audio");
	    $data["info"] = $model->find($id);
		$special_id=$data["info"]['special_id'];
		
		$data['info']['read_time']=floor(($data['info']['read_time'])/60).':'.(($data['info']['read_time'])%60);
	    if($doinfo == "modify"){
	        $d["status"] = I("post.status", 1);
	        $d["title"] = I("post.title");
			$d["thumb"] = I("post.thumb");
	        $d["ordernum"] = I("post.ordernum", 0);
			$read_time=explode(':',I('post.read_time'));
			$d["read_time"] = $read_time[0]*60+$read_time[1];
	        $d["file_link"] = I("post.file_link");
			$d["special_id"] = I("post.special_id");
			$id=I('post.id');
			//查询其他音频时长
			$audio_info=D('special_audio')->field('SUM(read_time) read_time')->group('special_id')->where(array('special_id'=>$d['special_id']))->find();
			
			//存储到专题的总阅读时长
			$special_time=floor(($audio_info['read_time']+$d['read_time'])/60).':'.(($audio_info['read_time']+$d['read_time'])%60);
			$rs=D('special')->where(array('id'=>$d['special_id']))->save(array('read_time'=>$special_time));
			$file = $this->uploadfile("article", array("gif","pptx","ppt","pdf","jpg","jpeg","png"));
			if($file){
				$thumb = $file["thumb"]["savename"];
				if($thumb){
				    $d["thumb"] = "/upload/article/".$thumb;
				}
			}
	        if($id > 0){
	            $rs=$model->where(array('id'=>$id))->save($d);
	        }else{
				$d["createtime"] = date("Y-m-d H:i:s");
	            $id=$model->add($d);
	        }
	        
	        $this->redirect("Special/audiolist",'id='.$d['special_id']);
	    }
	    $this->assign($data);
	    $this->assign("map", $this->getMap());
		$this->display();
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
    	$model = D("special");
    	$id = I("get.id");
        $model->delete($id);

    	$this->redirect("Special/listad", $this->getMap());
    }
	public function delaudio(){
		$model = D("special_audio");
		$id = I("get.audio_id");
	    $model->delete($id);
		$this->redirect("Special/audiolist", $this->getMap());
	}
    /**
     * [statusAd]
     * @return [type] [description]
     */
    public function topad(){
        $id = I("get.id", 0);
        $d['top'] = I("get.top", 0);
        $model = D("special");
        if($id > 0){
            $model->where("id=".$id)->save($d);
        }else{
            $model->add($d);
        }
        $this->redirect("Special/listad", $this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("special");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("Special/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("Special/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }

    public function getMap(){
        $p = I("get.p");
		$id = I("get.id");
		$special_id = I("get.special_id");
        $type = I("get.type", 0);
        $status = I("get.status", 1);
        $map = array("p"=>$p, "type"=>$type, "keyword"=>$keyword, "status"=>$status,'id'=>$id,'special_id'=>$special_id);
        return $map;
    }

    public function upFuJian(){
        $file = $this->uploadfile("article",array('avi','mov','rmvb','flv','mp4','3gp','mp3','wma','rm','wav','midi','ape','flac'));
        $url = array();
        if ($file["file_link"]) {
            if ($file["file_link"]["size"] > 5097152000) {
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
                        D("special")->where('id='.$id)->save($save);
                    }
                }
            }
        }
        $info = array();
        $info['is'] = $is;
        $this->ajaxReturn($info, "json");
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
}
