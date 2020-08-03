$(function(){
    $(".list-add").click(function () {
        var url = $(this).data('url');
        var title = $(this).data('title');
        console.log(url);
        layer.open({
            title: title,
            type: 2,
            area: ['1000px', '800px'],
            fixed: true, //不固定
            maxmin: true,
            content: url,
            end: function () {
                location.reload();
            }
        });
    });

    $(".list-update").click(function () {
        var url = $(this).data('url');
        var title = $(this).data('title');
        layer.open({
            title: title,
            type: 2,
            area: ['1000px', '800px'],
            fixed: true, //不固定
            maxmin: true,
            content: url,
            end: function () {
                location.reload();
            }
        });
    })

    $(".list-remove").click(function(){
        var obj = $(this);
        var url = $(this).data('url')
        layer.confirm('您确认删除该数据？', {
            btn: ['确定','取消'] //按钮
        }, function(){
            layer.closeAll('dialog');
            $.ajax({
                url: url,
                type: 'POST',
                success: function(data){
                    if( data.status ){
                        obj.parents('tr').remove();
                    }else{
                        layer.alert(data.info, {skin: 'layui-layer-lan'});
                    }
                }
            })
        });
    })
})