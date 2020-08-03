<?php
namespace CApi\Controller;
use Think\Controller;
class ArticleController extends BaseController {
	//社交首页
	public function index(){
		//顶部广告图
		$bannermodel = D("banner");
		$map = array("status"=>1, "type"=>8);
		$topbanner = $bannermodel->where($map)->order("ordernum asc")->select();
		foreach($topbanner as $k=>$v){
		    $v["image"] = $this->DoUrlHandle($v["image"]);
		    $topbanner[$k] = $v;
		}
		
		//热门推荐
		$model = D("mould_article");
		$map = array("status"=>1);
		$map["hot"] = 1;
		$order = "ordernum asc";
		$hot = $model->where($map)->order($order)->limit(0,6)->select();
		
		foreach($hot as $k=>$v){
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
			$v["avatar"] = $this->DoUrlHandle($v["avatar"]);
			$v["file_link"] = $this->DoUrlHandle($v["file_link"]);
			$hot[$k]=$v;
		}
		
		
		//中部轮播图
		$map = array("status"=>1, "type"=>9);
		$middlebanner = $bannermodel->where($map)->order("ordernum asc")->select();
		foreach($middlebanner as $k=>$v){
		    $v["image"] = $this->DoUrlHandle($v["image"]);
		    $middlebanner[$k] = $v;
		}
		
		$data = array(
		    "topbanner"=>$topbanner,"hot"=>$hot,"middlebanner"=>$middlebanner
		);
		return $data;
	}
	//热门搜索
	public function hot_search(){
		$where=array('status'=>1);
		$list=D('article_hot_search')->field('id,title')->where($where)->select();
		return $list;
	}
	//栏目首页
	public function column_index(){
		$id=I('get.id');
		if(empty($id)){
			E('缺少栏目ID');
		}
		//顶部广告图
		$bannermodel = D("banner");
		$map = array("status"=>1, "type"=>8);
		$topbanner = $bannermodel->where($map)->order("ordernum asc")->select();
		foreach($topbanner as $k=>$v){
		    $v["image"] = $this->DoUrlHandle($v["image"]);
		    $topbanner[$k] = $v;
		}
		$map=array('pid'=>$id);
		$list=D('article_column')->field('id,title,subtitle,image')->where($map)->select();
		if($list){
			foreach($list as $k=>$v){
				$v['image']=$this->DoUrlHandle($v['image']);
				$list[$k]=$v;
			}
			$data=$list;
		}else{
			$map=array("id"=>$id);
			$column_info=D('article_column')->where($map)->find();
			$data['subtitle']=$column_info['subtitle'];
		}
		
		//中部轮播图
		
		if($id==2){
			$map = array("status"=>1, "type"=>11);
		}else{
			$map = array("status"=>1, "type"=>9);
		}
		
		$middlebanner = $bannermodel->where($map)->order("ordernum asc")->select();
		foreach($middlebanner as $k=>$v){
		    $v["image"] = $this->DoUrlHandle($v["image"]);
		
		    $middlebanner[$k] = $v;
		}
		
		$data = array(
		    "topbanner"=>$topbanner,"data"=>$data,"middlebanner"=>$middlebanner
		);
		return $data;
	}
	//文章栏目
	public function column(){
		$pid=I('get.pid');
		$where=array();
		if(!empty($pid)){
			$where['pid']=$pid;
		}
		$list=D('article_column')->where($where)->select();
		foreach($list as $k=>$v){
			$v['image']=$this->DoUrlHandle($v['image']);
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$list[$k]=$v;
		}
		return $list;
	}
	//文章分类
	public function category(){
		$list=D('article_category')->select();
		return $list;
	}
	// 文章列表
    public function alist(){
        $model = D("mould_article");
        $map = array("status"=>1);
        $type = I("get.type", 0);
        if($type > 0){
            $map["type"] = $type;
        }
		$hot = I("get.hot");
		if(!empty($hot)){
		    $map["hot"] = $hot;
		}
		$column = I("get.column", 0);
		if($column > 0){
			$where["column"] = $column;
			$where["pcolumn"] = $column;
		    $where["_logic"] = "or";
		    $map["_complex"] = $where;
		}
        $keyword = I("get.keyword");
        if($keyword){
            $map["title"] = array("like", "%".$keyword."%");
        }

        $page = I("get.page", 1);
		$row = 10;
		$begin = ($page-1)*$row;
		$order = "ordernum asc";
		$count = $model->where($map)->count();
		$totalpage = ceil($count/$row);
		$list = $model->where($map)->order($order)->limit($begin, $row)->select();
        if($this->UserAuthCheckLogin()){
            $userid = $this->AuthUserInfo["id"];
            $record_model = D("user_record");
            foreach($list as $k=>$v){
                $map = array("userid"=>$userid, "source"=>1, "articleid"=>$v["id"]);
                $record = $record_model->where($map)->select();
				$v['share']=0;
				$v['collection']=0;
				$v['good']=0;
                foreach($record as $ik=>$iv){
                    switch($iv["type"]){
                        case 1: $v["share"] = 1; break;
                        case 2: $v["collection"] = 1; break;
                        case 3: $v["good"] = 1; break;
                    }
                }

                $v["avatar"] = $this->DoUrlHandle($v["avatar"]);
				if($v["thumb"]){
					$v["thumb"] = $this->DoUrlListHandle($v["thumb"]);
				}else{
					$v["thumb"] = $this->DoUrlListHandle('/upload/default/articlelogo.png');
				}
                
				$v['extimg']=strtolower(substr(strrchr($v['file_link'], '.'), 1));
                unset($v["file_link"]);
                $list[$k] = $v;
            }
        } else{
            foreach($list as $k=>$v){
                $v["avatar"] = $this->DoUrlHandle($v["avatar"]);
                if($v["thumb"]){
					$v["thumb"] = $this->DoUrlListHandle($v["thumb"]);
				}
				$v['extimg']=strtolower(substr(strrchr($v['file_link'], '.'), 1));
                unset($v["file_link"]);
				
                $list[$k] = $v;
            }
        }

        $this->SetPaginationHeader($totalpage, $count, $page, $row);

		return $list;
	}
	
