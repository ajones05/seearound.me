globalVarForEmail = "";
var infoBubble = "";

function newsMap(lat, lng, data) {
    var profileImage = '';
    console.log(data);
     if(data){
        profileImage = data.image;
     } else {
        profileImage = '/www/images/img-prof40x40.jpg';
     }
     commonMap = new MainMap ({
         mapElement : document.getElementById('map_canvas'),
         centerPoint : new google.maps.LatLng(lat,lng),
         icon:MainMarker1,
         centerData:data,
         profileImage:profileImage,
         markerType:'nonDragable',
         isMapDragable:'dragable',
         showMapElement:false
     });
/*	var myOptions = {

		center: new google.maps.LatLng(lat, lng),

		zoom: 13,

		mapTypeId: google.maps.MapTypeId.ROADMAP

	};

	var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

	var position = new google.maps.LatLng(lat, lng);

	var marker = new google.maps.Marker({

		map:map,

		draggable:false,

		position: position,

		icon: (data.id == user_id)?(baseUrl+'www/images/icons/icon_1.png'):(baseUrl+'www/images/icons/icon_3.png'),

		height:24,

		width:36

	});

	google.maps.event.addListener(marker, 'click', function(){ 

		newsinfo(map, marker, lat, lng, data);

	});

	var newsCircle = new AreaCircle({

			radious:1.5,

			center:position

	});

	newsCircle.polyArea.setMap(map);//newsCircle.makeCircle();*/

}

function newsinfo(map, marker, lat, lng, data) {

	if(infoBubble != ""){

		infoBubble.close();

		infoBubble = "";

	}

	var html = '<div id="content0_1" class="infoBubbleClass" style="min-width:200px;max-width:250px;min-height:50px;word-wrap:break-word;">'+

			'<div style="float:left;width:20%">'+

				'<img class="smallImage" src="'+data.image+'" />'+

			'</div>'+

			'<div class="markerNews" align="left" style="float:right;width:79%">'+

				'<span id="userName0_1" class="userName">'+data.name+'</span>'+

				'<span id="news0_1" style="margin-top:5px;display:block;word-wrap: break-word;">'+data.address+'</span>'+

			'</div>'+

		'</div>';

	infoBubble = new InfoBubble({

	  map: map,

	  content: html,

	  position: new google.maps.LatLng(lat, lng),

	  shadowStyle: 3,

	  padding: 10,

	  borderRadius: 4,

	  arrowSize: 25,

	  borderWidth: 1,

	  borderColor: '#2c2c2c',

	  hideCloseButton: false,

	  arrowPosition: 30,

	  minWidth: 200

	});

	infoBubble.open(map, marker);

   

}



function viewAllComments(nwid, thisone) {

	$(thisone).hide();

	$.ajax({

		url:baseUrl+'info/get-Total-Comments',

		type:'post',

		data:{news_id:nwid},

		success : function(data) {

			data = $.parseJSON(data);

			$(".cmntRow").html(data.comments);

			setThisHeight(Number($("#midColLayout").height()));

			$(".login-popup-class").colorbox({width:"26%",height:"32%", inline:true, href:"#login-id"}, function() {

			   $('html, body').animate({ scrollTop: 0 }, 0);     

			});

			

		}

	});

}



function openImage_old(imageName, w, h) { 

	if(w==0 && h==0) {

		var newImg = new Image();										

		newImg.src = baseUrl+'newsimages/'+imageName;

		w = newImg.width;

		h = newImg.height;

	}

	$("#imagePopup").attr('src', '');

    $('html, body').animate({ scrollTop: 0 }, 0);

        aaa =w;

		if(Number(w) < 400) {

			imageWidth = Number(w)+40;

		} else {

			imageWidth = w;

		}

		if(Number(h) < 400) {

			imageHeight = Number(h)+40;

		} else {

			imageHeight = h;  

		}

    $("#imagePopup").attr('src', baseUrl+"newsimages/"+imageName);

}



function openImage(imageName, w, h) { 
 	if(w==0 && h==0) {

		var newImg = new Image();										

		newImg.src = baseUrl+'newsimages/'+imageName;

		w = newImg.width;

		h = newImg.height;

	}

	 $("#imagePopup").attr('src', '');
         var screenTop = $(document).scrollTop();
         $("#colorbox").css("margin-top",screenTop);
         $('html, body').animate({ scrollTop: screenTop }, 0);

          aaa =w;

		if(Number(w) < 400) {

			imageWidth = Number(w)+40;

		} else {

			imageWidth = w;

		}

		if(Number(h) < 400) {

			imageHeight = Number(h)+40;

		} else {

			imageHeight = h;  

		}

        $("#imagePopup").attr('src', baseUrl+"newsimages/"+imageName);
        
 }
 

var reurl = window.location.href;

 function fbshare(scrpId,imageUrl,messageId,reurlPost) { 
    var newsVal='';
    newsVal+='<b>'+messageId+'</b><br>';
    FB.ui({ 
            method: 'feed',

            name: 'SeeAround.me',

            link: reurlPost,

            description: newsVal,

            picture: imageUrl
    });
    
}



function twitterShare(thisone) {

   var url = "https://twitter.com/intent/tweet?original_referer="+reurl+"&source=tweetbutton&text="+$('#newsDataDiv').html().substring(0, 120)+"&url=http://"+http_host+"/info/news/nwid/"+newsId; 

   window.open(url,"twiter Share", "width=500,height=350,top=200px,left=450px");

}



function publicMessage() {

  	var emialPattern = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    var message = $('#message').val();
	var to = $('#to').val();
	if(emialPattern.test(to)) {

		$.ajax({

			type: "POST",

			url: baseUrl+"info/public-message",

			data: {'to':to, 'message':message},

			success: function (msg) { 

				msg = JSON.parse(msg);

				if(msg && msg.errors) {

					if(msg && msg.errors['to'] != "") {

						$('#to_error').html(msg.errors['to']);

					}

					if(msg && msg.errors['message'] != "") {

						$('#message_error').html(msg.errors['message']);

					}

				}else if(msg && msg.success) {

					$('#message').val('');

					$('#subject').val('');

					$("#thanksButton").trigger('click');                

				}			

			}     

		});

	} else {

		$('#to_error').html('Please type a valid email address');

	}

}


function publicMessageEmail(scrpId,imageUrl,messageId,reurlPost){
    var emialPattern = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
   // var message = $('#message').val();
	var message = globalVarForEmail;
    var to = $('#to').val();
	if(emialPattern.test(to)) {
		$.ajax({
			type: "POST",
			url: baseUrl+"info/public-message-email",
			data: {'to':to, 'message':message},
			success: function (msg) { 
				msg = JSON.parse(msg);
				if(msg && msg.errors) {
					if(msg && msg.errors['to'] != "") {
						$('#to_error').html(msg.errors['to']);
					}
					if(msg && msg.errors['message'] != "") {
						$('#message_error').html(msg.errors['message']);
					}
				}else if(msg && msg.success) {
					$('#message').val('');
					$('#subject').val('');
					$("#thanksButton").trigger('click');                
				}			
			}     
		});
	} else {

		$('#to_error').html('Please type a valid email address');

	}

 }

 function clearErrors(id) {
    //alert('here');
   globalVarForEmail = http_host + '/info/news/nwid/'+id+'';
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