var map;

var centerPoint;

var userName;

var user_id;

var userImage;

var geocoder = new google.maps.Geocoder();

var firstFlag = true;

var dragableMarker = null;

var dragableInfobubble = null;

var infoArray = new Array();

var markerArray = new Object();

var news = '';

var centerMarker;

var customMap1,customMap2; //Related maps

var maxRadious = 0;

var markersOnMap = new Array(); //Contains all markers on map

var mapMoved = false; //to check whether map is dragend or not

var changedAddress;

var circle;

var mapCenterPoint = null;

var mapCenterMarker = null;

var circleSize = "0.8";

var previousBubbleOpened = null;

var INFOBUBBLE_ELEMENTS = new Object({

									 	"marker":null,

										"address":null,

										"map":null,

										"profileImage":null

									});



function changeMapZoom(type){

    var zoom = map.getZoom();

    if(type == 'in') {

        zoom++;

        map.setZoom(zoom);

    } else {

        zoom--;

        map.setZoom(zoom);

    }

}




function createInfoBubble(address,marker,map,imagePath){

	 var contentString = "<div style='width:100%;height:60px;'><div style='width:70%;float:left'>"+address+"</div><div style='width:30%;float:right'><img src='"+imagePath+"' style='height:50px;width:50px;'/></div></div>";

    var infowindow = new google.maps.InfoWindow({maxWidth:220});

    infowindow.setContent(contentString);

            infowindow.setPosition(marker.getPosition());

            infowindow.open(map);        

    google.maps.event.addListener(marker, 'click', function() {

            infowindow.setContent(contentString);

            infowindow.setPosition(marker.getPosition());

            infowindow.open(map);

    });

}

function createCenterMarker(latLng) {

     geocoder.geocode({

         'latLng': latLng

        }, function(results,status) {

            if (status == google.maps.GeocoderStatus.OK) {

                    if (results[0]) {

                        var address = results[0].formatted_address;

                        var image = new google.maps.MarkerImage(baseUrl+'www/images/marker.png',null,null,null, new google.maps.Size(25,50));

                        centerMarker = new google.maps.Marker({

                            position: latLng, 

                            map: map,

                            draggable:false,

                            icon:image

                    	});

                        centerMarker.setMap(map);

                         var infowindow = new google.maps.InfoWindow({maxWidth:220});

                         infowindow.setContent(address);

                         infowindow.setPosition(centerMarker.getPosition());

                         infowindow.open(map);        

                        

                     }

            }

        });

}



var rLatitude = null;

var rLongitude = null;

function initializeMap(type) { 

    

    hideNewsScreen();

	newsIdArray = new Object();

	latlngArray = new Object();

	infowindowArray = new Object();

	markerArray = new Object();

	user_idArray = new Array();

	

	$("#addNewsDiv").show();

	$("#SearchParameter").hide();

    firstFlag = true;

    $("#mapSection").html("");

	$("#mapSection1").html("");

    $("#cboxClose").show();

    map = null;

	

    if((rLatitude==null)&&(rLongitude==null)){

	  rLatitude = geoiplatitude; 

      rLongitude = geoiplongitude;

	}

	

	if($("#RLatitude"))

	   $("#RLatitude").attr('value',rLatitude);

	   

	if($("#RLongitude"))

	   $("#RLongitude").attr('value',rLongitude);

    if(dragableMarker) {

        dragableMarker.setMap(null);

        dragableMarker.info.setMap(null);

        dragableMarker = null;

        dragableInfobubble = null;

     }

    

	var myOptions = {

		zoom: 10,

        minZoom:2,

		center: new google.maps.LatLng(rLatitude, rLongitude),

        disableDefaultUI: true,

        panControl: false,

    	zoomControl: true,

        scaleControl: false,

    	streetViewControl: false,

    	overviewMapControl:false,

        draggable:true,

		mapTypeId: google.maps.MapTypeId.ROADMAP,

        boxStyle: { 

             background: "#fff",

        },

        closeBoxURL: "http://www.google.com/intl/en_us/mapfiles/close.gif"

        

	};

	

    if(document.getElementById('mapSection1')) {

		map = new google.maps.Map(document.getElementById('mapSection1'), myOptions);

	}else{    

		map = new google.maps.Map(document.getElementById('mapSection'), myOptions);

	}

 

    var searchPanel = document.getElementById('searchPanel');

    map.controls[google.maps.ControlPosition.TOP_CENTER].push(searchPanel);

    

    google.maps.event.addListener(map, "click", function(event){

       

    	mapClickHandler(event); 

    });

	var latlng = new google.maps.LatLng(rLatitude, rLongitude);

	

	getPlaceByLatitue(latlng,'draw');

    var input = document.getElementById('searchAddres');

    var autocomplete = new google.maps.places.Autocomplete(input);

}





