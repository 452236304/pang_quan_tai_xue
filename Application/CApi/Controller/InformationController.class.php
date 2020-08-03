<?php
namespace CApi\Controller;
use Think\Controller;
class InformationController extends BaseController {
	
	//照护课堂列表
	public function lists(){
		//关键字
		$keyword = I("get.keyword");

		$type = I("get.type");
		if(!in_array($type, [1,2])){
			E("请选择要查看照护课程的分类");
		}

		$model = D("information");

		$map = array("status"=>1, 'type'=>$type);
		if($keyword){
			$where["title"] = array("like", "%".$keyword."%");
			$where["subtitle"] = array("like", "%".$keyword."%");
			$where["_logic"] = "or";
			$map["_complex"] = $where;
		}

		$page = I("get.page", 1);
        $row = I("get.row", 10);
		$begin = ($page-1)*$row;

		$order = "top desc, ordernum asc, browser_count desc, collection_count desc, good_count desc, share_count desc";
		$count = $model->where($map)->count();
        $totalpage = ceil($count/$row);
		$list = $model->where($map)->order($order)->limit($begin, $row)->select();

		$this->SetPaginationHeader($totalpage, $count, $page, $row);

		foreach($list as $k=>$v){
			$list[$k]["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $list[$k]["source_logo"] = $this->DoUrlHandle($v["source_logo"]);
			$list[$k]["collection_count"] = htmlspecialchars($v["collection_count"]);
            $list[$k]["time"] = date("Y-m-d", strtotime($v["time"]));
            $list[$k]["newstime"] = date("Y-m-d", strtotime($v["newstime"]));
		}

		return $list;
	}

	//照护课堂详情
	public function detail(){
		$id = I("get.id", 0);

		$model = D("information");

		$map = array("status"=>1, "id"=>$id);
		$detail = $model->where($map)->find();
		if(empty($detail)){
			E("照护课堂不存在");
		}

		$detail["thumb"] = $this->DoUrlHandle($detail["thumb"]);
		$detail["video"] = $this->DoUrlHandle($detail["video"]);
        $detail["source_logo"] = $this->DoUrlHandle($detail["source_logo"]);
        $detail["content"] = $this->UEditorUrlReplace($detail["content"]);
        $detail["time"] = date("Y-m-d", strtotime($detail["time"]));
        $detail["newstime"] = date("Y-m-d", strtotime($detail["newstime"]));

		//是否已收藏
        $detail["is_collection"] = '0';
		//是否已点赞
        $detail["is_good"] = '0';

        if($this->UserAuthCheckLogin()){
            $user = $this->AuthUserInfo;
            $user_record_model = D("user_record");

            $map = array("userid"=>$user["id"], "source"=>1, "objectid"=>$id);
            $record = $user_record_model->where($map)->select();
            foreach ($record as $key => $val) {
                if ($val['type'] == 1) {
                    $detail["is_collection"] = '1';
                } elseif ($val['type'] == 2) {
                    $detail["is_good"] = '1';
                }
            }
        }

		//更新浏览量
		$entity = array("browser_count"=>($detail["browser_count"] + 1));
		$model->where($map)->save($entity);

		return $detail;
	}
	
	//照护课堂评论列表
    public function comment(){
        $infoid = I("get.infoid", 0);
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;

        $model = D("information_comment");
        
        $order = "createdate desc";
        $map = array("status"=>1, "infoid"=>$infoid, "reply"=>0);
        $count = $model->where($map)->count();
        $totalpage = ceil($count/$row);
		$list = $model->where($map)->order($order)->limit($begin, $row)->select();
		
		$this->SetPaginationHeader($totalpage, $count, $page, $row);

        $user = array();
        $comment_record_model = D("comment_record");
        if($this->UserAuthCheckLogin()){
            $user = $this->AuthUserInfo;
        }
        foreach($list as $k=>$v){
            $map = array("status"=>1, "infoid"=>$infoid, "reply"=>1, "commentid"=>$v["id"]);
            $item = $model->where($map)->order($order)->select();
            foreach($item as $ik=>$iv){
                $iv["avatar"] = $this->DoUrlHandle($iv["avatar"]);
                $iv["reply_avatar"] = $this->DoUrlHandle($iv["reply_avatar"]);
                $iv["content"] = str_replace("/Public/Home/content/emoji/", $this->GetDomainSite()."/Public/Home/content/emoji/", $iv["content"]);
                $iv["createdate"] = time_tranx($iv["createdate"]);
                $item[$ik] = $iv;
            }
            $v["item"] = $item;

            $v["avatar"] = $this->DoUrlHandle($v["avatar"]);
            $v["reply_avatar"] = $this->DoUrlHandle($v["reply_avatar"]);
            $v["content"] = str_replace("/Public/Home/content/emoji/", $this->GetDomainSite()."/Public/Home/content/emoji/", $v["content"]);
            $v["is_good"] = 0;
            if($user['id']){
                $map = array("userid"=>$user["id"], "source"=>1, "type"=>1, "objectid"=>$v['id']);
                $record = $comment_record_model->where($map)->find();
                if ($record) {
                    $v["is_good"] = 1;
                }
            }
            
            $v["createdate"] = time_tranx($v["createdate"]);

            $list[$k] = $v;
		}

        return $list;
    }
	
}