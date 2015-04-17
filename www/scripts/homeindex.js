var changePostLocationMap, changePostLocationMarker;
var imageHeight = '';
var imageWidth = '';

/*
* Function to open the post image in colorbox
* @pram {imageName : Name of the image,w : width of image,h : height of image}
*/    
function openImage(imageName, w, h) {
  
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
    self.focus();
}

/*
* Function to initialize the map and sub map popup
*/  
$(function(){
    initialize();
    $(".Map-Popup-Class1").colorbox({width:"70%",height:"", inline:true, href:"#Map-Popup-id"},function(){
          $('html, body').animate({ scrollTop: 0 }, 0);																										   	
          $("#useNewAddress").show();
          $("#useAddress").hide();
		  $("#searchAddresHome").val('');
	  	  $("#searchCityHome").val('');
		  $("#searchStateHome").val('');
          $("#colorbox").css("top", "85px");
          relatedMap();
     });

	$('#newsPost')
		.focus(function(){
			$('#postOptionId').show();
			$('#newsPost').attr('placeholder-data', $('#newsPost').attr('placeholder')).removeAttr('placeholder');
		})
		.blur(function(){
			$('#newsPost').attr('placeholder', $('#newsPost').attr('placeholder-data')).removeAttr('placeholder-data');
		})
		.bind('input paste keypress', editNewsHandle);

	$('.addNews').click(function(){
		if ($.trim($('#newsPost').val()) === ''){
			$('#newsPost').focus();
			return false;
		}

		if (commonMap.mapMoved){
			$("#locationButton").click();
			return false;
		}

		preparePost(new google.maps.LatLng(parseFloat(userLatitude), parseFloat(userLongitude)), userAddress);
	});

	$('#postlocationButton').click(function(){
		if ($.trim($('#newsPost').val()) === ''){
			$('#newsPost').focus();
			return false;
		}

		$(".Change-Post-Location-Popup-Class").colorbox({
			width: '70%',
			height: '',
			inline: true,
			href: '#Change-Postlocation-Map-Popup-id',
			open: true
		}, function(){
			var customAddress = '';

			$('html, body').animate({scrollTop: 0}, 0);
			$('.Change-homemap-address').val('');

			if (changePostLocationMap){
				changePostLocationMap.setZoom(14);
				changePostLocationMap.setCenter(changePostLocationMarker.getPosition());
			} else {
				changePostLocationMap = new google.maps.Map($('#Change-Postlocation-Map-Popup-id #mapSection')[0], {
					zoom: 14,
					center: new google.maps.LatLng(userLatitude, userLongitude)
				});

				changePostLocationMarker = new google.maps.Marker({
					map: changePostLocationMap,
					draggable: true,
					position: changePostLocationMap.getCenter(),
					icon: new google.maps.MarkerImage(baseUrl + MainMarker1.image, null, null, null,
						new google.maps.Size(MainMarker1.height,MainMarker1.width))
				});

				var infoWindow = new google.maps.InfoWindow({
					maxWidth: 220,
					content: commonMap.addressContent(userAddress, imagePath)
				});

				infoWindow.open(changePostLocationMap, changePostLocationMarker);

				google.maps.event.addListener(changePostLocationMarker, 'dragend', function(event){
					changePostLocationMap.setCenter(event.latLng);
					infoWindow.close();

					geocoder.geocode({
						'latLng': event.latLng
					}, function(results, status){
						if (status != google.maps.GeocoderStatus.OK){
							return false;
						}

						customAddress = results[0].formatted_address;
						infoWindow.setContent(commonMap.addressContent(customAddress, imagePath))
						infoWindow.open(changePostLocationMap, changePostLocationMarker);
					});
				});

				var autocomplete = new google.maps.places.Autocomplete($('.Change-homemap-address')[0]);

				$('#Change-Postlocation-Map-Popup-id .Change-srh-img').click(function(){
					var $address_target = $('.Change-homemap-address');
						address = $.trim($address_target.val());

					if (address === ''){
						$address_target.focus();
						return;
					}

					geocoder.geocode({
						'address': address
					}, function(results, status){
						if (status == google.maps.GeocoderStatus.OK){
							customAddress = results[0].formatted_address;
							infoWindow.setContent(results[0].formatted_address);
							changePostLocationMap.setCenter(results[0].geometry.location);
							changePostLocationMarker.setPosition(results[0].geometry.location);
						} else {
							alert('Sorry! We are unable to find this location.');
							$address_target.val('');
						}
					});
				});

				$('#Change-Postlocation-Map-Popup-id .useNewAddress').click(function(){
					$('#cboxClose').trigger('click');

					if (distance(commonMap.map.getCenter(), changePostLocationMap.getCenter(), 'M') > getRadius()){
						commonMap.showMapElement = false;
						commonMap.map.setCenter(changePostLocationMap.getCenter());
						commonMap.onMapDragen('CENTER');
					}

					preparePost(changePostLocationMap.getCenter(), customAddress);
				});
			}
		});
	});

	upclick({
		dataname: 'images',
		element: 'uploader',
		onstart: function(filename){
			var uploadedImage = filename.split('\\');
			var imageRealName = uploadedImage[uploadedImage.length-1];

			if (((imageRealName.length) - 50) > 4){
				var fileExtension = imageRealName.split('.');
				imageRealName = imageRealName.substring(0,50)+"..."+fileExtension[fileExtension.length-1];
			}

			filename = "<br/><div id='case1' style='margin-left: -25px;margin-top: -9px;'><div style='float:left;font: normal 12px lato-Regular;'>"+imageRealName;
			filename += '</div><div>&nbsp;<img class="image_delete" onclick="clearUpload();" src="'+baseUrl+'www/images/delete-icon.png"/></div></div>';
			$("#fileNm").html(filename);
			filename = uploadedImage[uploadedImage.length-1].split('.');

			var filenameArrayLength = filename.length;

			if (filename[filenameArrayLength-1] != 'jpg' && filename[filenameArrayLength-1] != 'gif' &&
				filename[filenameArrayLength-1] != 'jpeg' && filename[filenameArrayLength-1] != 'png' &&
				filename[filenameArrayLength-1] != 'bmp' && filename[filenameArrayLength-1] != 'JPG'){
				alert("Invalid file format");	
				clearUpload();
			}
		}
	});
});