	// 文章详情
    public function detail(){
        $id = I("get.id", 0);
        if($id <= 0){
            E("请选择要查看的文章");
        }

        $model = D("mould_article");
        $article = $model->find($id);
        if(empty($article)){
			E("文章不存在");
		}
		
		if(!in_array($article["status"], [1,-2])){
            E("文章状态异常，查看失败");
		}
			
        if($article["price"] <= 0){
            $article["free"] = 1;
        } else{
            $article["free"] = 0;
        }

        if($this->UserAuthCheckLogin()){
            $userid = $this->AuthUserInfo["id"];
            $record_model = D("user_record");
            $map = array("userid"=>$userid, "source"=>1, "articleid"=>$id);
            $record = $record_model->where($map)->select();
            foreach($record as $k=>$v){
                switch($v["type"]){
                    case 1: $article["share"] = 1; break;
                    case 2: $article["collection"] = 1; break;
                    case 3: $article["good"] = 1; break;
                }
            }

        }
        $http_type = "http://";
        if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')){
            $http_type = "https://";
        }
        //$article["content"] = preg_replace('/(<img.+?src=")(.*?)/','$1'.$http_type.$_SERVER['SERVER_NAME'].'$2', $article["content"]);
        $article["content"] = $this->UEditorUrlReplace($article["content"]);

        $section = array("<section>","</section>");
        $article["content"] = str_replace($section, "", $article["content"]);

		//增加阅读数
        $entity["browse_count"] = $article["browse_count"] + 1;
        $model->where("id=".$id)->save($entity);
		if($article["avatar"]){
			$article["avatar"] = $this->DoUrlHandle($article["avatar"]);
		}else{
			$article["avatar"] = $this->DoUrlHandle('/public/Admin/img/a20786397ee9edc0761474b9236abc2.png');
		}
        
