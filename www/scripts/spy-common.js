var ERROR_MESSAGE = 'Internal server error';
var NEWS_LIMIT = 15;
var commonMap;
var map;
var circle;
var bubbleContent = null;
var updatedContentId = 0;
var geocoder = new google.maps.Geocoder();
var previousBubble = null;
var currentIdOfMarker = "";
var initFlagForMap = null;
var paginationResultObject = null;
var markerArray = new Array();
var previousBox = null;
var popupCloseTimer = 0;
var globalStart = 0;
var otherProfileExist = 0;

/*
* Set of variables contains the map icon
*/
var MainMarker1 = {image:'www/images/icons/icon_1.png',
                    height:27,
                    width:37
                };
var MainMarker2 = {image:'www/images/icons/icon_3.png',
                    height:27,
                    width:37
                };
var UserMarker1 = {image:'www/images/icons/icon_2.png',
                    height:22,
                    width:32
                };
var UserMarker2 =  {image:'www/images/icons/icon_3.png',
                    height:22,
                    width:32
                };

function friendMapInitialize() {
    mapMoved = false;
    centerPoint = new google.maps.LatLng(userLatitude,userLongitude);
    geocoder.geocode({
      'latLng': centerPoint
    },function(results,status) {
         if (status == google.maps.GeocoderStatus.OK) {
            var dataToReturn = {name:userName,id:user_id,news:results[0].formatted_address,userImage:imagePath,user_id:user_id};
            profileMap(userLatitude,userLongitude,'MAIN',dataToReturn)
            moreFriends(0,'OTHER');
        }
    });
}


/*
* Function to initialise the map and get latest news
*/
function initialize() {
    mapMoved = false;
    centerPoint = new google.maps.LatLng(userLatitude,userLongitude);
  	latestNews();

    var input = document.getElementById('searchAddresHome');
    var autocomplete = new google.maps.places.Autocomplete(input);
    var input2 = document.getElementById('Change-searchAddresHome');
    var autocomplete = new google.maps.places.Autocomplete(input2);
}

/*
* Function to set the related map so that any changes on 
* the current map will effect the main map like : location change
* @param {lat : latitude,lng : longitude}
*/
var commonMap2 = null;

    function relatedMap(lat, lng){
    	var currentCenterPoint = null;
    	if((lat)&&(lng)){
    		currentCenterPoint = new google.maps.LatLng(lat,lng);
    	} else {
    		currentCenterPoint = new google.maps.LatLng(userLatitude,userLongitude);
    	}
        
        commonMap2 = new MainMap({
            mapElement  : document.getElementById('mapSection'),
            centerPoint : currentCenterPoint,
            icon:MainMarker1,
            centerData:"",
            mapType:"RELATED",
            profileImage:imagePath,
            markerType:'dragable',
            isMapDragable:'dragable',
            showMapElement:false
        });
        $("#useNewAddress").removeAttr('disabled');
        var input = document.getElementById('searchAddres');
        var autocomplete = new google.maps.places.Autocomplete(input);
    }


/*
* Function to set the profile map
* @param {lat : latitude,lng : longitude}
*/

function profileMap(lat,lng,type,dataToShow){
    var profileImage = '';
     if(dataToShow){
        profileImage = dataToShow.userImage;
     } else {
        profileImage = '/www/images/img-prof40x40.jpg';
     }

     commonMap = new MainMap ({
         mapElement : document.getElementById('map_canvas'),
         centerPoint : new google.maps.LatLng(lat,lng),
         icon:MainMarker1,
         centerData:dataToShow,
         mapType:type,
	     profileImage:profileImage,
         markerType:'nonDragable',
         isMapDragable:'dragable',
         showMapElement:false
     });
}

function profileMapOther(lat,lng,type,dataToShowOther){
      otherProfileExist = 1;
      var profileImage = '';
     if(dataToShowOther){
        profileImage = dataToShowOther.userImage;
     } else {
        profileImage = '/www/images/img-prof40x40.jpg';
     }

    commonMap = new MainMap({
        mapElement : document.getElementById('map_canvas'),
        centerPoint : new google.maps.LatLng(lat,lng),
        icon:MainMarker1,
        centerData:dataToShowOther,
        mapType:type,
	    profileImage:profileImage,
        markerType:'nonDragable',
        isMapDragable:'dragable',
        showMapElement:false,
		showInfoWindow: false
     });
 }