function changeUserAddress(flag) {

	var userAddress = '';

	if(flag == 'draw'){

		var searchaddr = $("#searchAddres").val();

		if(searchaddr) {

			userAddress += searchaddr+', ';

		}

		if(searchaddr == ""){

			$("#searchAddres").addClass("inputErrorBorder");

			return;

		} else {

			$("#searchAddres").removeClass("inputErrorBorder");

		}

	} else{

	    userAddress = $('#userAddress').html();

	}

    if(userAddress) {

        geocoder.geocode({

         'address': userAddress

        }, function(results,status) {

            if (status == google.maps.GeocoderStatus.OK) {

                    if (results[0]) {

                       var latlng = results[0].geometry.location;

					   if(flag == 'draw'){

						   map.setCenter(latlng);

						   rLatitude = latlng.lat();

						   rLongitude = latlng.lng();

						   getPlaceByLatitue(latlng,'draw');

					   } else {

                       	   changeLatLng(userAddress,latlng.lat(),latlng.lng()) 

					   }

                    }

                } else {

					alert("Sorry! We are unable to find this location.");

					$("#searchAddres").val("");

				}

            } 

        );

    } else {

        alert("Sorry! We are unable to find this location.");

		$("#searchAddres").val("");

    }

    

}



function changeLatLng(address,cLatitude,cLongitude, type) {

    $.ajaxSetup({async:false});

    $.post(

		baseUrl+"here-spy/change-address",

        {address:address,latitude:cLatitude,longitude:cLongitude},

        function(response){

           response =  JSON.parse(response);

		   if($('#location_error_div')) {

			   $('#location_error_div').slideUp();	

		   }
        },

        'html'

  );

  $.ajaxSetup({async:true});

}



function mapClickHandler(event){

    $('#useAddress').removeAttr('disabled');

	$('#useAddress').addClass('btn primary');

	if($("#RLatitude"))

	   $("#RLatitude").attr('value',(event.latLng).lat());

	if($("#RLongitude"))

	   $("#RLongitude").attr('value',(event.latLng).lng());

	   

    var flag = '';

    if(dragableInfobubble) {

        flag = true;

    } else {

        flag = false;

    }

    if(flag){

        if(!dragableInfobubble.isOpen())

            getPlaceByLatitue(event.latLng,'draw');

        else 

            return;

    } else {

		map.setZoom(10);

        getPlaceByLatitue(event.latLng,'draw');

    }

}

var newsIdArray = new Object();

var latlngArray = new Object();

var infowindowArray = new Object();

var user_idArray = new Array();

var lts;



function in_array(string, array){

	for (i in array) {

		if(""+array[i]+"" == ""+string+"") {

			return i;

		}

	}

	return -1;

}



function user_array(string, array){

	for (i = 0; i < array.length; i++) {

		if(array[i] == string) {

			return true;

		}

	}

	return false;

}



