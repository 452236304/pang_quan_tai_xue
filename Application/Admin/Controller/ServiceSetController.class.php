<?php
namespace Admin\Controller;
use Think\Controller;
class ServiceSetController extends BaseController
{
    public function index()
    {
        $info = D('service_set')->find();
        $info['upd_time'] = !empty($info) ? date('Y-m-d H:i:s', $info['upd_time']) : '';

        $this->assign("info", $info);
        $this->show();
    }

    public function save()
    {
        $data['online_call'] = I("post.online_call");
        $data['bg_url'] = I("post.bg_url");
        $data['title'] = I("post.title");
        $data['video_url'] = I("post.video_url");
        $data['status'] = I("post.status");

        $service_set_model = D('service_set');
        $id = $service_set_model->getField('id');
        if($id)
        {
            $res = $service_set_model->where(['id'=>$id])->save($data);
            if($res)
            {
                $service_set_model->where(['id'=>$id])->setField('upd_time', time());
            }
        }
        else
        {
            $data['upd_time'] = time();
            $service_set_model->add($data);
        }

        $this->redirect("ServiceSet/index");
    }
}