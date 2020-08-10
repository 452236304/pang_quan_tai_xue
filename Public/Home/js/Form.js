window.onload = function () {
    var oflink = document.getElementById('OrLeft');
    var aDt = oflink.getElementsByClassName('OrLeli');
    var aUl = oflink.getElementsByClassName('option');
    var aH3 = oflink.getElementsByTagName('h6');
    for (var i = 0; i < aDt.length; i++) {
        aDt[i].index = i;
        aDt[i].onclick = function (ev) {
            var ev = ev || window.event;
            var This = this;
            for (var i = 0; i < aUl.length; i++) {
                aUl[i].style.display = 'none';
            }
            aUl[this.index].style.display = 'block';
            document.onclick = function () {
                aUl[This.index].style.display = 'none';
            };
            ev.cancelBubble = true;

        };
    }
    for (var i = 0; i < aUl.length; i++) {

        aUl[i].index = i;

        (function (ul) {
            var iLi = ul.getElementsByTagName('li');

            for (var i = 0; i < iLi.length; i++) {
                iLi[i].onmouseover = function () {
                    this.className = 'hover';
                };
                iLi[i].onmouseout = function () {
                    this.className = '';
                };
                iLi[i].onclick = function (ev) {
                    var ev = ev || window.event;
                    aH3[this.parentNode.index].innerHTML = this.innerHTML;
                    ev.cancelBubble = true;
                    this.parentNode.style.display = 'none';

                    var id = $(this).attr('data-id');
                    $(this).parents('ul').siblings('input').val(id);
                };
            }

        })(aUl[i]);
    }

}
$(function () {
    $(".fontline label").on("click", function () {
        $(".fot_act").removeClass("fot_act")
        $(this).addClass("fot_act");
    })
})
// $(".submit").click(function() {
// 	$(".win").show().css("display", "flex");
// });
$(".colseBox").click(function() {
	$(".win").hide().css("display", "none");
});