/***********************************************************************************************************************************************************/
/*****                      Function to find latitude longitude of point at given angle and distance                                   *****/
/***********************************************************************************************************************************************************/

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

function distance(firstPoint,secondPoint,unit){ 
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



/*******************************************************************************************************************************************/
/*****                                          Function to Change map center on slide                                                 *****/
/*******************************************************************************************************************************************/

 function bindScroll(){
   if($(window).scrollTop() + $(window).height() > $(document).height() - 400) {
       $(window).unbind('scroll');
        getAllNearestPoint();
   }  
}

function changeMapCenter(brng,dist){
    var centerOfMap = (commonMap.map).getCenter();
    map.setCenter(centerOfMap.findPoint(brng,dist));
    commonMap.onMapDragen('CENTER');
}

/*******************************************************************************************************************************************/

function getAllNearestPoint(centerPosition){
    var divToFocus = false;

	if (globalStart > 0){
		divToFocus = $("#newsData div:last-child > ul");
	} else {
		commonMap.clearMarkers();
		$("#newsData").html('');
	}

    if (previousBubble){
        previousBubble.close();
    }

	reinitializeCenterData(userLatitude,userLongitude);

    showNewsScreen();
    removePaginationDiv();

	if (!centerPosition){
		if (commonMap.circle){
			centerPosition = [
				commonMap.circle.getCenter().lat(),
				commonMap.circle.getCenter().lng()
			];
		} else {
			centerPosition = [
				userLatitude,
				userLongitude
			];
		}
	}

	$.ajax({
		url: baseUrl + 'home/get-nearby-points',
		data: {
			user_id: user_id,
			latitude: centerPosition[0],
			longitude: centerPosition[1],
			radious: getRadius(),
			search_txt: $('#searchText').val(),
			filter: $('#filter_type').val(),
			fromPage: globalStart
		},
		type: 'POST',
		dataType: 'json',
		async: false
	}).done(function(response){
		if (response && response.status){
			hideNewsScreen();
			paginationResultObject = true;

			if (typeof response.result === 'object'){
				var scrollPosition = [
					self.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft,
					self.pageYOffset || document.documentElement.scrollTop  || document.body.scrollTop
				];

				window.scrollTo(scrollPosition[0], scrollPosition[1]);

				for (i in response.result){
					globalStart++;
					$("#newsData").append(response.result[i].html);
				}

				if (response.result.length == NEWS_LIMIT){
					$(window).scroll(bindScroll);
				}

				window.scrollTo(scrollPosition[0], scrollPosition[1])

				commonMap.createMarkersOnMap1(response.result, UserMarker1);

				updateNews();
			} else if (typeof response.result === 'string'){
				globalStart++;
				$("#newsData").append(response.result);
			}

			if ($("#newsData").height() > 714){
				setThisHeight(Number($("#newsData").height())+100);
			}
		} else if (response){
			alert(response.error.message);
		} else {
			alert(ERROR_MESSAGE);
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
	});
}

/*
* Function to change the height of left and right container
* on the basis of post area height
*/
var commentClass = new Array('leftCol eqlCH','rightCol textAlignCenter eqlCH');
function setThisHeight(height){
    var xxx = 0;
    $(".mainContainer div").each(function(aa){
        for(xxx in commentClass) {
            if(commentClass[xxx].indexOf($(this).attr('class'))>=0){
			$(this).css('min-height','auto');
		    $(this).height("100%");
                   if($(this).attr('id')!='mapDiv') {
                    $(this).css("position","absolute");
                }
            }
        }
    })
    $("#mainDiv").removeClass("appendClr");
    hideNewsScreen();
    $("#newsData").css("position","relative");
    $("#newsData").css("height","100%");
}

/*
* Function to add new news on the news post area
*/
function addNews() {
   	if($('#newsPost').val().indexOf("<") > 0 || $('#newsPost').val().indexOf(">") > 0){
		alert("You enter invalid text");
		return false;
	}

    var news = $.trim($('#newsPost').val());

    if(commonMap.mapMoved){
       $("#locationButton").trigger('click');
    } else {
        if(news != '') {
		      $('#newsWaitOnAdd').show();
                      var point = new google.maps.LatLng(parseFloat(userLatitude),parseFloat(userLongitude));
        		        geocoder.geocode({
        		 	   'latLng': point
     			}, function(results,status) {
       				if (status == google.maps.GeocoderStatus.OK) {
    					if (results[0]) {
							var data = new FormData();

							if ($('#fileNm').html()){
								data.append('images', $('[name=images]', formContainor)[0].files[0]);
							}

							data.append('news', news);
							data.append('latitude', userLatitude);
							data.append('longitude', userLongitude);
							data.append('address', results[0].formatted_address);

							$.ajax({
								url: baseUrl + 'home/add-news',
								data: data,
								cache: false,
								contentType: false,
								processData: false,
								dataType: 'json',
								type: 'POST',
								success: function(response){
									$("#newsData").prepend(response.news.html);
									$('#noNews').html('');
									$('#newsWaitOnAdd').hide();

									$('html, body').animate({scrollTop: 0}, 0);

									commonMap.createMarkersOnMap1([response.news], UserMarker1);

									clearUpload();
									reinitilizeUpload();
									updateNews();

									$('#postOptionId').hide();
									$('#newsPost').val('').css('height', 36).attr('placeholder','Share something from your current location');
									$('#loading').hide();
								}
							});
                                  }
                           }
                     });
                 } else {
       	$('#loading').hide();
	    }
     } 
  $("#mainForm").parent().remove();
 }

/*
* Function to get the latest news of any latitude longitude
* @param {latitude : latitude of the point,
*  longitude : longitude of the point,
*  type : INTIALIZE for initialization of new map, OTHER : to change on current map}
*/
function latestNews(){
	var dataToReturn = {
		id: 0,
		name: userName,
		news: 'This is me!',
		userImage: imagePath,
		user_id: user_id
	};

	commonMap = new MainMap({
		mapElement: document.getElementById('map_canvas'),
		centerPoint: centerPoint,
		icon: MainMarker1,
		centerData: dataToReturn,
		mapType: 'MAIN',
		markerType: 'nonDragable',
		isMapDragable: 'dragable',
		showMapElement: true
	});

	getAllNearestPoint([userLatitude, userLongitude]);
}

function reinitializeCenterData(latitude, longitude){
	$(document).find("#mainContent_0").each(function(){
		$(this).attr('currentDiv',1);
	});

	var markerContent = {
		id: 0,
		name: userName,
		news: 'This is me!',
		userImage: imagePath,
		user_id: user_id
	};

	var me = commonMap;
	var currentBubble = (me.bubbleArray[0]);
	if (currentBubble){
		currentBubble.newsId = new Array();
		currentBubble.user_id[0] = markerContent.user_id;
		currentBubble.user_id.length = 1;
		currentBubble.total = 0;
		currentBubble.currentNewsId = markerContent.id; 
		currentBubble.divContent =  me.createContent(markerContent.userImage,markerContent.name,markerContent.news,1,'first',0,markerContent.id,true);
		currentBubble.contentArgs[0] = ([markerContent.userImage,markerContent.name,markerContent.news,1,'first',0,markerContent.id]);
		currentBubble.contentArgs.length = 1;
	} else {
		var bubbleData = new Object({
			infobubble:"",
			newsId:new Array(),
			user_id:new Array(markerContent.user_id),
			currentNewsId:markerContent.id,
			divContent:me.createContent(markerContent.userImage,markerContent.name,markerContent.news,1,'first',0,markerContent.id,true),
			contentArgs:new Array([markerContent.userImage,markerContent.name,markerContent.news,1,'first',0,markerContent.id]),
			total:1
		});

		me.bubbleArray.push(bubbleData);
	}
}

function toggleBounce(newsId,user_id){
    commonMap.searchNewsMarker(newsId,'bounce');
}

function stopBounce(newsId,user_id){
    commonMap.searchNewsMarker(newsId,'stopbounce');
}

function updateNews(){
	$(".login-popup-class").colorbox({width:"26%",height:"32%", inline:true, href:"#login-id"});
	$(".Message-Popup-Class").colorbox({width:"40%",height:"45%", inline:true, href:"#Message-Popup_Email"},function(){$('html, body').animate({ scrollTop: 0 }, 0);});

	$( ".scrpBox" ).hover(function(){
		$(this).find(".deteleIcon").each(function(){
			$(this).css("display", "block");
		});
	}, function(){
		$(this).find(".deteleIcon").each(function(){
			$(this).css("display", "none");
		});
	});

	$(".Image-Popup-Class").colorbox({
		width: "60%",
		height: "80%",
		inline: true,
		href: "#Image-Popup"
	}, function(){
		$.colorbox.resize({
			width: imageWidth + 'px',
			height: imageHeight + 'px'
		});
	});
	
	$('.moreButton').click(function(e){
		e.preventDefault();

		var $post = $(this).closest('.post-bottom');
		$post.find('.short').remove();
		$post.find('.full').show();
		$(this).remove();
	});
}

/*
* Function to show toggle content on the infobubble 
* so that previous content hides and new content 
* shows when clicked on next and previous
*/
function toggleContent() {
    var flag = true;
    $("#contentUpdator").children().each(function(){
        if(flag){
            $(this).css('display','block');
            flag = false;
        }else{
            if($(this).attr('id')!='controller')
                $(this).css('display','none');
        }
    });
    $("#controller").children().each(function(){
       $(this).css('display','none');
    });
}

/*******************************************************************************************************************************************/
/*****                                          Function to handle pagination                                                          *****/
/*******************************************************************************************************************************************/

function removePaginationDiv(){
      $("#newsData div").each(function(){
        if($(this).attr('id')=='pagingDiv'){
            $(this).remove();
        }
      })
}

/*
* Function to show more news and comments when first post a meassge than clicked on 
* pagination button also includes new pagination
* @param {res : pagination result object,}
*/
function showResult() {
    if(paginationResultObject){
    	$('#buttonPost').removeAttr("disabled");
        $('#newsPost').css('height','36');
        $('#newsPost').val('');

        getAllNearestPoint();
    }
}

/*
* Function to get show comment area
* @param {DIV ID}
*/
function showCommentField(id) {
    if(isLogin){
       $("#comment_text_"+id).hide();
	   $("#commentTextarea_"+id).show();
       $("#comment"+id).focus();
       if($("#newsData").height()>714)
             setThisHeight(Number($("#newsData").height())+100);
    } else {
        alert('Please Login');
    }
}

/*
* Function to get commets for unique news
* @param {newsId : id of the news}
*/
function getComments(target){
	var news_item = $(target).closest('.scrpBox'),
		comment_list = $(target).closest('ul');

	$(target).hide();

	$.ajax({
		type: 'POST',
		url: baseUrl + 'home/get-total-comments',
		data: {
			news_id: news_item.attr('id').replace('scrpBox_', ''),
			limitstart: comment_list.find('.cmntList').size()
		},
		dataType : 'json'
	}).done(function(response){
		if (response && response.status){
			if (response.data){
				for (var i in response.data){
					$('.viewCount', comment_list).after(response.data[i]);
				}

				if (response.label){
					comment_list.effect("highlight", {}, 500, function(){
						$(target).text(response.label).show();
					});
				}
			}
		} else {
			alert(ERROR_MESSAGE);
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
	});
}

/*
* Function to resize the textbox of comment and news
* @param {DIV ID}
*/
function resizeArea(id, thisone){
	var keycode = '';
    $('#comment'+id).bind('keydown', function(event) {
		keycode = event.keyCode;
        if ($('#comment'+id).attr('value').length > 350) {
    		    alert('The comment should be less the 350 Characters');
                $('#comment'+id).attr('value',($('#comment'+id).attr('value')).substring(0, 350));
    	} 
    	
		if(keycode === 13 && event.shiftKey) {
			$('#comment'+id).autoGrow();
            if(Number($("#newsData").height())>714)
                setThisHeight(Number($("#newsData").height())+100);
        } else if(keycode === 13){
            if(($.trim($('#comment'+id).val())) == ""){
                return false;
			}
			if(($.trim($('#comment'+id).val())).indexOf("<") > 0 || ($.trim($('#comment'+id).val())).indexOf(">") > 0){
				alert("You enter invalid text");
				return false;
			}
			var comments = $('#comment'+id).val();
            $('#comment'+id).attr( "disabled","disabled");
			var news_id = id;
			if(comments != '') {
			    $('#rpl_loading_'+id).show();

				$.ajax({
					url: baseUrl + 'home/add-new-comments',
					data: {
						comment: comments,
						news_id: news_id
					},
					type: 'POST',
					dataType: 'json',
					async: false
				}).done(function(response){
					if (response && response.status){
						$('#comment'+id).val('').blur().removeAttr("disabled");
						$("#commentTextarea_"+id).before(response.html);
						$('#rpl_loading_'+id).hide();
					} else {
						alert(ERROR_MESSAGE);
					}
				}).fail(function(jqXHR, textStatus){
					alert(textStatus);
				});
			}
		}
       
	});

	if(keycode != 13){
		$('#comment'+id).autoGrow();
        if(Number($("#newsData").height())>714)
            setThisHeight(Number($("#newsData").height())+100);
	}
   
    
}
/*
* Function to show more comments when
* clicked on More.. button
*/
function showMoreComments(thisOne,currId){
    $(thisOne).hide();
    $('#morecomments_'+currId).show();
}

  function searchLatest(){
     if(controller == 'home' && action == 'index') {
		globalStart = 0; 
     getAllNearestPoint();
     } else{
        window.location= baseUrl+'home/index/sv/'+$('#searchText').val();
     }
  }

function getKeycodes(direct) {
	if (controller == 'home' && action == 'index'){
		globalStart = 0;
		getAllNearestPoint();
	} else {
		window.location = baseUrl+'home/index/sv/'+$('#searchText').val();
	}
}

/*
* Function to set the focus on the selected news
* @param {divId : id of the div}
*/
function focusOnNews(divId){
    $(document).scrollTo("#newsDiv"+divId);
	$("#newsDiv"+divId).find('.post-bottom .short').remove();
	$("#newsDiv"+divId).find('.post-bottom .moreButton').remove();
	$("#newsDiv"+divId).find('.post-bottom .full').show();
    $("#newsDiv"+divId).parent().css('box-shadow','1px 1px 2px 2px #888888');
}


/*
* Function to change the current location of user and update the new address in Database
*/
function fillUserAddress() {
    $("#waitingAddress").show();
    $("#useNewAddress").attr('disabled','disabled');
    $("#useNewAddress").hide();
    $("#useAddress").hide();
    $("#cboxClose").trigger('click');
    userLatitude  = commonMap2.centerPoint.lat();
    userLongitude = commonMap2.centerPoint.lng();
    centerPoint   = commonMap2.centerPoint;

     $.ajaxSetup({async:false});
        $.post(
    		baseUrl+"home/change-address",
            {
                    address:commonMap2.centerData,
                    latitude:userLatitude,
                    longitude:userLongitude
            },
            function(response){
               response =  JSON.parse(response);
               $("#userDiv").find('p').each(function(){
                    if($(this).attr('class')=='loc'){
                        $(this).html(commonMap2.centerData);
                    }
               })
    		   initialize();	   
    		},
            'html'
       );
      $("#waitingAddress").hide();
      $.ajaxSetup({async:true});
  }


/*
* Function to change the current location of user and update the new address in Database
*/
function fillPostLocationAddress(){
    $("#waitingAddress").show();
    $("#useNewAddress").attr('disabled','disabled');
    $("#useNewAddress").hide();
    $("#useAddress").hide();
    $("#cboxClose").trigger('click');
        userLatitude  = commonMap2.centerPoint.lat();
        userLongitude = commonMap2.centerPoint.lng();
        centerPoint   = commonMap2.centerPoint;

     var news = $.trim($('#newsPost').val());
         news = news.replace("http://", " "); 
         news = news.replace("https://", " "); 
  
   if(news != '' && news){
     addNews();
     $("#waitingAddress").hide(); 
     initialize();
    }
    else {
        $("#waitingAddress").hide(); 
        alert('Messagebox can not be blank');
    }
   
 }

/*
* Function to change the current location of user
*/
function fillAddress() {
	$("#cboxClose").trigger('click');
    userLatitude = commonMap2.changedLatitude;
    userLongitude = commonMap2.changedLongitude;
    centerPoint = new google.maps.LatLng(userLatitude,userLongitude);
    initialize();
    
}

/*
* Function to changes the link visibility
* @param {link object} link object whose visibility have to change
*/
function selectLink(thisOne,flag){
    $("#links a").each(function(){
        if(flag){
            thisOne = this;
            flag = false;
        }
        $(this).removeAttr('class');
    });
    $(thisOne).attr('class','blue');
    
}

/*
* Function to control the visibility of news shown on the map
* @param {type,id} type={prev,next} prev for previous news and next for next news ,
* id contains the current id of the div
*/
function showHideDiv(type,bubbleCounter){
        showHideCounter = $("#mainContent_"+bubbleCounter).attr('currentDiv');
        $("#content"+bubbleCounter+"_1").hide();
        $("#content"+bubbleCounter+"_"+showHideCounter).hide();
        if(type == 'next') {
            $("#prevDiv_"+bubbleCounter).show(); //.show() in previous version
            showHideCounter++;
            if(showHideCounter < Number($("#mainContent_"+bubbleCounter).attr('totalDiv'))){
                $("#content"+bubbleCounter+"_"+showHideCounter).show();
            } else {
               $("#nextDiv_"+bubbleCounter).hide(); 
               showHideCounter = Number($("#mainContent_"+bubbleCounter).attr('totalDiv'));
               $("#content"+bubbleCounter+"_"+showHideCounter).show();
            }
           
        } else {
            $("#nextDiv_"+bubbleCounter).show(); //.hide() in previous version
            showHideCounter--;
            if(showHideCounter > 1){
                 $("#content"+bubbleCounter+"_"+showHideCounter).show();
            } else {
                $("#prevDiv_"+bubbleCounter).hide(); 
                showHideCounter = 1;
                $("#content"+bubbleCounter+"_"+showHideCounter).show();
            }
        }
        if(bubbleCounter == 0){
            updatedContentId = showHideCounter;
        }
        $("#mainContent_"+bubbleCounter).attr('currentDiv',showHideCounter);
}

/* Function to chack the value length of text area and remove the extra characters
* @param val (value of text area), textLimit(Lenght of characters of text area) 
* @return null
*/

function textLimit(textLimit, thisone) {
	var text = $(thisone).val();
	if(text.length <= textLimit) {
		return true;
	} else {		
		$(thisone).val("");
		var newVal = "";
		for(var i = 0; i<textLimit; ++i) {
			newVal += text[i];
		}
		$(thisone).val(newVal);
		alert("Sorry! You can not enter more then "+textLimit+" charactes.");
		return false;
	}
}

/*
* Function to initialize the map silider
*/      
function sliderInitialization(){
	$("#slider")
		.slider({
			max: 1.5,
			min: 0.5,
			step: 0.1,
			value: getRadius(),
			animate: true
		})
		.bind("slidestop", function(event, ui){
			commonMap.onMapDragen('CENTER');
		})
		.bind("slide", function(event, ui){
			$("#radious").html(ui.value == 1 ? '1.0' : ui.value);
		});
}

function searchloactionOnMap(type) {
	var searchaddr = $("#searchAddres").val();

	if(searchaddr == ""){
		$("#searchAddres").addClass("inputErrorBorder");
		return;
	} else {
		$("#searchAddres").removeClass("inputErrorBorder");
	}

	var geocoder;
	var searchLat, searchLng; 
	geocoder = new google.maps.Geocoder();
	var sAddress = "";
	if(searchaddr) {
		sAddress += searchaddr+', ';
	}

	geocoder.geocode( { 'address': sAddress}, function(results, status) { 
		if (status == google.maps.GeocoderStatus.OK) {
			searchLat = results[0].geometry.location.lat();
			searchLng = results[0].geometry.location.lng();
			if(type == "editProfile") {
				relatedMap(searchLat, searchLng);
			} 
		}
		else{
			alert("Sorry! We are unable to find this location.");
			$("#searchAddres").val("");
		}
		
	});
}

function disabledEventPropagation(event)
{
   if (event.stopPropagation){
       event.stopPropagation();
   }
   else if(window.event){
      window.event.cancelBubble=true;
   }
}
var newImg;
var curHeight;
var curWidth;
function getImgSize(imgSrc)
{
	newImg = new Image();
	newImg.src = imgSrc;
	curHeight = newImg.height;
	curWidth = newImg.width;
}


function showHideDivContent(type,bubbleCounter){
    var me = commonMap;
    showHideCounter = $("#mainContent_"+bubbleCounter).attr('currentDiv');
        if(type == 'next') {
            $("#prevDiv_"+bubbleCounter).show(); //.show() in previous version
            showHideCounter++;
            
            if(showHideCounter < Number(me.bubbleArray[bubbleCounter].contentArgs.length)){
                var content = me.createOtherContent(me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][0],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][1],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][2],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][3],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][4],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][5],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][6],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][7],true);
                me.bubbleArray[bubbleCounter].currentNewsId = me.bubbleArray[bubbleCounter].newsId[showHideCounter-1];
                $("#content"+bubbleCounter+"_1").html(content);
                $("#detail_"+bubbleCounter).html("<a href='/info/news/nwid/"+me.bubbleArray[bubbleCounter].newsId[showHideCounter-1]+"'>Detail</a>");
            } else {
               $("#nextDiv_"+bubbleCounter).hide(); 
               showHideCounter = Number(me.bubbleArray[bubbleCounter].contentArgs.length);
               var content = me.createOtherContent(me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][0],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][1],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][2],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][3],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][4],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][5],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][6],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][7],true);
                me.bubbleArray[bubbleCounter].currentNewsId = me.bubbleArray[bubbleCounter].newsId[showHideCounter-1];
                $("#content"+bubbleCounter+"_1").html(content);
                $("#detail_"+bubbleCounter).html("<a href='/info/news/nwid/"+me.bubbleArray[bubbleCounter].newsId[showHideCounter-1]+"'>Detail</a>");
            }
           
        } else {
            $("#nextDiv_"+bubbleCounter).show();
            showHideCounter--;
            if(showHideCounter > 1){
                 var content = me.createOtherContent(me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][0],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][1],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][2],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][3],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][4],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][5],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][6],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][7],true);
                 me.bubbleArray[bubbleCounter].currentNewsId = me.bubbleArray[bubbleCounter].newsId[showHideCounter-1];
                $("#content"+bubbleCounter+"_1").html(content);
                $("#detail_"+bubbleCounter).html("<a href='/info/news/nwid/"+me.bubbleArray[bubbleCounter].newsId[showHideCounter-1]+"'>Detail</a>");
            } else {
                $("#prevDiv_"+bubbleCounter).hide(); 
                showHideCounter = 1;
                var content = me.createOtherContent(me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][0],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][1],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][2],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][3],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][4],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][5],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][6],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][7],true);
                me.bubbleArray[bubbleCounter].currentNewsId = me.bubbleArray[bubbleCounter].newsId[showHideCounter-1];
                $("#content"+bubbleCounter+"_1").html(content);
                $("#detail_"+bubbleCounter).html("<a href='/info/news/nwid/"+me.bubbleArray[bubbleCounter].newsId[showHideCounter-1]+"'>Detail</a>");
            }
        }
        if(bubbleCounter == 0){
            updatedContentId = showHideCounter;
        }
        $("#mainContent_"+bubbleCounter).attr('currentDiv',showHideCounter);
        popupCloseTimer = 0;
}