function placeMarker(point,name,id,address,News,user_id) {



	var currPos;

    var centerFlag = false;

	var noMarker;

	var hasUser = 0;

	var currentPos = in_array(point,latlngArray);



	if(currentPos != -1 && id != '0') {

		markerArray[id] = markerArray[currentPos];

	}

		

	if((user_array(user_id, user_idArray) == true) && currentPos != -1) {

		hasUser = 1;

		noMarker = false;

		

	} else if(currentPos != -1) {

		if(id != '0' && (user_array(user_id, user_idArray) == false)) {

			user_idArray.push(user_id);

		}

		noMarker = false;

		hasUser = 2;

        

		

	} else {

		hasUser = 0;

		latlngArray[id] = point;

		noMarker = true;

		if(id != '0' && (user_array(user_id, user_idArray) == false)) {

			user_idArray.push(user_id);

		}

	}

	

	if(noMarker) {

	    var curPos = in_array(point,latlngArray);

		if((Number(user_id) == Number(user_id)) && (""+centerPoint+"" == ""+point+"")) {

			centerFlag = true;

			var image = new google.maps.MarkerImage('www/images/marker.png',null,null,null, new google.maps.Size(25,50));

		} else {

			if(""+centerPoint+"" == ""+point+"") {

			    centerFlag = true;

				var image = new google.maps.MarkerImage('www/images/marker.png',null,null,null, new google.maps.Size(25,50));

			} else {

				var image = new google.maps.MarkerImage('www/images/mapize_marker_red.png',null,null,null, new google.maps.Size(25,30));

			}

		}

		

		var marker = new google.maps.Marker({

			icon: image,

			position: point, 

			map: map,

			title: name

		});

		

		markerArray[id] = marker;

		markersOnMap.push(marker);

        if(centerFlag) {

            if(mapCenterMarker) {

               marker.setMap(null); 

            } else {

               mapCenterMarker = marker; 

            }

            

           if(circle)

                circle.hideCircle();

      

           circle = new AreaCircle({

                radious:Number($("#radious").html()),

                center:map.getCenter(),

           })

           

        }

	

		var infoBubble = new InfoBubble();

		

		if(infowindowArray[curPos]) {

			var contents = infowindowArray[curPos].getContent();

			contents += "<div id='"+curPos+"div"+user_id+"'><br/>---<br/><b>Posted By : </b><span id='userName'>"+name+"</span><br/>";

			contents += "<span class='markerNews'>"+News+"</span></div>";

			infowindowArray[curPos].setContent(contents);

		} else {

			var content = "<div id='infoBoxStyle' align='left'>";

			

			content = content+"<div id='"+id+"div"+user_id+"'><b>Posted By : </b><span id='userName'>"+name+"</span>";

			content = content+"<br/><span class='markerNews'>"+News+"</span></div>";

			infoBubble.setContent(content);

			

			google.maps.event.addListener(marker,"mouseover",function(event){

			    if(previousBubbleOpened)

					previousBubbleOpened.close();

			   	for(i in infoArray)

					infoArray[i].close();

				previousBubbleOpened = infoBubble;

				infoBubble.open(map,marker);

			});

			

			google.maps.event.addListener(marker, "mouseout", function() {

				if(previousBubbleOpened)

					previousBubbleOpened.close();

				previousBubbleOpened = null;

				for(i in infoArray)

					infoArray[i].close();

			});

			

			infoArray.push(infoBubble);

			infowindowArray[id] = infoBubble;

		}

		

	} else {

	    

		if(hasUser == 2 && id != '0') {

		   	var contents = infowindowArray[currentPos].getContent();

			contents += "<div id='"+currentPos+"div"+user_id+"'><br/>---<br/><b>Posted By : </b><span id='userName'>"+name+"</span><br/>";

			contents += "<span class='markerNews'>"+News+"</span></div>";

            infowindowArray[currentPos].setContent(contents);

		} else {

		   markerArray[id].setMap(map);

           var infoBubble = new InfoBubble();

           if(infowindowArray[curPos]) {

			 var contents = infowindowArray[curPos].getContent();

			 contents += "<div id='"+curPos+"div"+user_id+"'><br/>---<br/><b>Posted By : </b><span id='userName'>"+name+"</span><br/>";

			 contents += "<span class='markerNews'>"+News+"</span></div>";

		     infowindowArray[curPos].setContent(contents);

    	  } else {

    			var content = "<div id='infoBoxStyle' align='left'>";

    			

    			content = content+"<div id='"+id+"div"+user_id+"'><b>Posted By : </b><span id='userName'>"+name+"</span>";

    			content = content+"<br/><span class='markerNews'>"+News+"</span></div>";

    			content = content+"</div>"; 

    			infoBubble.setContent(content);

    			

    			google.maps.event.addListener(markerArray[id],"mouseover",function(event){

    			 	for(i in infoArray)

    					infoArray[i].close();

    				infoBubble.open(map,this);

    			});

    			

    			google.maps.event.addListener(markerArray[id], "mouseout", function() {

    				for(i in infoArray)

    					infoArray[i].close();

    			});

    			infoArray.push(infoBubble);

    			infowindowArray[id] = infoBubble;

    		}

		}

	}

    

}



