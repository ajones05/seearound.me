$(document).ready(function() {
        //profileMap(userLatitude, userLongitude);
        friendMapInitialize();
        setHeight('.eqlCH');
});

    var childrens = '';
    function listInfoOver(thisone) {
        childrens = $(thisone).children();
        $(childrens[0]).hide();
        $(childrens[1]).show();
    }
    
    function listInfoOut(thisone) {
        childrens = $(thisone).children();
        $(childrens[0]).show();
        $(childrens[1]).hide();
    }
    
    function deleteFriend(thisone, user) {
        if(user) {
            if(confirm("Are you sure to delete this friend?")) {
                $.ajax({
                    url : baseUrl+'contacts/delete',
                    type : 'post',
                    data : {user : user},
                    success : function(obj) {
                        if(obj) {
                            $(thisone).parent().parent().remove();
                            if($("#totalFriend")) {
                                $("#totalFriend").html(Number($("#totalFriend").html())-1);
                            }
                        }
                    }
                });
            }
        }
    }
    
    function moreFriends(page,type) {
        $("#sacrchWait2").toggle();
        $("#moreText").toggle();
        $.ajax({
            url : baseUrl+'contacts/friends-list',
            type : "post",
            data : {page:page},
            success : function(data) {
                data = $.parseJSON(data);
                if(data.more > 0) {
                    var pageHtml = '<div class="row show-grid">'+
                        '<div align="center" onclick=moreFriends('+data.page+',"OTHER") class="postClass_after">'+
                            '<lable id="moreText">More</lable><img id="sacrchWait2" class="searchWait" src="'+baseUrl+'www/images/wait.gif"/>'+
                        '</div>'+
                    '</div>';
                    $("#pagingDiv").html(pageHtml);
                } else {
                    $("#pagingDiv").toggle();
                }
                
                if(data && data.frlist) {
                    for(var x in data.frlist) { 
                        var imgSrc = baseUrl+"www/images/img-prof40x40.jpg";
                        if((data.frlist[x]).Profile_image) {
                            if(((data.frlist[x]).Profile_image).indexOf("://") > 0) {
                                imgSrc = (data.frlist[x]).Profile_image;
                            }else{
                                imgSrc = baseUrl+'uploads/'+(data.frlist[x]).Profile_image;
                            }
                        }
                        var frHtml = '<div class="invtFrndList">'+
                            '<ul class="invtFrndRow afterClr">'+
                                '<li class="img">'+
                                    '<a href="'+baseUrl+'home/profile/user/'+(data.frlist[x]).id+'">'+                                    
                                        '<img src="'+imgSrc+'"/>'+
                                    '</a>'+
                                '</li>'+
                                '<li class="name">'+(data.frlist[x]).Name+
                                    '<span class="loc">';
                                    if((data.frlist[x]).address) {
                                    frHtml += (data.frlist[x]).address;
                                    }
                                    frHtml += '</span>'+
                                '</li>'+
                                //'<li style="width: 25px; cursor: pointer;margin-top:23px;" class="btnCol"><img height="25" width="25" title="" onclick="clearErrors();user_id='+(data.frlist[x]).id+';" class="Message-Popup-Class" src="'+baseUrl+'www/images/envelope-icon.gif" /></li>'+
                                 '<li style="width: 26px; cursor: pointer; margin-top:15px;" class="btnCol"><img style="width: 26px; height: auto;" title="" onclick="clearErrors('+(data.frlist[x]).id+');user_id='+(data.frlist[x]).id+';" class="Message-Popup-Class" src="'+baseUrl+'www/images/envelope-icon.gif" /></li>'+
                                 '<input type="hidden" id="frndListUserId" name="frndListUserId" value="'+(data.frlist[x]).id+'"/>'+
                                '<li style="width: 15px; cursor: pointer; margin-left: 18px;" class="btnCol">&nbsp;&nbsp;<img title="" onclick="deleteFriend(this, '+(data.frlist[x]).fid+');" style="margin-top: 4px; width: 15px; height: auto;" src="'+baseUrl+'www/images/delete-icon.png" /></li>'+'<div class="clr"></div>'
                            '</ul>'+
                        '</div>'+
                        '<div class="clr"></div>';
                        $("#friendList").html( $("#friendList").html()+frHtml);
                        var dataToReturn = {name:(data.frlist[x]).Name,id:(data.frlist[x]).fid,news:(data.frlist[x]).address,userImage:imgSrc,user_id:(data.frlist[x]).fid};
                        commonMap.createMarker(new google.maps.LatLng((data.frlist[x]).latitude,(data.frlist[x]).longitude),UserMarker1,'MAIN',dataToReturn);
                        //setHeight('.eqlCH');
                        if($("#midColLayout").height()>714)
                            setThisHeight(Number($("#midColLayout").height()));
                        }
                    $(".Message-Popup-Class").colorbox({width:"40%",height:"45%", inline:true, href:"#Message-Popup"},function(){$('html, body').animate({ scrollTop: 0 }, 0);});
                    $(".Thanks-Popup-Class").colorbox({width:"40%",height:"20%", inline:true, href:"#Thanks-Popup"},function(){$('html, body').animate({ scrollTop: 0 }, 0);});
                    
                }
            
               
            }
        });
    }

	/*Auto completer code starts*/
    $(document).ready(function(){
        str = $("#search").attr("value","");
    });
    
    $(function () {
        $("#search").autocomplete({
            minLength: 1,
            source: function (request, response) {
            $("#sacrchWait").show();
            $.ajax({
                url: baseUrl+"contacts/search",
                dataType: "json",
                data: {            
                    search: request.term
                },
                success: function (data) {
                    $("#sacrchWait").hide();
                    response(data.success);
                }
                });
            },
            focus: function (event, ui) {
            if (ui.item.id < 0)
                return false;
            $("#search").val(ui.item.Name);
                return false;
            },
            select: function (event, ui) {
               //alert(11);
                if (ui.item.id < 0)
                    return false;        
                window.location.href = baseUrl+"home/profile/user/"+ui.item.id;    
                //return false;
            }
        })
        .data("autocomplete")._renderItem = function (ul, item) {
            var imgsrc ="";
            var address = "";
            if(item.Profile_image) {
                if(item.Profile_image.indexOf("://") > 0) {
                    imgsrc = item.Profile_image;
                }else {
                    imgsrc = baseUrl+"uploads/"+item.Profile_image;
                }
            } else {
                imgsrc = baseUrl+"www/images/img-prof40x40.jpg";
            } 
            if(item.address != null) {
                address = item.address;
            }
            return $("<li></li>")
            .data("item.autocomplete", item)
            .append("<a><div class='ui_image'><img height='50' width='50' src='"+imgsrc+"' /></div><div class='ui_main_text'><span class='ui_name'>"+ item.Name +"</span><br><span class='ui_address'>"+ address +"</span></div></a>")
            .appendTo(ul);
        };
    });
    
    
   function clearErrors(id) {
    globalFrienListId = id;
    reciever_userid = globalFrienListId;
    if($('#message')) {		

		$('#message').val("");

	} 

	if($('#subject')) {	

		$('#subject').val("");	

	}

	if($('#to')) {	

		$('#to').val("");	

	}

}
    