        $article["thumb"] = $this->DoUrlListHandle($article["thumb"]);
		$article["thumb"] = $article["thumb"][0];
		$ext=strtolower(substr(strrchr($article['file_link'], '.'), 1));
		$article["file_link"] = $this->DoUrlHandle($article["file_link"]);
		if($ext='wma' || $ext='mp3' || $ext='rm' || $ext='wav' || $ext='midi' || $ext='ape' || $ext='flac'){
			$article['file_type']="radio";
		}elseif($ext='avi' || $ext='mov' || $ext='qt' || $ext='asf' || $ext='navi' || $ext='divx' || $ext='mpeg' || $ext='mpg' || $ext='dat' || $ext='mp4'){
			$article['file_type']="video";
		}else{
			$article['file_type']="other";
		}
		$where=array();
		$where['g.status']=1;
		$where['g.articleid']=$id;
		$article['relevant_list']=D('article_good')->alias('g')->join('LEFT JOIN sj_product p on g.good_id=p.id')->field('p.id,p.title,p.thumb,p.price')->where($where)->limit(6)->select();
		foreach($article['relevant_list'] as $k=>$v){
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$article['relevant_list'][$k]=$v;
		}
		$platform=$this->GetHttpHeader('platform');
		if($platform=='xcx'){
			$off=F('off');
			$article['off']=$off['off'];
		}else{
			$article['off']='1';
		}
        return $article;
	}
	
	//文章评论列表
    public function comment(){
        $articleid = I("get.articleid", 0);
        $page = I("get.page", 1);
        $row = 10;
        $begin = ($page-1)*$row;

        if($this->UserAuthCheckLogin()) {
            $userid = $this->AuthUserInfo["id"];
        }
        $record_model = D("user_record");

        //文章评论列表
        $comment_model = D("article_comment");
        
        $order = "createdate desc";
        $map = array("status"=>1, "articleid"=>$articleid, "reply"=>0);
        $count = $comment_model->where($map)->count();
        $totalpage = ceil($count/$row);
        $list = $comment_model->where($map)->limit($begin, $row)->order('createdate desc')->select();
        foreach($list as $k=>$v){
            $map = array("status"=>1, "articleid"=>$articleid, "reply"=>1, "commentid"=>$v["id"]);
            $item = $comment_model->where($map)->select();
            foreach($item as $ik=>$iv){
                $iv["avatar"] = $this->DoUrlHandle($iv["avatar"]);
                $iv["reply_avatar"] = $this->DoUrlHandle($iv["reply_avatar"]);
                $iv["content"] = str_replace("/Public/Home/content/emoji/", $this->GetDomainSite()."/Public/Home/content/emoji/", $iv["content"]);
                $item[$ik] = $iv;
            }
            $v["item"] = $item;

            $v["avatar"] = $this->DoUrlHandle($v["avatar"]);
            $v["reply_avatar"] = $this->DoUrlHandle($v["reply_avatar"]);
            $v["content"] = str_replace("/Public/Home/content/emoji/", $this->GetDomainSite()."/Public/Home/content/emoji/", $v["content"]);
            $v["good"] = 0;
            if ($userid) {
                $map = array("userid"=>$userid, "source"=>3, "articleid"=>$v["id"], "type"=>4);
                $record = $record_model->where($map)->find();
                if ($record) {
                    $v["good"] = 1;
                }
            }

            $list[$k] = $v;
		}
        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        $ret = array();
        $ret['count'] = $count;
        $ret['list'] = $list;

        return $ret;
    }

    // 文章评论点赞
    public function commentgood(){

        $comment_model = D("article_comment");

        $commentid = I("post.commentid", 0);
        $comment = $comment_model->find($commentid);
        if(empty($comment)){
            E("选择的评论不存在");
        }
        if(!$this->UserAuthCheckLogin()){
            E("您还未登录，请点击确定前往登录",21);
        }
        $userid = $this->AuthUserInfo["id"];

        $record_model = D("user_record");

        $data = array("status"=>0, "count"=>0);

        $map = array("userid"=>$userid, "type"=>4, "articleid"=>$commentid, "source"=>3);
        $check = $record_model->where($map)->find();
        if(empty($check)){
            $entity = array(
                "userid"=>$userid, "source"=>3, "type"=>4,
                "articleid"=>$commentid, "createdate"=>date("Y-m-d H:i:s")
            );
            $record_model->add($entity);

            $entity = array();
            $data["count"] = $entity["good_count"] = $comment["good_count"] + 1;
            $comment_model->where("id=".$commentid)->save($entity);

            $data["status"] = 1; //增加
        } else{
            $record_model->where($map)->delete();

            $entity = array();
            $data["count"] = $entity["good_count"] = $comment["good_count"] - 1;
            $comment_model->where("id=".$commentid)->save($entity);

            $data["status"] = 2;

        }

        return $data;
    }
}