function toggleBounce(id,user_id) {

	

	if(markerArray[id]) {

		

		if((Number(user_id) == Number(user_id)) && (""+centerPoint+"" == ""+markerArray[id].getPosition()+"")) {

			var image = new google.maps.MarkerImage('www/images/GreenGMapMarker.png',null,null,null, new google.maps.Size(30,50));

			markerArray[id].setIcon(image);

		} else {

			if (""+centerPoint+"" == ""+markerArray[id].getPosition()+"") {

				var image = new google.maps.MarkerImage('www/images/GreenGMapMarker.png',null,null,null, new google.maps.Size(30,50));

				markerArray[id].setIcon(image);

			} else {

				var image = new google.maps.MarkerImage('www/images/GreenGMapMarker.png',null,null,null, new google.maps.Size(25,30));

				markerArray[id].setIcon(image);

			}

		}

	}

}



function stopBounce(id,user_id) {

	

	if(markerArray[id]) {

		

		if((Number(user_id) == Number(user_id))&&(""+centerPoint+"" == ""+markerArray[id].getPosition()+"")) {

			var image = new google.maps.MarkerImage('www/images/marker.png',null,null,null, new google.maps.Size(25,50));

			markerArray[id].setIcon(image);

		} else {

			if (""+centerPoint+"" == ""+markerArray[id].getPosition()+"") {

				var image = new google.maps.MarkerImage('www/images/marker.png',null,null,null, new google.maps.Size(25,50));

				markerArray[id].setIcon(image);

			} else {

				var image = new google.maps.MarkerImage('www/images/mapize_marker_red.png',null,null,null, new google.maps.Size(25,30));

				markerArray[id].setIcon(image);

			}

		}

	}

}



function getPlaceByAddress(address,type) {

    geocoder.geocode({

     'address': address

    }, function(results,status) {

     if (status == google.maps.GeocoderStatus.OK) {

         if (results[0]) {

             var formattedAddress = results[0].formatted_address;

             var latlng = results[0].geometry.location;

             if(type=='centerpoint')

                centerPoint = latlng;

             if(type=='search'){

				$('#useAddress').removeAttr('disabled');

				$('#useAddress').addClass('btn primary');

                userLatitude = latlng.lat();

                userLongitude = latlng.lng();

                $("#currentAddress").attr('value',formattedAddress);

                createDragableMarker(latlng,formattedAddress);

             }

             return;

         }

     }

    });

}



