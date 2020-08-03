<?php
namespace Admin\Controller;
use Think\Controller;
class CaseContentController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function index(){
        $order = "orderby ASC";
        $param = $this->getMap();
        $map = array();

        if($param["keyword"]){
            $map["c.title"] = array("like","%".$param["keyword"]."%");
        }
        $case_contentmodel = D("case_content");
        $count = $case_contentmodel->count();
        $model = $case_contentmodel->alias('c')->field('c.*,cc.title as cate_title')->join('LEFT JOIN sj_case_cate as cc ON c.cate_id=cc.id');

        $data = $this->pager(array("mo"=>$model, "count"=>$count), "10", $order, $map, $param);

        $this->assign($data);
        $this->assign("map",$this->getMap());
        $this->display();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modify(){
        $id = I("get.id", 0);
        $doinfo = I("get.doinfo");
        $model = D("case_content");
        if($id > 0)
        {
            $info = $model->find($id);
        }
        else
        {
            $info['status'] = 1;
        }
        $cate = D('case_cate')->where('status=1')->select();

        if($doinfo == "modify")
        {
            $d = [];
            $d['title'] = I('post.title');
            $d['img_url'] = I('post.img_url');
            $d['content'] = I('post.content');
            $d['content_detail'] = I('post.content_detail');
            $d['cate_id'] = I('post.cate_id');
            $d['orderby'] = I('post.orderby');
            $d['status'] = I('post.status');
            $d['add_time'] = time();

            if(empty($d['title'])) $this->error('标题不为空');
            if(empty($d['img_url'])) $this->error('封面图不为空');
            if(empty($d['content'])) $this->error('概述不为空');
            if(empty($d['cate_id']) || $d['cate_id'] == 0) $this->error('请选择分类');

            if($id > 0)
            {
                $model->where("id=".$id)->save($d);
            }
            else
            {
                $model->add($d);
            }

            $url = U('CaseContent/index')."?".http_build_query($this->getMap());
            header('Location:'.$url);
            //$this->redirect($url);
        }

        $this->assign('info', $info);
        $this->assign('cate', $cate);
        $this->assign("map",$this->getMap());
        $this->display();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function del(){
        $model = D("case_content");
        $id = I("get.id");
        $model->delete($id);
        $this->redirect("CaseContent/index",$this->getMap());
    }

    public function getMap(){
        $p = I("get.p");
        $keyword = I("get.keyword");
        if(empty($keyword)){
            $keyword = mb_convert_encoding($_GET["keyword"], "UTF-8", "GB2312");
        }

        $map = array("p"=>$p, "keyword"=>$keyword);
        return $map;
    }
}