$(window).scroll(function(){
	if ($("#midColLayout").height()>714){
		setThisHeight(Number($("#midColLayout").height()));
	}
});

/*
* Function to clear the upload div form and iframe
*/      
function clearUpload() {
    $('#fileNm').html("");
    var ufchilds = $("#mainForm").children();
    $(ufchilds[0]).val("");
}


var formContainor = '';

/*
* Function to reinitialize uploader for the next accesss
*/    
function reinitilizeUpload() {
     upclick({
		dataname: 'images',
        element: 'uploader',
        onstart: function(filename) {
            var uploadedImage = filename.split('\\');
            var imageRealName = uploadedImage[uploadedImage.length-1];
            if(((imageRealName.length) - 50)>4){
                var fileExtension = imageRealName.split('.');
                imageRealName = imageRealName.substring(0,50)+"..."+fileExtension[fileExtension.length-1];
            }    
            filename = "<br/><div id='case2' style='margin-left:0px;margin-top:-37px;'><div style='margin-bottom : 2px;float:left;'>"+imageRealName;
            filename += '</div><div>&nbsp;<img onclick="clearUpload();" src="'+baseUrl+'www/images/delete-icon.png" style="cursor:pointer;"/></div></div>';
            $("#fileNm").html(filename);
        }
    });
}

function preparePost(location, address){
	var news = $('#newsPost').val();

   	if (news.indexOf('<') > 0 || news.indexOf('>') > 0){
		alert('You enter invalid text');
		return false;
	}

	$('#newsWaitOnAdd').show();

	if ($.trim(address) !== ''){
		return addPost(location, address);
	}

	geocoder.geocode({
		'latLng': location
	}, function(results, status){
		if (status == google.maps.GeocoderStatus.OK){
			addPost(location, results[0].formatted_address);
		} else {
			addPost(location);
		}
	});
}

function addPost(location, address){
	var data = new FormData();

	if (address){
		data.append('address', address);
	}

	if ($('#fileNm').html()){
		data.append('images', $('[name=images]', formContainor)[0].files[0]);
	}

	data.append('news', $('#newsPost').val());
	data.append('latitude', location.lat());
	data.append('longitude', location.lng());

	$.ajax({
		url: baseUrl + 'home/add-news',
		data: data,
		cache: false,
		contentType: false,
		processData: false,
		dataType: 'json',
		type: 'POST',
	}).done(function(response){
		if (response && response.status){
			updateNews($(response.news.html).prependTo($('#newsData')));
			$('#noNews').html('');
			$('#newsWaitOnAdd').hide();

			$('html, body').animate({scrollTop: 0}, 0);

			commonMap.createMarkersOnMap1([response.news], UserMarker1);

			clearUpload();
			reinitilizeUpload();

			$('#postOptionId').hide();
			$('#newsPost').val('').css('height', 36);
			$('#loading').hide();
		} else if (response){
			alert(response.error.message);
		} else {
			alert(ERROR_MESSAGE);
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
	});

	$("#mainForm").parent().remove();
}