function getPlaceByLatitue(latlng,flag) {

    geocoder.geocode({

     'latLng': latlng

    }, function(results,status) {

     if (status == google.maps.GeocoderStatus.OK) {

         if (results[0]) {

             

             var formattedAddress = results[0].formatted_address;

             var latitudeLongitude = results[0].geometry.location;

             userLatitude = latitudeLongitude.lat();

             userLongitude = latitudeLongitude.lng();

			 

             $("#currentAddress").attr('value',formattedAddress);

             if(flag == 'draw') {

               centerPoint = latitudeLongitude;

			   createDragableMarker(latitudeLongitude,formattedAddress);

             } 

             

             if(flag == 'popup') {

                dragableMarker.info.setContent(createAddressContent(formattedAddress));

                dragableMarker.info.open(map,dragableMarker);

             }

             

             if(flag == 'centerpoint') {

                centerPoint = latitudeLongitude;

             }

			 

			 if(flag == 'initiate'){

				 INFOBUBBLE_ELEMENTS.address = formattedAddress;

  				 createInfoBubble(INFOBUBBLE_ELEMENTS.address,INFOBUBBLE_ELEMENTS.marker,INFOBUBBLE_ELEMENTS.map,INFOBUBBLE_ELEMENTS.profileImage);

			 }

			 if($("#userLoc")) {

			 	$("#userLoc").html(formattedAddress);

			 }

             return;

         }

     }

    });

}



function createAddressContent(address) {

    var content = "<div id='infoBoxSmall' align='left'>";

    content = content+"<b>Address : </b><br/><span id='userAddress'>"+address+"</span>";

    content = content+"</div>";   

	

	if($("#address")) {

		$("#address").attr('value',address);

	}

    return content;

}



function createDragableMarker(latLng,address){

    var image = new google.maps.MarkerImage(baseUrl+'www/images/man.png',null,null,null, new google.maps.Size(26,36));

    

    if(dragableMarker) {

        dragableMarker.setMap(null);

    }

    

    dragableMarker = new google.maps.Marker({

        position: latLng, 

        map: map,

        draggable:true,

        icon:image

	});

    

	dragableMarker.info = new google.maps.InfoWindow({

		content:createAddressContent(address)

	});

	

	dragableMarker.info.open(map, dragableMarker);

	google.maps.event.addListener(dragableMarker, 'click', function() {

	   dragableMarker.info.open(map, dragableMarker);



	});

    google.maps.event.addListener(dragableMarker,'drag',function(event){

	   dragableMarker.info.setMap(null);

       map.setOptions({draggable:false})

    });

	google.maps.event.addListener(dragableMarker,'dragend',function(event){

         centerPoint = event.latLng;																	

         dragableMarker.info.setMap(null);

         getPlaceByLatitue(event.latLng,'popup');

		 if($("#RLatitude"))

	   		$("#RLatitude").attr('value',(event.latLng).lat());

	   

		if($("#RLongitude"))

		   $("#RLongitude").attr('value',(event.latLng).lng());

        map.setOptions({draggable:true});

      

    });

    setTimeout(function(){

        var parent= jQuery(".gm-style-iw").parent();

        parent.children("div").eq(0).children("div").eq(1).css({"background-color": "#fff", "box-shadow": "none"});

        parent.children("div").eq(0).children("div").eq(0).css({"border-top-color": "#fff"});

        parent.children("div").eq(0).children("div").eq(2).children("div").eq(0).children("div").eq(0).css({"box-shadow": "none"});

        parent.children("div").eq(0).children("div").eq(2).children("div").eq(1).children("div").eq(0).css({"box-shadow": "none"});

        parent.children("div").eq(2).children("img").attr("src", "/www/images/iw_close.gif");

        parent.children("div").eq(2).children("img").css({"position": "absolute","left": "2px","top": "0px","width": "11px","height": "11px"});

    }, 2700);

}





function setContent(formattedAddress){

    $('#userAddress').html(formattedAddress);

}

