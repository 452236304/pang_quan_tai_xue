<?php
namespace Home\Controller;
use Think\Controller;

class FormController extends Controller
{
    protected $title = [];
    public function __construct()
    {
        parent::__construct();
        $this->getTitle();
    }

    public function index()
    {
        $this->assign('title', $this->title);
        $this->display();
    }

    public function submit()
    {
        $data = I('post.');
        $title = $this->title;
        $data['title1'] = $title['title1'][$data['title1']];
        $data['title2'] = $title['title2'][$data['title2']];
        $data['title3'] = $title['title3'][$data['title3']];
        $data['title4'] = $title['title4'][$data['title4']];
        $data['title5'] = $title['title5'][$data['title5']];
        $data['forcase_price'] = bcmul(floatval($data['word_num']), 0.8, 2);
        $data['end_time'] = strtotime($data['end_time']);
        $data['add_time'] = time();

        $res = D('form_submit')->add($data);
        if(!$res)
        {
            $this->error('申请失败');
        }

        $this->success('申请成功');
    }

    /**
     * 获取表单设置
     */
    public function getTitle()
    {
        $model = D('form_set');
        $set = $model->field('id,title,type')->order('orderby ASC')->select();
        $title = [];
        foreach ($set as $k => $v)
        {
            switch ($v['type'])
            {
                case 1:// 1订单类型
                    $title['title1'][$v['id']] = $v['title'];
                    break;
                case 2:// 2科目类型
                    $title['title2'][$v['id']] = $v['title'];
                    break;
                case 3:// 3学历背景
                    $title['title3'][$v['id']] = $v['title'];
                    break;
                case 4:// 4引用数
                    $title['title4'][$v['id']] = $v['title'];
                    break;
                case 5:// 5写作格式
                    $title['title5'][$v['id']] = $v['title'];
                    break;
            }
        }
        $this->title = $title;
    }
}