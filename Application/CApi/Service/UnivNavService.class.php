<?php
namespace CApi\Service;

class UnivNavService
{
    /**
     * Notes: 发现中部特殊导航
     * User: dede
     * Date: 2020/7/20
     * Time: 7:08 下午
     * @param $tag
     */
    public function nav($tag){
        switch ($tag){
            case 'older':
                return $this->older();
            case 'public_benefit':
                return $this->publicBenefit();
            case 'zhishi':
                return $this->repository();
            default:
                return [];
        }
    }

    /**
     * Notes: 养老通
     * User: dede
     * Date: 2020/7/20
     * Time: 7:09 下午
     * @return array
     */
    protected function older(){
        return [
            [
                'title' => '',
                'icon' => '',
                'url' => '',
            ],
            [
                'title' => '',
                'icon' => '',
                'url' => '',
            ]
        ];
    }

    /**
     * Notes: 公益活动
     * User: dede
     * Date: 2020/7/20
     * Time: 7:11 下午
     */
    protected function publicBenefit(){
        return [
            [
                'title' => '椿声计划',
                'url' => '/pages/Find/gyplay/gyplay',
                'icon' => DoUrlHandle('/Public/Api/university/gy1.png'),
            ],
            [
                'title' => '时间银行',
                'url' => '/pages/Find/gyTime/gyTime',
                'icon' => DoUrlHandle('/Public/Api/university/gy2.png'),
            ],
            [
                'title' => '公益活动',
                'url' => '/pages/Find/gyactivity/gyactivity',
                'icon' => DoUrlHandle('/Public/Api/university/img11.png'),
            ]
        ];
    }

    /**
     * Notes: 知识库
     * User: dede
     * Date: 2020/7/20
     * Time: 7:13 下午
     * @return array
     */
    protected function repository(){
        return [
            [
                'title' => '知识库',
                'icon' => DoUrlHandle('/Public/Api/university/list1.png'),
                'url' => '/pages/Find/zskClassify/zskClassify',
            ],
            [
                'title' => '专题讲座',
                'icon' => DoUrlHandle('/Public/Api/university/list2.png'),
                'url' => '/pages/Find/zskSubject/zskSubject',
            ],
            [
                'title' => '学习教程',
                'icon' => DoUrlHandle('/Public/Api/university/list3.png'),
                'url' => '/pages/Find/zskStudy/zskStudy',
            ]
        ];
    }
}