<?php
namespace Admin\Controller;
use Think\Controller;
class InvoiceController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function lists(){
        $order = "status DESC,add_time DESC";
        $param = $this->getMap();
        $map = array();

        if($param["keyword"]){
            $map["u.mobile"] = array("like","%".$param["keyword"]."%");
        }
        $invoicemodel = D("invoice");
        $count = $invoicemodel->count();
        $model = $invoicemodel->alias('i')->field("i.*,u.mobile")->join("LEFT JOIN sj_user AS u ON i.user_id=u.id");

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
        $model = D("invoice");
        $data = $model->alias('i')->where("i.id={$id}")->field("i.*,u.mobile")->join("LEFT JOIN sj_user AS u ON i.user_id=u.id")->find();

        // 发票的相关订单流水号
        $order['product_order'] = D('product_order')->where("invoice_id={$id}")->field('sn')->select();
        $order['service_order'] = D('service_order')->where("invoice_id={$id}")->field('sn')->select();

        if($doinfo == "modify")
        {
            $d = [];
            $d['status'] = I('post.status');
            $d['invoice_url'] = I('post.invoice_url');
            if($d['status'] == 1)
            {
                if(empty($d['invoice_url']))
                {
                    $this->error('审核成功，发票图片不为空',U('Invoice/modify', 'id='.$id));
                }
            }
            else
            {
                $d['invoice_url'] = '';
            }

            if($id > 0)
            {
                $d['status_time'] = time();
                $model->where("id=".$id)->save($d);
            }

            $url = U('Invoice/lists')."?".http_build_query($this->getMap());
            header('Location:'.$url);
            //$this->redirect($url);
        }

        $this->assign('data', $data);
        $this->assign('order', $order);
        $this->assign("map",$this->getMap());
        $this->display();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
        $model = D("service_project");
        $id = I("get.id");
        $model->delete($id);
        $this->redirect("ServiceProject/listad",$this->getMap());
    }

    /**
     * [statusAd]
     * @return [type] [description]
     */
    public function topad(){
        $id = I("get.id", 0);
        $d['top'] = I("get.top", 0);
        $model = D("service_project");
        if($id > 0){
            $model->where("id=".$id)->save($d);
        }else{
            $model->add($d);
        }
        $this->redirect("ServiceProject/listad",$this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("service_project");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("ServiceProject/listad",$this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("ServiceProject/listad",$this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }

    public function longlist(){
        $param = $this->getMap();

        $map = array("projectid"=>$param["projectid"]);
        $data = $this->pager("service_detail", "10", "id asc", $map, null);
        $this->assign($data);
        $this->assign("map", $param);
        $this->show();
    }
    public function longad(){
        $id = I("get.id", 0);
        $projectid = I("get.projectid", 0);
        $doinfo = I("get.doinfo");
        $model = D("service_detail");
        $data["info"] = $model->find($id);
        if($doinfo == "modify"){
            $d["title"] = I("post.title");
            $d["content"] = htmlspecialchars_decode(I("post.content"));
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $d['projectid'] = $projectid;
                $model->add($d);
            }
            $this->redirect("ServiceProject/longlist",$this->getMap());
        }
        $this->assign($data);
        $this->assign("map",$this->getMap());
        $this->display();
    }


    public function getMap(){
        $p = I("get.p");
        $keyword = I("get.keyword");
        if(empty($keyword)){
            $keyword = mb_convert_encoding($_GET["keyword"], "UTF-8", "GB2312");
        }
        $type = I('get.type');
        $recommend = I("get.recommend");
        $seckill = I("get.seckill");
        $top = I("get.top");
        $projectid = I("get.projectid");
        $map = array("p"=>$p, "keyword"=>$keyword,'recommend'=>$recommend,'seckill'=>$seckill,'top'=>$top, 'projectid'=>$projectid);
        return $map;
    }
}