function array_unshift_assoc($arr, $key, $val) 
{ 
    $temp = new Array($val);
    for(i in $arr){
        $temp.push($arr[i]);
    }
    return $temp;
}

function changeHomeLocation(flag) {
	var userAddress = '';
	var searchaddr = $("#searchAddresHome").val();
	if(searchaddr == ""){
		$("#searchAddresHome").addClass("inputErrorBorder");
		return;
	} else { 
		$("#searchAddresHome").removeClass("inputErrorBorder");
	}
	if(searchaddr) {
		userAddress += searchaddr;
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
						   relatedMap(latlng.lat(), latlng.lng());
					   }
                    }
                } else {
					alert("Sorry! We are unable to find this location.");
					$("#searchAddresHome").val("");
				}
            } 
        );
    } else {
        alert("Sorry! We are unable to find this location.");
		$("#searchAddresHome").val("");
	}
}
function changepostLocation(flag) {
    var userAddress = '';
    var searchaddr = $("#Change-searchAddresHome").val();
    if(searchaddr == ""){
        $("#Change-searchAddresHome").addClass("inputErrorBorder");
        return;
    } else { 
        $("#Change-searchAddresHome").removeClass("inputErrorBorder");
    }
    if(searchaddr) {
        userAddress += searchaddr;
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
                           relatedMap(latlng.lat(), latlng.lng());
                       }
                    }
                } else {
                    alert("Sorry! We are unable to find this location.");
                    $("#Change-searchAddresHome").val("");
                }
            } 
        );
    } else {
        alert("Sorry! We are unable to find this location.");
        $("#Change-searchAddresHome").val("");
    }
}

function getRadius(){
	var radius = Number($("#radious").html());

	if (radius > 0){
		return radius;
	}

	return 0.8;
}
