<?php
namespace CApi\Controller;
use Think\Controller;
class ArticleHandleController extends BaseLoggedController {

    // 分享/收藏/点赞
    public function interactive(){
        $type = I("post.type");
        if(empty($type) || !in_array($type, [1,2,3])){ //1:分享；2：收藏；3：点赞
            E("请选择文章的交互类型");
		}
		
        $article_model = D("mould_article");
		
        $articleid = I("post.articleid", 0);
        $article = $article_model->find($articleid);
        if(empty($article)){
            E("选择的文章不存在");
        }
		
        $userid = $this->AuthUserInfo["id"];

        $record_model = D("user_record");

        $data = array("status"=>0, "count"=>0);
        
        $map = array("userid"=>$userid, "type"=>$type, "articleid"=>$articleid);
        $check = $record_model->where($map)->find();
        if(empty($check)){
            $entity = array(
                "userid"=>$userid, "source"=>1, "type"=>$type,
                "articleid"=>$articleid, "createdate"=>date("Y-m-d H:i:s")
            );
            $record_model->add($entity);

            $entity = array();
            switch($type){
                case 1: $data["count"] = $entity["share_count"] = $article["share_count"] + 1; break;
                case 2: $data["count"] = $entity["collection_count"] = $article["collection_count"] + 1; break;
                case 3: $data["count"] = $entity["good_count"] = $article["good_count"] + 1; break;
            }
            $article_model->where("id=".$articleid)->save($entity);

            $data["status"] = 1; //增加
        } else{
            if($type == 1){
                $data["count"] = $article["share_count"];
            } else{
            	$record_model->where($map)->delete();

                $entity = array();
                switch($type){
                    case 2: $data["count"] = $entity["collection_count"] = $article["collection_count"] - 1; break;
                    case 3: $data["count"] = $entity["good_count"] = $article["good_count"] - 1; break;
                }
                $article_model->where("id=".$articleid)->save($entity);

                $data["status"] = 2;
            }
        }
		$user = D('user')->where(array('id'=>$userid))->find();
		if($type==1){
			//分享文章积分任务
			D('Point','Service')->append($user['id'],'share');
		}elseif($type==3){
			//点赞文章积分任务
			D('Point','Service')->append($user['id'],'like');
		}
		
		
        return $data;
    }
	
    // 发表评论
    public function comment(){
        $data = I("post.");

        $content = trim($data["content"]);
        if(empty($content)){
            E("请输入评论的内容");
        }

        $articleid = $data["articleid"];
        if(empty($articleid)){
            E("请选择要评论的文章");
        }

        $user = $this->AuthUserInfo;

        $comment_model = D("article_comment");

        $commentid = getDecimalValue($data["commentid"], 0);
        if($commentid){
            $comment = $comment_model->find($commentid);
        }
        $entity = array(
            "status"=>1, "type"=>1, "articleid"=>$articleid,
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

        $entity["id"] = $comment_model->add($entity);

        $entity["avatar"] = $this->DoUrlHandle($entity["avatar"]);
        $entity["reply_avatar"] = $this->DoUrlHandle($entity["reply_avatar"]);
        $entity["content"] = str_replace("/Public/Home/content/emoji/", $this->GetDomainSite()."/Public/Home/content/emoji/", $entity["content"]);
        D('mould_article')->where('id='.$articleid)->setInc('comment_count',1);
		
		//评论积分任务
		D('Point','Service')->append($user['id'],'comment');
		
        return $entity;
    }
}