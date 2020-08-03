<?php
namespace Admin\Controller;
use Think\Controller;
class ProductOrderProductController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("product_order_product");
        $orderid = I('get.orderid', 0);
        $map = array('orderid'=>$orderid);
        $data = $model->where($map)->select();
        $this->assign("data", $data);
        $this->show();
    }

    /**
     * 回复评论
     * [modifyad]
     * @return [type] [description]
     */
    public function comment(){
        $id = I("get.id", 0);
        $model = D("product_comment");
        $doinfo = I("get.doinfo");
        $data = $model->where('id=' . $id)->find();
        if (empty($data)) {
            alert_close('评论不存在');
        }
        if($doinfo == "modify"){
            $d["platform_reply"] = I("post.platform_reply");

            if (empty($d["platform_time"])) {
                $d["platform_time"] = date("Y-m-d H:i");
            }
            $d["adminid"] = $_SESSION['manID'];
            if($id > 0){
                $model->where("id=".$id)->save($d);
                alert_back('保存成功');
            }
        }
        $this->assign("info", $data);
        $this->show();
    }
}