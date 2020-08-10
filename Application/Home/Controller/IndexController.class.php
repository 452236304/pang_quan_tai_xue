<?php
namespace Home\Controller;

class IndexController extends BaseController
{
    protected $title = [];

    public function index()
    {
        $title = $this->getTitle();
        $show_notice['image'] = array('neq', '');
        $bannerData = D("banner")->field('id,image,cate')->where(array('type'=>1,'status'=>1))->where($show_notice)->order("cate asc,ordernum asc")->select();
        $banner = [];
        foreach ($bannerData as $k => $v)
        {
            $banner[$v['cate']][] = $v;
        }
        $cate = D('case_cate')->field('id,title')->where('status=1')->order('orderby ASC')->select();
        $model = D('case_content');
        $content = [];
        foreach ($cate as $k => $v)
        {
            $content[] = $model->field("id,title,content,img_url")->where("cate_id={$v['id']} AND status=1")->limit(4)->order('orderby ASC')->select();
        }

        $this->assign('cate', $cate);
        $this->assign('banner', $banner);
        $this->assign('content', $content);
        $this->assign('title', $this->title);
        $this->display();
    }

    /**
     * 获取表单设置
     */
    public function getTitle()
    {
        $model = D('form_set');
        $set = $model->field('id,title,type')->order('orderby ASC,add_time DESC')->select();
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