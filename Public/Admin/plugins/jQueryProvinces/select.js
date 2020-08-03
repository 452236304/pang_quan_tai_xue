var province=$(".province"),city=$(".city"),town=$(".town");
var provinceText,cityText,townText,cityItem;
province.each(function(z,item){
    var lprovince=$(item),
    lcity = $(item).next(),
    ltown = $(item).next().next();
    var cityList=[],areaList=[];
    for(var i=0;i<provinceList.length;i++){
        
        if(provinceList[i].name==lprovince.val()){
            provinceText=provinceList[i].name;
            cityList=provinceList[i].cityList;
            cityItem=i;
            for(var j=0;j<cityList.length;j++){
                if(cityList[j].name==lcity.val()){
                    cityText=cityList[j].name;
                    areaList=cityList[j].areaList;
                    for(var k=0;k<areaList.length;k++){
						if(areaList[k]!=ltown.val()){
							addEle(ltown,areaList[k]);
						}
                    }
                }else{
					addEle(lcity,cityList[j].name);
				}
            }
        }else{
			addEle(lprovince,provinceList[i].name);
		}
    }
})

function addEle(ele,value){
    var optionStr="";
    optionStr="<option value="+value+">"+value+"</option>";
    ele.append(optionStr);
}
function removeEle(ele){
    ele.find("option").remove();
    var optionStar="<option value="+"请选择"+">"+"请选择"+"</option>";
    ele.append(optionStar);
}

province.on("change",function(){
    provinceText=$(this).val();
    $.each(provinceList,function(i,item){
        if(provinceText == item.name){
            cityItem=i;
            return cityItem
        }
    });
    var city = $(this).siblings(".city");
    var town = $(this).siblings(".town");
    removeEle(city);
    removeEle(town);
    $.each(provinceList[cityItem].cityList,function(i,item){
        addEle(city,item.name)
    })
});
city.on("change",function(){
    cityText=$(this).val();
    var town = $(this).siblings(".town");
    var provinceText = $(this).siblings(".province").val();
    removeEle(town);
    $.each(provinceList,function(i,item){
        if(provinceText == item.name){
            cityItem=i;
            return cityItem
        }
    });
    $.each(provinceList[cityItem].cityList,function(i,item){
        if(cityText == item.name){
            for(var n=0;n<item.areaList.length;n++){
                addEle(town,item.areaList[n])
            }
        }
    });
});