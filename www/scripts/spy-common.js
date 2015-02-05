var commonMap;
var circleSize = "0.8";
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
var globalStart=null;
var otherProfileExist = 0;
var isScrollable  = 1;
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
  	latestNews(userLatitude,userLongitude,'INITIAL');
    
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
var commonMap;


function profileMap(lat,lng,type,dataToShow){
    var profileImage = '';
     if(dataToShow){
        profileImage = dataToShow.userImage;
     } else {
        profileImage = 'http://www.herespy.com/www/images/img-prof40x40.jpg';
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
        profileImage = 'http://www.herespy.com/www/images/img-prof40x40.jpg';
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


var windowScrollingAgent = 0;
var scrollingCounter = 15;
function loadMorePosts()
{  
  if(windowScrollingAgent) {
     
     scrollingCounter += 15;  
     nextPage(scrollingCounter,"ALL");
     
  } else {
    
    nextPage(15,"ALL");
    windowScrollingAgent = 1;
  }
 
  $(window).bind('scroll', bindScroll);
}


 function bindScroll(){
   if($(window).scrollTop() + $(window).height() > $(document).height() - 100) {
       $(window).unbind('scroll');
        loadMorePosts();
   }  
}
 
$(window).scroll(bindScroll);


function changeMapCenter(brng,dist){
    var centerOfMap = (commonMap.map).getCenter();
    map.setCenter(centerOfMap.findPoint(brng,dist));
    commonMap.onMapDragen('CENTER');
}

/*******************************************************************************************************************************************/
var searchTxt = '';
function getAllNearestPoint(latitude,longitude,radious,circleFlag){
    showNewsScreen();
    removePaginationDiv();
    searchTxt = '';
    $("#newsData").html("");    
    if(searchTxt == $("#searchText").val()){
        commonMap.flushMap('CENTER');
    }
    if(!radious){
       circleSize = radious = (Number($("#radious").html()) > 0)?(Number($("#radious").html())):0.8;
       if(circleFlag){
           if(commonMap.circle){
                latitude = commonMap.circle.getCenter().lat();
                longitude = commonMap.circle.getCenter().lng();
           }
       }
    }

    var url = baseUrl+"home/get-nearby-points";
	 $.ajaxSetup({async:true});
        $.post(
            url,
            {
                'user_id':user_id,	
                'latitude': latitude,
                'longitude':longitude,
                'radious':radious,
                'search_txt':searchTxt,
                'fromPage':0,
                'endPage':16
            },
            function(obj){
	        hideNewsScreen();
                obj = JSON.parse(obj);
                var flag = true;
                var resultObj = obj.result;
                var timing = obj.timing;
                
              
                $("#newsData").html(obj.html);
                $("#waitingBar").remove();
                if(obj.paging==0){
                 //do nothing
                } else {
                    var pagingDiv = '<div id="pagingDiv"><div align="center" onclick=nextPage(15,"ALL") class="postClass"><b>Older Posts</b></div></div>';
                $("#newsData").append(pagingDiv);
                }
                paginationResultObject = resultObj;
                var NoOfResult = 0;
                var bubblePocketNumber = 1;
               
                if(obj.size==0){

                } else {
                    isScrollable = 1;
                }
               
               	for(i in resultObj) {
               	    var markerIcon = UserMarker1;
               	    if(distance(new google.maps.LatLng(userLatitude,userLongitude),new google.maps.LatLng(resultObj[i][0]['latitude'],resultObj[i][0]['longitude']),'F') != 0){
               	        commonMap.createMarkersOnMap(resultObj[i],bubblePocketNumber++,markerIcon);
                    } else {
                        commonMap.createMarkersOnMap(resultObj[i],0,markerIcon);
                    }
               	}
                    $(".Image-Popup-Class").colorbox({width:"60%",height:"80%", inline:true, href:"#Image-Popup"},function(){$.colorbox.resize({width:imageWidth+'px' , height:imageHeight+'px'});});  
                    if($("#newsData").height()>714)
                    setThisHeight(Number($("#newsData").height())+100);
            },
            "html"
        )
	 	if($('#searchText').val()) {
			searchData('0',true,'selected',false);
		}
        $.ajaxSetup({async:false});
 
}



function nextPage(pageNumber,filter){
    globalStart= pageNumber; 
    showLoadingScreen();
    removePaginationDiv();
    var searchTxt = '';

    circleSize = radious = (Number($("#radious").html()) > 0)?(Number($("#radious").html())):0.8;
    if(commonMap.circle){
        latitude  = commonMap.circle.getCenter().lat();
        longitude = commonMap.circle.getCenter().lng();
    }
    
    var searchText = filter;
    var url = baseUrl+"home/search-nearest-news";
    if(filter == 'ALL'){
        url = baseUrl+"home/get-nearby-points";
        searchText = "";
    }
    var totalDiv = ($("#newsData div[class=scrpBox]").length);
    var divToFocus = null;
    $("#newsData div:nth-child("+(totalDiv-1)+")").find('ul').each(function(){
        if(!divToFocus)
            divToFocus = $(this).attr('id');
    });
    
	$.ajaxSetup({async:false});
        $.post(
            url,
            {
                'user_id':user_id,	
                'latitude': latitude,
                'longitude':longitude,
                'radious':radious,
                'search_txt':"",
                'filter':searchText,
                'fromPage':pageNumber,
                'endPage':16
            },
            function(obj){
                hideNewsScreen();
                obj = JSON.parse(obj);
                var flag = true;
                var resultObj = "";
                if(filter == 'ALL')
                    resultObj = obj.result;
                else
                   resultObj = obj.markerData;  
                   var timing = obj.timing;
                $("#waitingBar").remove();
               	$("#newsData").append(obj.html);
            
                pageNumber = pageNumber+15;
                if(obj.size==0){
                
                } else {
                    isScrollable = 1;
                }
               
                if(obj.size>15)
                 var pagingDiv = '<div id="pagingDiv" <div align="center" onclick=nextPage('+pageNumber+',"'+filter+'") class="postClass"><b>Older Posts</b></div></div>';
                 $("#newsData").append(pagingDiv);
                 paginationResultObject = resultObj;
                 var NoOfResult = 0;
                 var bubblePocketNumber = 1;
               
               	for(i in resultObj) {
               	    var markerIcon = UserMarker1;
               	    if(distance(new google.maps.LatLng(userLatitude,userLongitude),new google.maps.LatLng(resultObj[i][0]['latitude'],resultObj[i][0]['longitude']),'F') != 0){
               	        commonMap.createMarkersOnMap(resultObj[i],bubblePocketNumber++,markerIcon);
                    } else {
                            commonMap.createMarkersOnMap(resultObj[i],0,markerIcon);
                    }
               	} 
				$(".Image-Popup-Class").colorbox({width:"60%",height:"80%", inline:true, href:"#Image-Popup"},function(){$.colorbox.resize({width:imageWidth+'px' , height:imageHeight+'px'});});  
				if($("#newsData").height()>714)
                     setThisHeight(Number($("#newsData").height())+100);
            },
            "html"
         )
	 	if($('#searchText').val()) {
			searchData('0',true,'selected',false);
		}
                
         if(divToFocus){
             $.scrollTo( "#"+divToFocus,0, {offset: {top:-0, left:-100} }  ); 
         }
      	 $.ajaxSetup({async:false});
         return false;
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
    textBoxHeight = 1;
    var news = $.trim($('#newsPost').val());
    news = news.replace("http://", " "); 
    news = news.replace("https://", " "); 
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
    				          var formattedAddress = results[0].formatted_address;
                              if($('#fileNm').html() != ""){  
                                  $("#tempContainor").html(formContainor); 
                                  $("#mainForm").attr('action',baseUrl+'home/add-news?news='+news+'&latitude='+userLatitude+'&longitude='+userLongitude+'&address='+formattedAddress); 
                                  $("#mainForm").submit();
                                  $('#loading').hide();
                                  $('#postOptionId').hide();
                                  $('#newsPost').attr('placeholder','Share something from your current location'); 
                                       } else { 
                                                $.post(baseUrl+'home/add-news',
                                                       {'news': news,'latitude': userLatitude,'longitude': userLongitude, 'address': formattedAddress}, 
                                                       showAddNews, 
                                                       "html");
                                             $('#postOptionId').hide();
                                             $('#newsPost').attr('placeholder','Share something from your current location'); 
                                             } 
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
function latestNews(latitude,longitude,type){
   
    $.ajaxSetup({async:false});
    var url = baseUrl+"home/get-latest-news";
    $.post(
        url,
        {
            'user_id':user_id,
            'latitude': latitude,
            'longitude':longitude	
        },
        function(obj){
            obj = JSON.parse(obj);
            var flag = true;
            var resultObj = obj.result;
            var dataToReturn = null; 
            if(resultObj.length) {
              	for(i in resultObj) {
              	 if(resultObj[i]['user_id'] == user_id){
              	    if((Number(userLatitude).toFixed(4) == Number(resultObj[i]['latitude']).toFixed(4))&&(Number(userLongitude).toFixed(4)==Number(resultObj[i]['longitude']).toFixed(4))){
        				dataToReturn = {name:resultObj[i]['Name'],id:0,news:"This is me!",userImage:resultObj[i]['Profile_image'],user_id:resultObj[i]['user_id']};
                        break;
                    }
                  }  
                }
            } 
           
            if(!dataToReturn) {
               dataToReturn = {name:userName,id:0,news:"This is me!",userImage:imagePath,user_id:user_id};
            }
           console.log("news");
            if(type == 'INITIAL') {
                console.log("initial");
    	        commonMap = new MainMap({
                       mapElement : document.getElementById('map_canvas'),
                       centerPoint : centerPoint,
                       icon:MainMarker1,
                       centerData:dataToReturn,
                       mapType:"MAIN",
                       markerType:'nonDragable',
                       isMapDragable:'dragable',
                       showMapElement:true
                });
				
            } else {
                commonMap.createMarker(centerPoint,MainMarker1,'center',dataToReturn);
            }
                        getAllNearestPoint(userLatitude,userLongitude,0.8,false);             
			
        },
        "html"
    )  
    $.ajaxSetup({async:true});
}

function reinitializeCenterData(latitude,longitude){
    $.ajaxSetup({async:false});
    $(document).find("#mainContent_0").each(function(){
           $(this).attr('currentDiv',1);
    })
    var url = baseUrl+"home/get-latest-news";
    $.post(
        url,
        {
         'user_id':user_id,
         'latitude': latitude,
         'longitude':longitude	
        },
        function(obj){
            obj = JSON.parse(obj);
            var flag = true;
            var resultObj = obj.result;
            var markerContent = null; 
            
            if(resultObj.length) {
              	for(i in resultObj) {
              	 if(resultObj[i]['user_id'] == user_id){
              	    if((Number(userLatitude).toFixed(4) == Number(resultObj[i]['latitude']).toFixed(4))&&(Number(userLongitude).toFixed(4)==Number(resultObj[i]['longitude']).toFixed(4))){
                        markerContent = {
                            name:resultObj[i]['Name'],
                            id:0,news:"This is me!",
                            userImage:resultObj[i]['Profile_image'],
                            user_id:resultObj[i]['user_id']
                        };
                          break;
                    }
                  }  
                }
            } 
           
            if(!markerContent) {
               markerContent = {name:userName,id:0,news:"This is me!",userImage:imagePath,user_id:user_id};
            }
             var me = commonMap;
             var currentBubble = (me.bubbleArray[0]);
             if(currentBubble){
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
   );
}

function toggleBounce(newsId,user_id){
    commonMap.searchNewsMarker(newsId,'bounce');
}

function stopBounce(newsId,user_id){
    commonMap.searchNewsMarker(newsId,'stopbounce');
}

/*
* Function to 
* @param {CONTENT ARRAY,CURRENT NEWS WHICH IS FOCUSED,DATA TO SHOW,NEWS ID, TYPE(ADDED,PREVIOUS))}
*/
function showAddNews(obj){
    initFlagForMap = null;
    
    var jsdata = $.parseJSON(obj);
        textBoxHeight = 1;
        var news = $.trim($('#newsPost').val().replace(/\n/g,"<br>"));
        var html = '<div id="scrpBox_'+jsdata.id+'" class="scrpBox" onmouseover="toggleBounce('+jsdata.id+','+user_id+');" onmouseout="stopBounce('+jsdata.id+','+user_id+');">'+
                        '<ul class="myScrp afterClr" id="newsDiv'+jsdata.id+'">'+ 
                            '<div class="deteleIcon">'+
                                '<img class="crp" style="float:right;margin-right: 7px;" onclick="deletes(this, \'news\', '+jsdata.id+');" src="'+baseUrl+'www/images/delete-icon.png">'+'</div><div class="deleteIcon12">'+
                                '<img class="crp12" style="margin-top: 17px;right: 14px;position: absolute;cursor: pointer;width: 23px;" onclick="showAlert();" src="'+baseUrl+'www/images/thumsup_voted_gray.png">'+
                            '</div>'+
    					    '<li>'+
                                '<ul class="post">'+
                                     '<li class="imgPro">'
    	                                if (jsdata.image) { 
    		                                if(jsdata.image.indexOf('://') > 0) {
    			                                html += '<a href="'+baseUrl+'home/profile/user/'+user_id+'"><img src="' + jsdata.image + '"  class="smallImage"/></a>';
    		                                }else {
    			                                html += '<a href="'+baseUrl+'home/profile/user/'+user_id+'"><img src="'+baseUrl+'uploads/' + jsdata.image + '"  class="smallImage"/></a>';
    		                                }
    	                                }else html += '<a href="'+baseUrl+'home/profile/user/'+user_id+'"><img src="'+baseUrl+'www/images/img-prof40x40.jpg"  class="smallImage"/></a>';
    						     
                                     html+='</li>';
                                 
                                     html+='<li>'+
                                        '<div class="post-top">'+ 
                                        
                                            '<a class="title" href="'+baseUrl+'home/profile/user/'+user_id+'">'+userName+'</a>'+
                                            '<span class="post-date">'+ 'Just now' + '</span>'+ 
                                            '<span class="message_number">0</span>'+
                                        
                                        '</div>';
                                     
                                     html+='<div class="post-bottom test">';
                                            html+= "<p>" + linkClickable(news.substring(0,350)) + "</p>";
                                            if(news.length>350) {
                                                 html+='<span><a id="moreButton_'+jsdata.id+'" href="javascript:void(0)" onclick="showMoreNews(this,'+jsdata.id+')">....More</a><span id="morecontent_'+jsdata.id+'" style="display:none">'+linkClickable(news.substring(350,news.length))+'</span></span>';
                                            } 
                                     
                                     html+='</p></div></li><li class="clr"></li>'; 
                                 
                                html+='</ul>';
                                html+='<li class="cmnt">';
                                if(jsdata.news){
                                    if((jsdata.news).images) {
                                          
                                                                     var imageUplaoded = baseUrl+'newsimages/'+(jsdata.news).images;
                                            html+='<div><img onclick="openImage(\''+(jsdata.news).images+'\', '+jsdata.source_image_width+', '+jsdata.source_image_height+');" class="Image-Popup-Class" src="'+imageUplaoded+'" style="width: 100%; height: auto;cursor: pointer;"></div>'; 
                                        };
                                    };
                                             
                                html+='</li>';
                             html+='<div class="dur">'+
								 '<span class="floatR">'+
                                   '<span style="position:block;float:left;">Share this Post:</span>'+
                                   '<a class="crp"  onclick="fbshare(\'scrpBox_'+jsdata.id+'\',\''+baseUrl+'tbnewsimages/'+(jsdata.news).images+'\',\''+(jsdata.news).news+'\',\''+baseUrl+'info/news/nwid/'+jsdata.id+'\')">'+
                                    '<img style="width:25px; height:25px;" src="'+baseUrl+'www/images/facebook.png" />'+
                                   '</a>'+                                   
                                   '<a class="crp Message-Popup-Class cboxElement" onclick="clearErrors();">'+
                                    '<img style="width:25px; height:25px;" src="'+baseUrl+'www/images/email.png" />'+
                                   '</a>'+
                                 '</span><div class="clr"></div>'+
                             '</div>'+
			                 '<ul id="comment_list_'+jsdata.id+'"'+ 'class="cmntRow afterClr"' +'>'+
    					        '<ul id="commentTextarea_'+jsdata.id+'" class="cmntList-last afterClr" style="margin-top:0px; border-top:none;">'+
                                    '<li id="tempDiv_'+jsdata.id+'"'+ 'style="padding:0;"'+ '>'+
                                        '<ul class="inpCmnt afterClr">'+
                                            '<span style="margin-bottom: 0px; float: right; margin-right: 131px; margin-top: 9px; display: none;">'+
                                                '<img id="rpl_loading_'+jsdata.id +'src="'+baseUrl+'www/images/wait.gif">'+
                                            '</span>'+
                                            '<li class="imgPro">';
                                                if (jsdata.image) { 
                            		                if(jsdata.image.indexOf('://') > 0) {
                            			                html += '<img src="' + jsdata.image + '"   class="smallImage"/>';
                            		                }else {
                            			                html += '<img src="'+baseUrl+'uploads/' + jsdata.image + '"   class="smallImage"/>';
                            		                }
                            	                }else html += '<img src="'+baseUrl+'www/images/img-prof40x40.jpg"   class="smallImage"/>';
                                            html+='</li>'+
                                
                                            '<li class="inpCmnt-last">'+
    							                '<textarea id="comment'+jsdata.id+'" class="textAreaClass" onkeydown="textLimit(250, this);" onkeyup="textLimit(250, this);resizeArea('+jsdata.id+');" placeholder="Write comments..."></textarea>'+
    						                '</li>'+
                                            '<div class="clr"></div>'+
                                        '</ul>'+
                                    '</li>'+
                                '</ul>'+
    					     '</ul></li></ul>'
    				'</div>';
        	$("#newsData").prepend(html);
        	$('#newsPost').val('');
                $('#newsPost').css('height','36');
          	$('#noNews').html('');
    		$('#newsWaitOnAdd').hide();
    		$(".Image-Popup-Class").colorbox({width:"60%",height:"80%", inline:true, href:"#Image-Popup"},function(){$.colorbox.resize({width:imageWidth+'px' , height:imageHeight+'px'});});
                $(".login-popup-class").colorbox({width:"26%",height:"32%", inline:true, href:"#login-id"});
                $(".Message-Popup-Class").colorbox({width:"40%",height:"45%", inline:true, href:"#Message-Popup_Email"},function(){$('html, body').animate({ scrollTop: 0 }, 0);});
    		$('html, body').animate({ scrollTop: 0 }, 0);

        var plusHeight = 0
        if((jsdata.news).images) {
            plusHeight = Number(jsdata.source_image_height);
        }
         if($("#newsData").height()>714)
                    setThisHeight(Number($("#newsData").height())+100+plusHeight);
       if($("#news0_1").html() == "This is me!"){
              changeNewsContent(0,1,jsdata,news,'FIRST');
       } else {
            
            if(commonMap.findData(commonMap.bubbleArray[0].user_id,user_id,'CHECK')){
                news = concatinateNews(news,jsdata.id);
                commonMap.updateContent(commonMap.createContent(jsdata.image,userName,news,++(commonMap.bubbleArray[0]).total,"second",0,jsdata.id),0,(commonMap.bubbleArray[0]).total,jsdata.id);
                $("#contentUpdator").html(commonMap.bubbleArray[0].divContent);
                updatedContentId = (commonMap.bubbleArray[0]).total;
                commonMap.bubbleArray[0].divContent = "";
                commonMap.toggleContent(0,Number((commonMap.bubbleArray[0]).total),updatedContentId);
                UpdatedContent = commonMap.updateContent($("#contentUpdator").html(),0,Number((commonMap.bubbleArray[0]).total),jsdata.id);
                if((currentIdOfMarker==0)&&(previousBubble)){
                    previousBubble.setContent(UpdatedContent);
                }
                $("#contentUpdator").html("");
                
            } else {
                changeNewsContent(0,1,jsdata,news,'ADDED');
            }
      }
      
      $.ajax({
		url : baseUrl+'home/news-pagging',
		type : 'post',
		data : {lat : userLatitude, lng : userLongitude},
		success : function(data) {
			removePaginationDiv();
			var data = $.parseJSON(data);
			$("#waitingBar").remove();
			if(data.paging) {
	        	var pagingDiv = '<div id="pagingDiv">'+data.paging+'</div>';
				$("#newsData").append(pagingDiv);
			}
		}
	 });
	 clearUpload();
     reinitilizeUpload();
     location.reload();
}

/*@ function for alert  */
function showAlert(){
    alert('You can not vote your own post.');
}

/*
* Function to change news content when new news is posted
* @param {bubbleArrayIndex : index of the div where news has to change,
*  newsPosition : position of the news,
*  jsdata : object of news which is to be replaced with new content
*  news : news content,
*  type : type of the content}
*/
function changeNewsContent(bubbleArrayIndex,newsPosition,jsdata,news,type){
     $(document).find("#mainContent_0").each(function(){
        if($(this).html()==""){
            $(this).remove();
        };
     })
     $("#contentUpdator").html(commonMap.bubbleArray[bubbleArrayIndex].divContent);
     var UpdatedContent = '';
     if((currentIdOfMarker==0)&&(previousBubble)){
        news = concatinateNews(news,jsdata.id);
        $("#userName"+bubbleArrayIndex+"_"+newsPosition).html(userName);
        $("#news"+bubbleArrayIndex+"_"+newsPosition).html(news);
        UpdatedContent = commonMap.changeContent($("#mainContent_"+bubbleArrayIndex).html(),bubbleArrayIndex,Number((commonMap.bubbleArray[bubbleArrayIndex]).total),jsdata.id);
     } else {
        news = concatinateNews(news,jsdata.id);
        $("#userName"+bubbleArrayIndex+"_"+newsPosition).html(userName);
        $("#news"+bubbleArrayIndex+"_"+newsPosition).html(news);
        UpdatedContent = commonMap.changeContent($("#contentUpdator").html(),bubbleArrayIndex,Number((commonMap.bubbleArray[bubbleArrayIndex]).total),jsdata.id);
     }
    var currentBubble = commonMap.bubbleArray[0];
    var arrowFlag = false;
    if(currentBubble.contentArgs[0][2] == 'This is me!'){
        currentBubble.newsId = new Array()
        currentBubble.newsId.push(jsdata.id);
        currentBubble.user_id = new Array()
        currentBubble.user_id.push(user_id);
        currentBubble.contentArgs[0] = [jsdata.image,userName,news,1,'first',0,jsdata.id];
    }else{
        arrowFlag = true;
        currentBubble.newsId = array_unshift_assoc(currentBubble.newsId,0,jsdata.id);
        currentBubble.user_id = array_unshift_assoc(currentBubble.user_id,0,user_id);
        currentBubble.total++;
        currentBubble.contentArgs = array_unshift_assoc(currentBubble.contentArgs,0,[jsdata.image,userName,news,1,'first',0,jsdata.id]);
    }
    currentBubble.currentNewsId = jsdata.id;
    var content = commonMap.createOtherContent(jsdata.image,userName,news,1,'first',0,jsdata.id);
    $("#mainContent_0").attr('currentDiv',1);
    $("#content0_1").html(content);
    $("#detail_0").html("<a href='http://here-spy/info/news/nwid/"+jsdata.id+"'>Detail</a>");
    $("#prevDiv_0").hide();
    if(type != 'FIRST')
        $("#nextDiv_0").show();
    else
        $("#nextDiv_0").hide();
    $("#contentUpdator").html("");
    
}


function changeBubbleContent(bubbleArrayIndex,newsPosition,jsdata,news,type){
    var currentBubble = commonMap.bubbleArray[0];
    currentBubble.newsId = array_unshift_assoc(currentBubble.newsId,0,jsdata.id);
    currentBubble.user_id = array_unshift_assoc(currentBubble.user_id,0,jsdata.user_id);
    currentBubble.total++;
    currentBubble.contentArgs = array_unshift_assoc(currentBubble.contentArgs,0,[jsdata.images,jsdata.Name,jsdata.news,1,'first',0,jsdata.id]);
    var content = commonMap.createOtherContent(currentBubble.contentArgs[0][0],currentBubble.contentArgs[0][1],currentBubble.contentArgs[0][2],currentBubble.contentArgs[0][3],currentBubble.contentArgs[0][4],currentBubble.contentArgs[0][5],currentBubble.contentArgs[0][6],currentBubble.contentArgs[0][7],false);
    $("#mainContent_"+bubbleCounter).attr('currentDiv',0);
    $("#content0_1").html(content);
}

/*
* Function to concatinate news when length increases more then 100
* @param {news : news string, newsId : id of the news}
*/
function concatinateNews(news,newsId){
    var content = news.substring(0,100);
    if(news.length>100) {
        content += "..<span style='color:blue;cursor:pointer;' onclick='focusOnNews("+newsId+")'>More</span></span></div></div>";
    }
    return content;
}

/*
* Function to show handle the visibilite of next and
* previous button on the infobubble
* @param {buttonType : array of button,action : action perform over button array
* buttonIndex : index of next previous button}
*/
function hideShowPreviousNextButton(buttonType,action,buttonIndex){
    for(i in buttonType){
        $('#'+buttonType[i]+"_"+buttonIndex).css('display',action[i]);
    }
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
* Function to show more news and comments when clicked on 
* pagination button also includes new pagination
* @param {pageNumber : page number,filter : type of filter}
*/
function paging(pageNumber,filter){ 
         showResult(paginationResultObject,pageNumber);
}

/*
* Function to show more news and comments when first post a meassge than clicked on 
* pagination button also includes new pagination
* @param {res : pagination result object,pageNumber : current page number}
*/
function showResult(res,pageNumber) {
   var jsdata;
   var html 
    if(res){
    	$('#buttonPost').removeAttr("disabled");
        $('#newsPost').css('height','36');
        $('#newsPost').val('');
        
        var startPage = ((pageNumber-1)*15)+1;
        var endPage   = (pageNumber*15);
            pageNumber++;
        var paginationFlag = true;
        globalStart = globalStart+16;
        nextPage(globalStart,'ALL'); //globalStart variable is now assigned with value of startpage variable  from NextPage function just increment it by value of 16 points to correct result parsing after posting a post.
                                     // No need to execute following commented code
    }
}

/*
* Function to get show more news when
* clicked on the More.. link
* @param {thisOne : id of the div,
*   morecontentId : id of the span contains remaining news}
*/
function showMoreNews(thisOne,morecontentId){
    $(thisOne).remove();
    var newsParent = $("#morecontent_"+morecontentId).parent().parent();
    $(newsParent).html($(newsParent).html()+""+$("#morecontent_"+morecontentId).html());
    var height=Number($(newsParent).parent().parent().height())+Number($("#morecontent_"+morecontentId).height());
    $(newsParent).parent().parent().height(height-30);
    $("#morecontent_"+morecontentId).remove();
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
function getComments(newsId){
	$.post(
		baseUrl+"home/get-total-comments",
		{
			'news_id' : newsId
		},
		function(obj){
			obj = JSON.parse(obj);
			$("#comment_list_"+newsId).html(obj.comments);
			$("#comment_list_"+newsId).hide();
			$("#comment_list_"+newsId).fadeIn(1000);
		},
		"html"
	)	
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
		      	$.ajaxSetup({async:true});
                $.post(
					baseUrl+"home/add-new-comments",
					{
						'comments':comments, 'news_id':news_id, 'user_id' : user_id
					},
					function(obj){
					 	obj = JSON.parse(obj);
						$('#comment'+id).val('');
						$('#comment'+id).blur();

                        $('#rpl_loading_'+id).hide();
						
                        var commentArea = $("#commentTextarea_"+id).html();
                        if($("#tempDiv_"+id).html()!=null){
                            commentArea = $("#tempDiv_"+id).html();
                        }
						$("#commentTextarea_"+id).remove();
						var commentData = '<li id="comment_'+obj.commentId +'" class="cmntList afterClr">'+'<div class="imgPro">'
                                                         if(obj.image) {
                                                                if(obj.image.indexOf('://') > 0) {
                                                                    commentData += '<img src="' + obj.image + '/>';
                                                                }else {
                                                                    commentData += '<img src="' + baseUrl + 'uploads/' + obj.image + '"/>';
                                                                }
                                                            }else {
                                                                commentData += '<img src="' + baseUrl + 'www/images/img-prof40x40.jpg"/>';
                                                            }                                                     
                                                         commentData += '</div>'+
                                                    '<ul>'+
                                                        '<div class="deteleIcon">'+
                                                            '<img class="crp deteleIcon_img" src="' + baseUrl + 'www/images/delete-icon.png" onclick="deletes(this, \'comments\', '+obj.commentId+');" style="float:right;"/>'+
                                                        '</div>'+
                                                        '<li class="title">' +
                                                            '<div class="title-wrapper">'+ userName +':' +
                                                            '<span class="cmnt">'+
                                                                linkClickable(comments.substring(0,255));
                                                                if(comments.length>255) {comments
                                                                     commentData+='<a id="moreButton_'+obj.id+'" href="javascript:void(0)" onclick="showMoreComments(this,'+obj.commentId+')">....More</a><span id="morecomments_'+obj.commentId+'" style="display:none">'+comments.substring(255,comments.length)+'</span>';
                                                                }
                                                            commentData +='</span>'+'</div>'+   
						                                '</li>'+
                                                        '<li class="dur"><p>Just now</p></li>'+
                                                    '</ul><div class="clr"></div></li>';
                        
						if(thisone){ 
							$(thisone).parent().parent().parent().before(commentData);
							if($('#midColLayout')) {
								setThisHeight(Number($("#midColLayout").height()));
							}
							if($('#totalCommentView')) {
								$('#totalCommentView').html(Number($('#totalCommentView').html())+1);
							}
						} else { 
							if(!$("#comment_list_"+id).attr('class')){
								$("#comment_list_"+id).attr('class','cmntRow afterClr');
							}
							$("#comment_list_"+id).append(commentData);
							
							$("#comment_list_"+id).append("<li id='commentTextarea_"+id+"' class='cmntList-last afterClr'>"+commentArea+"</li>");
							$("#comment_list_"+id).show();							
						}
                        if($("#newsData").height()>714)
                                setThisHeight(Number($("#newsData").height())+100);
                        $('#comment'+id).removeAttr('disabled');
					},
					"html"
				)
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

/*
* Function to get the key value
*/
function myElapsedFunction(){
var delay = (function(){
  var timer = 0;
  return function(callback, ms){
    clearTimeout (timer);
    timer = setTimeout(callback, ms);
  };
})();

$('#searchText').keyup(function() {
    delay(function(){
    searchData('0',false,'selected',false);  
  }, 1000 );
});

}

  function searchLatest(){
     if(controller == 'home' && action == 'index') {
     searchData('0',false,'selected',false);  
     } else{
        window.location= baseUrl+'home/index/sv/'+$('#searchText').val();
     }
  }

function getKeycodes(direct) {
	var keycode = '';
	if(direct) {
		if(controller == 'home' && action == 'index') {
			searchData('0',false,'selected',false);
		} else {
			window.location = baseUrl+'home/index/sv/'+$('#searchText').val();
		}
	} else { 
		$('#searchText').bind('keyup', function(event) {
			if(controller == 'home' && action == 'index') {
				keycode = event.keyCode;
				if(keycode === 13){
				   searchData('0',false,'selected',false);
				}
			} else {
				keycode = event.keyCode;
				if(keycode === 13){
				    window.location = baseUrl+'home/index/sv/'+$('#searchText').val();
				}
			}
		});
	}
}

/*
* Function to search the data
* @param {user_id : id of the user, flag : {true,false} to detect radious,
* filter : type of the filter like userinterest, search etc..}
*/
function searchData(user_id,flag,filter,circleCenterFlag) {
    commonMap.clearMarkers();
    if(previousBubble){
        previousBubble.close();
    }
	if(searchTxt == $("#searchText").val()){
		commonMap.flushMap('CENTER');
	}
	var searchText = $("#searchText").val();
	searchText     = $.trim(searchText); 
	$('#loading').show();
	$('#newsData').html("");
    var radious = '';
    if(flag){
      radious  = (Number($("#radious").html()) > 0)?Number($("#radious").html()):0.8;  
    } 
    var url = baseUrl+"home/search-nearest-news/";
    var circleLat  = "";
    var circleLong = "";
    
    if(commonMap.circle){
        circleLat  = commonMap.circle.getCenter().lat();
        circleLong = commonMap.circle.getCenter().lng();
    } else {
       circleLat =  userLatitude;
       circleLong = userLongitude;
    }
    
    reinitializeCenterData(userLatitude,userLongitude);
	$.ajaxSetup({async:true});
        $.post(
            url,
            {
		'searchText' : searchText, 
                'latitude': circleLat, 
                'longitude' : circleLong, 
                'user_id' : user_id,
                'radious':radious,
                'filter':filter
            },
            function(obj){
		  $('#loading').hide();
                  obj = JSON.parse(obj);
		var flag = true;
                var resultObj = obj.markerData;
                var timing = obj.timing;
		paginationResultObject = resultObj;
                $("#newsData").html(obj.html);
                $("#waitingBar").remove();
	        if(obj.size>15)
                var pagingDiv = '<div id="pagingDiv" <div align="center" onclick=nextPage(15,"'+filter+'") class="postClass"><b>Older Posts</b></div></div>';
                $("#newsData").append(pagingDiv);
		$(".Image-Popup-Class").colorbox({width:"60%",height:"80%", inline:true, href:"#Image-Popup"},function(){$.colorbox.resize({width:imageWidth+'px' , height:imageHeight+'px'});});

				//Create markers of post
	        var bubblePocketNumber = 1;
               	for(i in resultObj) {
               	    var markerIcon = UserMarker1;
               	    if(distance(new google.maps.LatLng(userLatitude,userLongitude),new google.maps.LatLng(resultObj[i][0]['latitude'],resultObj[i][0]['longitude']),'F') != 0){
               	        commonMap.createMarkersOnMap(resultObj[i],bubblePocketNumber++,markerIcon);
                    } else {
                            commonMap.createMarkersOnMap(resultObj[i],0,markerIcon);
                    }
				}
		 
                    $("#addNewsDiv").hide();
		    $("#SearchParameter").show();
	            $("#searchTextDiv").html($("#searchText").val());
								
            },
            "html"
        )
		 $.ajaxSetup({async:true});
}

/*
* Function to set the focus on the selected news
* @param {divId : id of the div}
*/
function focusOnNews(divId){
  if($("#newsDiv"+divId)){
    $(document).scrollTo("#newsDiv"+divId);
    $("#moreButton_"+divId).hide();
    $("#morecontent_"+divId).show();
    $("#newsDiv"+divId).parent().css('box-shadow','1px 1px 2px 2px #888888');
  }
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
* Function to reinitialize map at its orignal lat lon
*/
function goToHome(){
	searchText = '';
    if(previousBubble)
        previousBubble.close();
    previousBubble = null;
    commonMap.map.setCenter(new google.maps.LatLng(userLatitude,userLongitude));
    commonMap.onMapDragen('CENTER'); 
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

/* Function to change the circle size and related points on the basis of slider
* @param null
*/

function changeCircle() { 
    newCenterPoint = commonMap.map.getCenter();
    commonMap.onMapDragen('CENTER');
    $("#slider").slider("enable");
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
    $("#slider").slider({max:1.5,min:.5,step:0.1,value:circleSize,animate: true}).bind( "slidestop", function(event, ui) { if(ui.value == 1)
            ui.value = "1.0";circleSize = Number(ui.value);$("#slider").slider("disable");changeCircle();});
    $("#slider").slider({max:1.5,min:.5,step:0.1,value:circleSize,animate: true}).bind( "slide", function(event, ui) {
		$("#loading").show();																								  
        if(ui.value == "1") {
            ui.value = "1.0";
        }
        $("#radious").html(ui.value); 
        circleSize = Number(ui.value);
    }); 
}


function hideMoreButton(thisone) {
    $(thisone).html("");
    $(thisone).parent().css('color','black');
    $(thisone).parent().find('span').each(function(){
       $(this).show();
    })
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
                $("#detail_"+bubbleCounter).html("<a href='http://here-spy/info/news/nwid/"+me.bubbleArray[bubbleCounter].newsId[showHideCounter-1]+"'>Detail</a>");
            } else {
               $("#nextDiv_"+bubbleCounter).hide(); 
               showHideCounter = Number(me.bubbleArray[bubbleCounter].contentArgs.length);
               var content = me.createOtherContent(me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][0],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][1],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][2],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][3],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][4],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][5],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][6],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][7],true);
                me.bubbleArray[bubbleCounter].currentNewsId = me.bubbleArray[bubbleCounter].newsId[showHideCounter-1];
                $("#content"+bubbleCounter+"_1").html(content);
                $("#detail_"+bubbleCounter).html("<a href='http://here-spy/info/news/nwid/"+me.bubbleArray[bubbleCounter].newsId[showHideCounter-1]+"'>Detail</a>");
            }
           
        } else {
            $("#nextDiv_"+bubbleCounter).show();
            showHideCounter--;
            if(showHideCounter > 1){
                 var content = me.createOtherContent(me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][0],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][1],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][2],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][3],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][4],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][5],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][6],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][7],true);
                 me.bubbleArray[bubbleCounter].currentNewsId = me.bubbleArray[bubbleCounter].newsId[showHideCounter-1];
                $("#content"+bubbleCounter+"_1").html(content);
                $("#detail_"+bubbleCounter).html("<a href='http://here-spy/info/news/nwid/"+me.bubbleArray[bubbleCounter].newsId[showHideCounter-1]+"'>Detail</a>");
            } else {
                $("#prevDiv_"+bubbleCounter).hide(); 
                showHideCounter = 1;
                var content = me.createOtherContent(me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][0],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][1],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][2],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][3],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][4],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][5],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][6],me.bubbleArray[bubbleCounter].contentArgs[showHideCounter-1][7],true);
                me.bubbleArray[bubbleCounter].currentNewsId = me.bubbleArray[bubbleCounter].newsId[showHideCounter-1];
                $("#content"+bubbleCounter+"_1").html(content);
                $("#detail_"+bubbleCounter).html("<a href='http://here-spy/info/news/nwid/"+me.bubbleArray[bubbleCounter].newsId[showHideCounter-1]+"'>Detail</a>");
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


function sendLink(thisone,action){
    var pageLink = $(thisone).attr('name');
    $.post(baseUrl+"home/change-link",{"pageLink":pageLink},function(data){
        window.location = data;
    })
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
function linkClickable(replaceText) 
{
  
    var replacePattern1, replacePattern2, replacePattern3, replacedText;

    //URLs starting with http://, https://, or ftp://
    replacePattern1 = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
    replacedText = replaceText.replace(replacePattern1, '<a href="$1" target="_blank">$1</a>');

    //URLs starting with "www." (without // before it, or it'd re-link the ones done above).
    replacePattern2 = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
    replacedText = replacedText.replace(replacePattern2, '$1<a href="http://$2" target="_blank">$2</a>');

    //Change email addresses to mailto:: links.
    replacePattern3 = /(\w+@[a-zA-Z_]+?\.[a-zA-Z]{2,6})/gim;
    replacedText = replacedText.replace(replacePattern3, '<a href="mailto:$1" target="_blank"kiya >$1</a>');

    return replacedText

}
