<?php
namespace CApi\Controller;
use Think\Controller;
class InformationHandleController extends BaseLoggedController {
    //收藏/点赞/分享
    public function interactive(){
		$user = $this->AuthUserInfo;

		$data = I("post.");
		
        $type = $data["type"];
        if(!in_array($type, [1,2,3])){ //1:收藏；2：点赞；3：分享
            E("请选择资讯的交互类型");
		}
		
        $infomodel = D("information");

		$infoid = $data["infoid"];
		$map = array("status"=>1, "id"=>$infoid);
        $info = $infomodel->where($map)->find();
        if(empty($info)){
            E("选择的资讯不存在");
        }

        $recordmodel = D("user_record");

        $data = array("status"=>0, "count"=>0);

        $map = array("userid"=>$user["id"], "type"=>$type, "objectid"=>$infoid);
        $check = $recordmodel->where($map)->find();
        if(empty($check)){
            $entity = array(
                "userid"=>$user["id"], "source"=>1, "type"=>$type,
                "objectid"=>$infoid, "createdate"=>date("Y-m-d H:i:s")
            );
            $recordmodel->add($entity);

            $entity = array();
            switch($type){
                case 1: $data["count"] = $entity["collection_count"] = $info["collection_count"] + 1; break;
                case 2: $data["count"] = $entity["good_count"] = $info["good_count"] + 1; break;
                case 3: $data["count"] = $entity["share_count"] = $info["share_count"] + 1; break;
            }
            $infomodel->where("id=".$infoid)->save($entity);

            $data["status"] = 1; //记录
        } else{
            if($type == 3){
                $data["count"] = $info["share_count"];
            } else{
            	$recordmodel->where($map)->delete();

                $entity = array();
                switch($type){
                    case 1: $data["count"] = $entity["collection_count"] = $info["collection_count"] - 1; break;
                    case 2: $data["count"] = $entity["good_count"] = $info["good_count"] - 1; break;
                }
                $infomodel->where("id=".$infoid)->save($entity);

                $data["status"] = 2; //取消
            }
        }
		if($type==1){
			//分享文章积分任务
			D('Point','Service')->append($user['id'],'share');
		}elseif($type==2){
			//点赞文章积分任务
			D('Point','Service')->append($user['id'],'like');
		}
		
        return $data;
    }
	
    //发表资讯评论
    public function comment(){
		$user = $this->AuthUserInfo;
		
        $data = I("post.");

        $content = trim($data["content"]);
        if(empty($content)){
            E("请输入评论的内容");
        }

        $infoid = $data["infoid"];
        if(empty($infoid)){
            E("请选择要评论的资讯");
        }

        $commentmodel = D("information_comment");

        $commentid = getDecimalValue($data["commentid"], 0);
        if($commentid){
            $comment = $commentmodel->find($commentid);
        }
        $entity = array(
            "status"=>1, "type"=>1, "infoid"=>$infoid,
            "content"=>htmlspecialchars_decode($content), "createdate"=>date("Y-m-d H:i:s")
        );

        if(empty($comment)){
            $entity["reply"] = 0;
            $entity["userid"] = $user["id"];
            $entity["nickname"] = $user["nickname"];
            $entity["avatar"] = $user["avatar"];
        } else{
            $entity["reply"] = 1;
            if($comment["reply"] == 0){
                $entity["commentid"] = $comment["id"];
            } else{
                $entity["commentid"] = $comment["commentid"];
            }
            
            $entity["userid"] = $user["id"];
            $entity["nickname"] = $user["nickname"];
            $entity["avatar"] = $user["avatar"];

            if($user["id"] == $comment["userid"]){
                $entity["reply_userid"] = $user["id"];
                $entity["reply_nickname"] = $user["nickname"];
                $entity["reply_avatar"] = $user["avatar"];
            } else{
                $entity["reply_userid"] = $comment["userid"];
                $entity["reply_nickname"] = $comment["nickname"];
                $entity["reply_avatar"] = $comment["avatar"];
            }
        }

        $entity["id"] = $commentmodel->add($entity);

        $entity["avatar"] = $this->DoUrlHandle($entity["avatar"]);
        $entity["reply_avatar"] = $this->DoUrlHandle($entity["reply_avatar"]);
        $entity["content"] = str_replace("/Public/Home/content/emoji/", $this->GetDomainSite()."/Public/Home/content/emoji/", $entity["content"]);
		
        //评论积分任务
        D('Point','Service')->append($user['id'],'comment');
		
        return $entity;
    }

    //评论点赞
    public function commentInteractive(){
        $user = $this->AuthUserInfo;

        $data = I("post.");

        $type = 1;

        $commentmodel = D("information_comment");

        $commentid = $data["commentid"];
        $map = array("status"=>1, "type"=>1, "id"=>$commentid);
        $info = $commentmodel->where($map)->find();
        if(empty($info)){
            E("选择的资讯评论不存在");
        }

        $recordmodel = D("comment_record");

        $data = array("status"=>0, "count"=>0);

        $map = array("userid"=>$user["id"], "source"=>1, "type"=>$type, "objectid"=>$commentid);
        $check = $recordmodel->where($map)->find();
        if(empty($check)){
            $entity = array(
                "userid"=>$user["id"], "source"=>1, "type"=>$type,
                "objectid"=>$commentid, "createdate"=>date("Y-m-d H:i:s")
            );
            $recordmodel->add($entity);

            $entity = array();
            switch($type){
                case 1: $data["count"] = $entity["good_count"] = $info["good_count"] + 1; break;
            }
            $commentmodel->where("id=".$commentid)->save($entity);

            $data["status"] = 1; //记录
        } else{
            $recordmodel->where($map)->delete();

            $entity = array();
            switch($type){
                case 1: $data["count"] = $entity["good_count"] = $info["good_count"] - 1; break;
            }
            $commentmodel->where("id=".$commentid)->save($entity);

            $data["status"] = 2; //取消

        }

        return $data;
    }

}