function createSearchBar() {

    var content;

    content = '<div id="searchPanel">';

    content += '<input type="text" value="Search Place" id="searchPlace" name="searchPlace" onfocus="if(this.value==\'Search Place\'){this.value = \'\';}"  onblur="if(this.value==\'\'){this.value = \'Search Place\';}" />&nbsp;<input type="button" value="Go" onclick="searchPlaceBy();"/>';    

    content += '</div>';

    $("#searchContent").html(content);

    

}



var name = "#floatMenu";  

var menuYloc = null;  

  

$(document).ready(function(){ 

	if($(name).css("top")){

	menuYloc = parseInt($(name).css("top").substring(0,$(name).css("top").indexOf("px")))  

	$(window).scroll(function () {  

		var offset = menuYloc+$(document).scrollTop()+"px";  

		$(name).animate({top:offset},{duration:500,queue:false});  

	});  

	}

});

function resizeArea(id){

	var keycode = '';

	

	$('#comment'+id).bind('keydown', function(event) {

		keycode = event.keyCode;

		if(keycode === 13 && event.shiftKey) {

			$('#comment'+id).autoGrow();

		} else if(keycode === 13){

			var comments = $('#comment'+id).val();

			var news_id = id;

			if(comments != '') {

				$.post(

					baseUrl+"here-spy/add-new-comments",

					{

						'comments':comments, 'news_id':news_id, 'user_id' : user_id

					},

					function(obj){

						obj = JSON.parse(obj);

						

						$('#comment'+id).val('');

						$('#comment'+id).blur();

						$('#comment'+id).attr('rows','1');

						

						var commentData = '<div id="comment_' + obj.commentId + '" class="commentField"><table><tr><td style="width:10%" valign="top">';

						if(obj.image) {

							if(obj.image.indexOf('://') > 0) {

								commentData += '<img src="' + obj.image + '" style="width:35px;height:35px;"/></td><td valign="top" style="width:90%">';

							}else {

								commentData += '<img src="' + baseUrl + 'uploads/' + obj.image + '" style="width:35px;height:35px;"/></td><td valign="top" style="width:90%">';

							}

						}else {

							commentData += '<img src="' + baseUrl + 'www/images/img-prof40x40.jpg" tyle="width:35px;height:35px;"/></td><td valign="top" style="width:90%">';

						}

						 commentData += '<div class="user_name"><b class="userNameStyle">' + userName + '</b>&nbsp;<span id="comm">' + comments + '</span></td></tr></table>' + '<div class="commentTime">Just now</div>' + '</div>';

						$("#comment_list_"+id).append(commentData);

					},

					"html"

				)

			}

		}

	});



	if(keycode != 13){

		$('#comment'+id).autoGrow();

	} else {

		

	}

}

function showMoreNews(thisOne,morecontentId){

    $(thisOne).remove();

    var newsParent = $("#morecontent_"+morecontentId).parent().parent();

    $(newsParent).html($(newsParent).html()+""+$("#morecontent_"+morecontentId).html());

    var height=Number($(newsParent).parent().parent().height())+Number($("#morecontent_"+morecontentId).height());

    $(newsParent).parent().parent().height(height-30);

    $("#morecontent_"+morecontentId).remove();

}



/*******************************************************************************************************************************************/

/*****                                          Function to validate existance of Email address                                        *****/

/*******************************************************************************************************************************************/



function validateEmail(email) {

    $.ajaxSetup({async:false});

    $.post(

		baseUrl+"here-spy/validate-email",

        {email:email},

        function(response){

           response =  JSON.parse(response);  

           emailValidation(response.result);

        },

        'html'

  );

  $.ajaxSetup({async:true});

}



/*******************************************************************************************************************************************/

/*****                      Function hepls to create two map which are related change in on reflects in othere                         *****/

/*****                      cancelSearch() function to cancel search and close popup                                                   *****/

/*****                      closeThisPopup() to save new centerpoint of user  and   close popup                                        *****/

/*****                      relatedMap(MAP TYPE,CURRENT ADDRESS) to create map which are ralated to each othere                          *****/

