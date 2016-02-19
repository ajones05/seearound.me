var preurl ="http://graph.facebook.com/";
var posturl ="/picture?type=square";
    var accessToken;
    function getFbFriends() {        
        //var profilePicsDiv = document.getElementById('profile_pics');
        FB.getLoginStatus(function(response) {
            if (response.status != 'connected') {
                    FB.login(function(response) {
                        FB.api('/me', function (response) {
                        var nwId = response.id;
                        var name = response.name;
                        var email = response.email;
                        var gender = response.gender;
                        var dob = response.birthday;

                        if(nwId != undefined) { 
                            document.body.style.cursor = 'wait';
                            $.post(baseUrl+'index/fb-auth/', {'id':nwId,'name':name,'email': email,'picture':preurl+nwId+posturl,'gender':gender,'dob':dob}, getFbFriends, "text");
                        }			
                    });
                }, {scope: 'email,user_likes,user_birthday'});
            } else {
                $("#inviteMain1").html('<div class="fbFrndsMainWeit"><img src="'+baseUrl+'www/images/loader.gif"></div>');
                document.body.style.cursor = 'default';
                accessToken = FB.getAuthResponse()['accessToken'];

                FB.api('/me/friends', function(response) {
                    if(response.data) {
                        $("#inviteMain1").toggle();
                        $("#inviteMain2").toggle();
                        $.each(response.data,function(index,friend) {
                            $.ajax({
                                type: "POST",
                                url : baseUrl+"contacts/checkfbstatus",
                                data : {network_id : friend.id},
								dataType: 'json',
                                success : function(data) { 
                                    if(data.type == "blank") {
                                        var html = '<div id="'+friend.id+'" class="invtFrndList">'+
                                            '<ul class="invtFrndRow">'+
                                                '<li class="img"><img src="'+preurl+friend.id+posturl+'" width="40" height="40" /></li>'+
                                                '<li class="name">'+friend.name+'</li>'+
                                                '<li class="btnCol" id="invite_'+friend.id+'" onclick="inviteFbFriend('+friend.id+',\''+friend.name+'\');"><a href="javascript:void(0);" id="stauts_'+friend.id+'">&nbsp;&nbsp;Invite</a></li>'+
                                            '</ul>'+
                                        '</div><div class="clr"></div>';
                                        $("#invite_div").html($("#invite_div").html()+html);
                                    }else { 
                                        if(data.type == "facebook") {
                                            var html = '<div id="'+friend.id+'" class="invtFrndList">'+
                                                '<ul class="invtFrndRow">'+
                                                    '<li class="img"><img src="'+preurl+friend.id+posturl+'" width="40" height="40" /></li>'+
                                                    '<li class="name">'+friend.name+'</li>'+
                                                    '<li class="btnCol" id="invite_'+friend.id+'"><a href="javascript:void(0);" id="stauts_'+friend.id+'">Pending</a></li>'+
                                                '</ul>'+
                                            '</div><div class="clr"></div>';
                                            $("#invite_div").html($("#invite_div").html()+html);
                                        }else if(data.type == "herespy") {
                                                var alrConn = Number($("#alrConn").html());
                                                $("#alrConn").html(alrConn+1);
                                                $("#alrConn").parent().parent().show();
                                        }else if(data.type == "follow") {
                                            var html = '<div id="'+friend.id+'" class="invtFrndList">'+
                                                '<ul class="invtFrndRow">'+
                                                    '<li class="img"><img src="'+preurl+friend.id+posturl+'" width="40" height="40" /></li>';
                                                    if((data.address).address) {
                                                        html += '<li class="name">'+friend.name+'<span class="loc">'+(data.address).address+'</span></li>';
                                                    } else {
                                                        html += '<li class="name">'+friend.name+'<span class="loc"></span></li>';
                                                    } 
                                                    html += '<li class="btnCol" id="invite_'+friend.id+'" onclick="followFbFriends('+friend.id+');"><a href="javascript:void(0);" id="stauts_'+friend.id+'">Connect</a></li>'+
                                                '</ul>'+
                                            '</div><div class="clr"></div>';
                                            $("#connect_div").html($("#connect_div").html()+html); 
                                        }
                                    }

                                    if($("#midColLayout").height()>714)
                                        setThisHeight(Number($("#midColLayout").height()));
                                }
                            });
                        });
                    }else {
                        alert("Error!");
                        window.location.reload();
                    }
                });
            }
        });
    }
    
function inviteFbFriend(friend, name){
	$.ajax({
		url: baseUrl + 'contacts/invite',
		data: {network_id: friend},
		type: 'POST',
		dataType: 'json',
	}).done(function(response){
		if (response && response.status){
			sendFbMessage(friend);
		} else {
			alert(response ? response.error.message : ERROR_MESSAGE);
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
	});
}

function sendFbMessage(networkId){
	FB.ui({
		method: "send",
		name: "Herespy",
		link: baseUrl + "index/send-invitation/regType/facebook/q",
		to: networkId
	}, function(response){
		if (response != null){
			$("#invite_" + networkId).html('<a href="javascript:void(0);" id="stauts_' + networkId + '">Pending</a>');
			$("#invite_" + networkId).removeAttr('onclick');
			alert("Message has been sent successful.");
		} else {
			alert("Sorry! message can not be send");
		}
	});
}

function followFbFriends(friend){
	$.ajax({
		url: baseUrl + 'contacts/follow',
		data: {network_id: friend},
		type: 'POST',
		dataType: 'json',
	}).done(function(response){
		if (response && response.status){
			sendFbMessage(friend);
		} else {
			alert(response ? response.error.message : ERROR_MESSAGE);
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
	});
}
