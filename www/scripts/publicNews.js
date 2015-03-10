globalVarForEmail = "";
var infoBubble = "";

function newsMap(lat, lng, data) {
    var profileImage = '';

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
