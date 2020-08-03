<?php
namespace CApi\Controller;

class TopicController extends BaseController
{
    /**
     * Notes: 话题排行榜
     * User: dede
     * Date: 2020/2/26
     * Time: 10:33 下午
     */
    public function top(){
        $page = I('page', 1, 'intval');
        $limit = I('limit', 1, 'intval');
        $topic = D('Topic', 'Service')->top($page, $limit);
        $data = [
            'topic' => $topic,
        ];
        return $data;
    }

    public function search(){
        $keyword = I('keyword');
        if( !$keyword ){
            E('请输入要搜索的关键字');
        }
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $topic = D('Topic', 'Service')->search($keyword, $offset, $row);
        $data = [
            'topic' => $topic,
        ];
        return $data;
    }
}