/*******************************************************************************************************************************************/



function relatedMap(type,address){

    if(type == 'first') {

            customMap1 = new CustomMap({

               mapElement : document.getElementById('mapSection1'),

               mapType : 'nonclickable',

               centerPoint : new google.maps.LatLng(userLatitude,userLongitude),

               markerType : 'nondragable',

               address : address,

               icon:{

                    image:baseUrl+'www/images/man.png',

                    height:38,

                    width:24,

                },

               relatedMap:null

             });

      } else {       

          customMap2 = new CustomMap({

                   mapElement : document.getElementById('mapSection2'),

                   mapType : 'clickable',

                   centerPoint : customMap1.centerPoint,

                   markerType : 'dragable',

                   address : address,

                   icon:{

                        image:baseUrl+'www/images/man.png',

                        height:38,

                        width:24,

                    },

                   relatedMap:customMap1

          });

      }

}



function cancelSearch(){ 

	customMap1.getPlaceByLatitue(customMap1.centerPoint,'nochange');

    $('#cboxClose').trigger('click');

}



function closeThisPopup() {

	customMap1.centerPoint = customMap2.centerPoint = (customMap2.marker).getPosition();

	customMap1.address = customMap2.address;

    $("#userLoc").html(customMap2.address);

    $('#cboxClose').trigger('click');

}


/*******************************************************************************************************************************************/

/*****                      Function to find latitude longitude of point at given angle and distance                                   *****/

/*******************************************************************************************************************************************/



google.maps.LatLng.prototype.findPoint = function (brng, dist) {

    

    dist = dist / 6371;

    brng = brng.toRad();

    

    var lat1 = this.lat().toRad(), lon1 = this.lng().toRad();

    

    var lat2 = Math.asin(Math.sin(lat1) * Math.cos(dist) +

    Math.cos(lat1) * Math.sin(dist) * Math.cos(brng));

    

    var lon2 = lon1 + Math.atan2(Math.sin(brng) * Math.sin(dist) *

    Math.cos(lat1),

    Math.cos(dist) - Math.sin(lat1) *

    Math.sin(lat2));

    

    if (isNaN(lat2) || isNaN(lon2)) return null;

    

    return new google.maps.LatLng(lat2.toDeg(), lon2.toDeg());

}



Number.prototype.toRad = function () {

    return this * Math.PI / 180;

}



Number.prototype.toDeg = function () {

    return this * 180 / Math.PI;

}





function distanceBetween(secondPoint,firstPoint){

    lat1 = firstPoint.lat();

	lon1 = firstPoint.lng();

	

	lat2 = secondPoint.lat();

	lon2 = secondPoint.lng();

	var R = 6371; 

	var dLat = (lat2-lat1).toRad();

	var dLon = (lon2-lon1).toRad();

	var lat1 = lat1.toRad();

	var lat2 = lat2.toRad();



	var a = (Math.sin(dLat/2) * Math.sin(dLat/2)) + (Math.sin(dLon/2) * Math.sin(dLon/2) * Math.cos(lat1) * Math.cos(lat2)); 

	var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 

	var d = R * c;

	return d;

}



function distance(firstPoint,secondPoint,unit) { 

  var lat1= firstPoint.lat();

  var lon1= firstPoint.lng(); 

  var lat2 = secondPoint.lat();

  var lon2= secondPoint.lng();



  var theta = lon1 - lon2; 

  var dist = Math.sin(lat1.toRad()) * Math.sin(lat2.toRad()) +  Math.cos(lat1.toRad()) * Math.cos(lat2.toRad()) * Math.cos(theta.toRad()); 

  dist = Math.acos(dist); 

  dist = dist.toDeg(); 

  var miles = dist * 60 * 1.1515;

  

  if (unit == "K") {

    return (miles * 1.609344); 

  } else if (unit == "N") {

      return (miles * 0.8684);

    } else {

		return miles;

      }

}
