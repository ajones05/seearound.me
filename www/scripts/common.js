/* facebook */
window.fbAsyncInit = function(){
	FB.init({
		appId: facebook_appId,
		xfbml: true,
		cookie: true,
		version: 'v2.1'
	});
};

(function(d, s, id){
	var js, fjs = d.getElementsByTagName(s)[0];
	if (d.getElementById(id)) {return;}
	js = d.createElement(s); js.id = id;
	js.src = "//connect.facebook.net/en_US/sdk.js";
	fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

$(function(){
	$('#logoutLi a').click(function(e){
		var url = $(this).attr('href');

		e.preventDefault();

		FB.getLoginStatus(function(response){
			if (response.authResponse){
				FB.logout(function(){
					window.location.href = url;
				});
			} else {
				window.location.href = url;
			}
		}); 
	});

	$('#myProfLink').click(function(e){
		e.preventDefault();
		e.stopPropagation();

		$('#ddMyProf').show();

		$('body').on('click.menu', function(){
			$('#ddMyProf').hide();
			$(this).off('click.menu');
		});
    });

	$("#ddMyConn").click(function (e){
		e.preventDefault();
		e.stopPropagation();

		$('#ddMyConnList').show();

		$('body').on('click.menu', function(){
			$('#ddMyConnList').hide();
			$(this).off('click.menu');
		});
	});

	$('#facebookLogin').click(function(e){
		e.preventDefault();

		var url = $(this).attr('href');
		
		FB.login(function(response){
			if (response.authResponse){
				FB.api('/me', function(response){
					if (response.email){
						window.location.href = url;
					} else {
						alert('Email not activated');
					}
				});
			}
		},{scope: 'email'});
	});
});

function showFriendRequest(thisone){
	var html = '<p class="bgTop"></p>'+
		'<img style=" margin: 7px 0 7px 112px;" src="'+baseUrl+'www/images/wait.gif" /><br>'+
        '<p class="pendReq2"></p>';

	$("#ddMyConnList").html(html);

    $.ajax({
		url: baseUrl + 'contacts/requests',
		type: "POST",
		success: function(obj){
                if(obj) {
                    obj = $.parseJSON(obj); 
                    if(obj.total > 0) { 
                        html = '';
                        html += '<p class="bgTop"></p>';
                        currentRequests = 0;
                        for(var x in obj.data) { 
                            currentRequests++;
                            var imgsrc = baseUrl+'www/images/img-prof40x40.jpg';
                            if((obj.data[x]).Profile_image) {
                                if((obj.data[x]).Profile_image != 'null' || (obj.data[x]).Profile_image != '') { 
                                    if(((obj.data[x]).Profile_image).indexOf('://') > 0) {
                                        imgsrc = (obj.data[x]).Profile_image;
                                    }else {
                                        imgsrc = baseUrl+'uploads/'+(obj.data[x]).Profile_image;
                                    }
                                }
                            }

                            html += '<ul class="connList afterClr">'+
                            '<li class="thumb">'+
                            '<img src="'+imgsrc+'" width="40" height="40" />'+
                            '</li>'+
                            '<li class="name">'+
                            (obj.data[x]).Name +
                            '</li>'+
                            '<li class="adrs">';

                            if((obj.data[x]).address) {
                                html += (obj.data[x]).address.breakAt(32);
                            } else {
                                html += '<br><br>';
                            }

                            html +='</li>'+
                            '<li class="btnSet">'+
                            '<input class="curPnt" type="button" value="Accept" onclick="allow(this,'+(obj.data[x]).fid+');" />&nbsp;&nbsp;'+
                            '<input class="curPnt" type="button" value="Deny" onclick="deny(this,'+(obj.data[x]).fid+');" />&nbsp;&nbsp;'+
                            '<img class="imgAlowDeny" height="20" width="50" src="'+baseUrl+'www/images/loader.gif" />'+
                            '</li>'+
                            '</ul><div class="clear"></div>';
                        }
					if (obj.total >= 5){
						html += '<p class="pendReq">'+
							'<a href="'+baseUrl+'contacts/all-requests">View all pending requests</a>'+
							'</p>';
					} else {
						html+='<p class="pendReq2"></p>';
					}
					$("#noteTotal").html(obj.total); 
					$("#ddMyConnList").html(html);
				} else {
					html = '<p class="pendReq">No new friend request found.</p>';								
					$("#ddMyConnList").html(html);
				}
				$("#ddMyConnList").append('<a class="friend" href="'+baseUrl+'contacts/friends-list">Go to my friends </a>');
			} else {
				$(thisone).parent().siblings('div').html("");
			}
		},
		error : function(){
			$(thisone).parent().siblings('div').html();
		}
	});
}
