<?php
namespace Home\Controller;
use Think\Controller;

class CaseController extends Controller
{
    public function index()
    {
        $data = D('case_cate')->field('id,title')->where('status=1')->order('orderby ASC')->select();
        $model = D('case_content');
        foreach ($data as $k => $v)
        {
            $data[$k]['content'] = $model->field("id,title,content,img_url")->where("cate_id={$v['id']} AND status=1")->order('orderby ASC')->select();
        }
        var_dump($data);
        $this->display();
    }

}