<?php
namespace Admin\Controller;
use Think\Controller;
class UserConsumeController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $userid = I('get.id', 0);
        $model = D("user_consume");
        $map = array('userid'=>$userid);
        $data = $model->where($map)->order("createdate desc")->select();
        $this->assign("data", $data);
        $this->show();
    }
}