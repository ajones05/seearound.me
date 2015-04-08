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
	if (isLogin){
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
	} else {
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

		$('#loginForm').validate({
			errorPlacement: function(error, element){
			},
			rules: {
				email: {
					required: true,
					email: true
				},
				password: {
					required: true
				}
			},
			submitHandler: function(form){
				$('#loginError').html('');

				$.ajax({
					url: $(form).attr('action'),
					data: $(form).serialize(),
					type: 'POST',
					dataType: 'json'
				}).done(function(response){
					if (response && response.status){
						if (!response.active){
							window.location.href = response.redirect;
							return false;
						}

						if (returnUrl != ''){
							window.location.href = returnUrl;
							return false;
						}

						window.location.href = response.redirect;
					} else if (response){
						$('#loginError').html(response.error.message);
					} else {
						alert(ERROR_MESSAGE);
					}
				}).fail(function(jqXHR, textStatus){
					alert(textStatus);
				});
			}
		});
	}
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
                            '<input class="curPnt" type="button" value="Accept" onclick="friendRequest(this,'+(obj.data[x]).sender_id+',\'confirm\');" />&nbsp;&nbsp;'+
                            '<input class="curPnt" type="button" value="Deny" onclick="friendRequest(this,'+(obj.data[x]).sender_id+',\'reject\');" />&nbsp;&nbsp;'+
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

function friendRequest(thisone, id, action){
	$(thisone).siblings('img').toggle();

	$.ajax({
		url: baseUrl + 'contacts/friend',
		type: 'POST',
		data: {
			user: id,
			action: action,
			total: true
		},
		dataType: 'json'
	}).done(function(response){
		if (response && response.status){
			$('#noteTotal').html(response.total);
			$(thisone).parent().parent().remove();

			currentRequests--;

			if (response.total == 0){
				$('#noteTotal').hide();
			}

			if (currentRequests <= 0){
				showFriendRequest();
			}
		} else {
			alert(ERROR_MESSAGE);
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
	});
}
