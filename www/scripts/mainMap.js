function MainMap(opt_options) {

    this.setValues(opt_options);

    this.flag = 0;

    this.initialFlag = false;

    this.createMap();

    return this;

};



MainMap.prototype = new google.maps.OverlayView;



//On add function 

MainMap.prototype.onAdd = function() {

 var me = this;

 this.listeners_ = [

   google.maps.event.addListener(this, 'position_changed',

       function() {}),

   google.maps.event.addListener(this, 'text_changed',

       function() {})

 ];

}



//onRemove function to remove all the listeners

MainMap.prototype.onRemove = function() {

 for (var i = 0, I = this.listeners_.length; i < I; ++i) {

   google.maps.event.removeListener(this.listeners_[i]);

 }

};



MainMap.prototype.createMap = function(){

    var me = this;

    me.bubbleArray = new Array();

    me.geocoder = new google.maps.Geocoder();

    me.mapMoved = false;

    

    var myOptions = {

		zoom: 14,

		center: me.centerPoint,

        disableDefaultUI: true,

        panControl: false,

    	zoomControl: false,

        scaleControl: false,

    	streetViewControl: false,

    	overviewMapControl:false,

		mapTypeId: google.maps.MapTypeId.ROADMAP,

    };

    map = me.map = new google.maps.Map(me.mapElement, myOptions);

     google.maps.event.addListener(map, 'click', function(event) {

            if(previousBox)

                 previousBox.removeAttr('style');

            previousBox = null;

     });

    if(me.mapType == 'MAIN'){

         google.maps.event.addListener(map, 'zoom_changed', function() {

    		if (map.getZoom() < 13){

    		   map.setZoom(13);

    		}

    		if (map.getZoom() > 15){

    		   map.setZoom(15);

    		}

    	 });

        if(me.showMapElement)

            me.mapContent();    

        google.maps.event.addListener(me.map,"tilesloaded",function(){

           	if(me.showMapElement) {

                sliderInitialization();

			}

			var currentUrl =  String(document.URL).split("/");

			if(currentUrl[currentUrl.length-1] == 'search'){

				selectLink($("#links li:nth-child(2)").find("a"),false);

				$("#links li:nth-child(2)").find("a").trigger('click');

				searchData(user_id,false,'selected');

			} else if(currentUrl[currentUrl.length-1] == 'friend') {

				selectLink($("#links li:nth-child(2)").find("a"),false);

				$("#links li:nth-child(4)").find("a").trigger('click');

				searchData(user_id,false,'myconnection');

			} else if(currentUrl[currentUrl.length-1] == 'interest') {

				selectLink($("#links li:nth-child(2)").find("a"),false);

				$("#links li:nth-child(3)").find("a").trigger('click');

				searchData(user_id,false,'interest');

			}

		

        });

		

        if(me.isMapDragable == 'dragable'){

            google.maps.event.addListener(me.map,'dragend',function(){

               initFlagForMap = true;

               if(previousBubble)

                    previousBubble.close();

               if(me.showMapElement)

                    me.onMapDragen('CENTER');

               else

                   me.onMapDragen('FRIENDS');

            });

        }

        me.marker = new Array();

        if(me.showMapElement)

            me.createMarker(me.centerPoint,me.icon,'center',me.centerData);

        else

            me.createMarker(me.centerPoint,me.icon,'other',me.centerData);

    } else {

       

        me.getCenterAddress(me.centerPoint,'CENTER');

    }

};

MainMap.prototype['createMap'] = MainMap.prototype.createMap;



MainMap.prototype.changeMapCenter = function(latLng){

    this.map.setCenter(latLng);

    this.centerPoint = latLng;

    userLatitude = latLng.lat();

    userLongitude  = latLng.lng();

    initFlagForMap = false;

    me.onMapDragen('ALL');

}

MainMap.prototype['changeMapCenter'] = MainMap.prototype.changeMapCenter;

MainMap.prototype.clearMarkers = function(){

     var tempMarkerArray = this.marker;

     for(i in  tempMarkerArray){

           if(i>0)

            tempMarkerArray[i].setMap(null);

     }

     this.marker.length = 1;

     this.bubbleArray.length = 1;

    

} 

MainMap.prototype['clearMarkers'] = MainMap.prototype.clearMarkers;



MainMap.prototype.createMarker = function(markerPosition,markerIcon,type,markerContent){

    var previousPosition = this.findPrevious(markerPosition);

    

    var me = this;

    //console.log(markerPosition.lat()+","+markerPosition.lng()+"-"+previousPosition+"--"+markerContent.id);

    if(previousPosition==-1){

        if(type == 'center'){

            if(distance(new google.maps.LatLng(userLatitude,userLongitude),markerPosition,'F')!=0){

               return false;

            }

        }

        this.counter = 0;

        var marker = new google.maps.Marker({

                     position:markerPosition,

                     map: this.map,

                     draggable:false,

                     icon:this.createIcon(markerIcon)

        });

        (marker).setMap(this.map);

        (this.marker).push(marker);

        markerArray.push(marker);

        this.flag = 1;

        if(type == 'center'){

            circle = this.circle;

            if(circle){

                circle.hideCircle();

            }

          

            circle = new AreaCircle({

                    radious:Number(circleSize),

                    center:markerPosition

            });

            this.circle = circle;

        }

        bubbleContent = me.bubbleArray;

        (me.bubbleArray).push(this.createInfoWindow(marker,markerContent.name,markerContent.news,markerContent.id,markerContent.userImage,markerContent.user_id));

    } else {

         

      //  if(me.findData(me.bubbleArray[previousPosition].user_id,markerContent.user_id,'CHECK')) {

//            if(initFlagForMap && (previousPosition == 0)){

//                return;

//            }

//            

//            if((previousPosition == 0)&&(this.initialFlag==true)){

//                this.initialFlag = false;

//                return;

//            }

//            

//            bubbleContent = me.bubbleArray;

//            var currentBubble = (me.bubbleArray[previousPosition]);

//            (currentBubble.user_id).push(markerContent.user_id);

//            $("#contentUpdator").html(me.bubbleArray[previousPosition].divContent);

//            $('#nextDiv_'+previousPosition).css('display','none'); // 'block' in previous version

//            me.bubbleArray[previousPosition].divContent = $("#contentUpdator").html();

//            $("#contentUpdator").html("");

//            

//            var newContent = this.createContent(markerContent.userImage,markerContent.name,markerContent.news,++(currentBubble.total),'second',previousPosition,markerContent.id,false);

//            

//            this.updateContent(newContent,previousPosition,1,markerContent.id);

//        } else {

//            

           

          

          

            if(me.findData(me.bubbleArray[previousPosition].newsId,markerContent.id,'CHECK')){

                var currentBubble = (me.bubbleArray[previousPosition]);

                    (currentBubble.newsId).push(markerContent.id);

                    (currentBubble.user_id).push(markerContent.user_id);

                     currentBubble.total++;

                     me.bubbleArray[previousPosition].divContent =  me.createContent(me.bubbleArray[previousPosition].contentArgs[0][0],me.bubbleArray[previousPosition].contentArgs[0][1],me.bubbleArray[previousPosition].contentArgs[0][2],me.bubbleArray[previousPosition].contentArgs[0][3],me.bubbleArray[previousPosition].contentArgs[0][4],me.bubbleArray[previousPosition].contentArgs[0][5],me.bubbleArray[previousPosition].contentArgs[0][6],me.bubbleArray[previousPosition].contentArgs[0][7],false);

                    (me.bubbleArray[previousPosition].contentArgs).push([markerContent.userImage,markerContent.name,markerContent.news,++(currentBubble.total),'second',previousPosition,markerContent.id]);

         

                }

   //   }

    }

    return true;

};

MainMap.prototype['createMarker'] = MainMap.prototype.createMarker;



MainMap.prototype.findData = function(searchIn,searchId,type){

    for(i in searchIn){

        if(searchIn[i] == searchId){

            if(type == 'CHECK')

                return false;

            else

                return i;

        }

    }

    if(type == 'CHECK')

       return true;

    else

        return -1;

    

}

MainMap.prototype['findData'] = MainMap.prototype.findData;



MainMap.prototype.findPrevious = function(point){

    var tempMarkerArray = this.marker;

    for(var i in  tempMarkerArray){

       if((tempMarkerArray[i].getPosition().lat() == point.lat())&&(tempMarkerArray[i].getPosition().lng() == point.lng())){

           return i;

       }

       

       if(distance(tempMarkerArray[i].getPosition(),point,'F')<=0.0189393939){

            return i;

       }

    }

    return -1;

}

MainMap.prototype['findPrevious'] = MainMap.prototype.findPrevious;



MainMap.prototype.createIcon = function(currentIcon) {

    var icon = currentIcon;

    var image = new google.maps.MarkerImage(baseUrl+""+icon.image,null, null, null, new google.maps.Size(icon.height,icon.width));

    return image;

};

MainMap.prototype['createIcon'] = MainMap.prototype.createIcon;



MainMap.prototype.markerClickEvent = function(marker,infoWindow,content,id,newsId){

     var content = "<div id='mainContent_"+id+"' class='bubbleContent' totalDiv='1' currentDiv='1'>"+content+"</div>";

     infoWindow.setContent(content);

     var me = this;

    

    

     google.maps.event.addListener(marker, "mouseover", function(){

        $(document).find("#mainContent_"+id).each(function(){

                    if($(this).html()==""){

                        $(this).remove();

                    };

        })

      

       $("#mainContent_"+id).parent().parent().parent().css('min-height','0px');

        if(previousBubble)

             previousBubble.close();

	    $("#mainContent_"+id).parent().parent().parent().css('min-height','0px');

        var entryFlag = false;

        

        if(!$("#mainContent_"+id).attr('currentdiv')){

            entryFlag = true;

        }

       

        if(($("#mainContent_"+id).attr('currentdiv')==1)||entryFlag){

           

            if($("#mainContent_"+id).attr('currentdiv')<(me.bubbleArray[id].newsId).length){

                entryFlag = true; 

            }

            

            if(entryFlag){

                if(((me.bubbleArray[id].newsId).length<=1) || (me.bubbleArray[id].contentArgs[0][2] == 'No News')){

                    entryFlag = false;

                }

            }

            

            if(entryFlag){

                $(document).find("#mainContent_"+id).each(function(){

                    $(this).remove();

                })

                content = me.createContent(me.bubbleArray[id].contentArgs[0][0],me.bubbleArray[id].contentArgs[0][1],me.bubbleArray[id].contentArgs[0][2],me.bubbleArray[id].contentArgs[0][3],me.bubbleArray[id].contentArgs[0][4],me.bubbleArray[id].contentArgs[0][5],me.bubbleArray[id].contentArgs[0][6],me.bubbleArray[id].contentArgs[0][7],true);

                content = "<div id='mainContent_"+id+"' class='bubbleContent' totalDiv='"+(me.bubbleArray[id].newsId).length+"' currentDiv='1'>"+content+"</div>";

                infoWindow.setContent(content);

                 

            }

            entryFlag = false;

        } 

        

        infoWindow.open(this.map,this);

        currentIdOfMarker = me.findPrevious(marker.getPosition());

        previousBubble = infoWindow;

        

     });

     

    // google.maps.event.addListener(marker, "mouseout",function(){

//        if(previousBubble)

//            previousBubble.close();

//        

//     });

     google.maps.event.addListener(marker,"click",function(){

        if(previousBox)

            previousBox.removeAttr('style');

         previousBox = null;

         if($("#newsDiv"+me.bubbleArray[id].currentNewsId).position()){

            previousBox = $("#newsDiv"+me.bubbleArray[id].currentNewsId).parent();

            previousBox.css('box-shadow','1px 1px 2px 2px #888888');

            $.scrollTo( "#newsDiv"+me.bubbleArray[id].currentNewsId , 1000, {offset: {top:-100, left:-100} }  );

        }

     });

}

MainMap.prototype['markerClickEvent'] = MainMap.prototype.markerClickEvent;

MainMap.prototype.toggleContent = function(contentId,total,toShow){

    toggleContent();

    $('#prevDiv_'+contentId).css('display','none');

    $('#nextDiv_'+contentId).css('display','none');

}

MainMap.prototype['toggleContent'] = MainMap.prototype.toggleContent;



MainMap.prototype.createInfoWindow = function(marker,name,news,newsId,userImage,user_id){

     var infoWindow = new InfoBubble();

     var content = this.createContent(userImage,name,news,1,'first',(this.marker).length-1,newsId,true);   

     this.markerClickEvent(marker,infoWindow,content,(this.marker).length-1,newsId);

     var bubbleContent = new Object({

        infobubble:infoWindow,

        newsId:new Array(newsId),

        user_id:new Array(user_id),

        currentNewsId:newsId,

        divContent:content,

        contentArgs:new Array([userImage,name,news,1,'first',(this.marker).length-1,newsId]),

        total:1

     })

     this.counter++;

     return bubbleContent;

};

MainMap.prototype['createInfoWindow'] = MainMap.prototype.createInfoWindow;



MainMap.prototype.changeContent = function(newContent,counter,visibleDiv,newsId){

    var currentTempBubble = (this.bubbleArray[counter]);

    var content = newContent;

    if(this.findData(this.bubbleArray[counter].newsId,newsId,'CHECK')) {

        (currentTempBubble.newsId).push(newsId);

    }

    currentTempBubble.divContent = newContent;

    newContent = "<div id='mainContent_"+counter+"' class='bubbleContent' totalDiv='"+currentTempBubble.total+"' currentDiv='"+visibleDiv+"'>"+newContent+"</div>";

    (currentTempBubble.infobubble).setContent(newContent);

    return newContent;

}

MainMap.prototype['changeContent'] = MainMap.prototype.changeContent;



MainMap.prototype.updateContent = function(newContent,counter,visibleDiv,newsId) {

    var currentTempBubble = (this.bubbleArray[counter]);

    var content = currentTempBubble.divContent;

   

    if(this.findData(this.bubbleArray[counter].newsId,newsId,'CHECK')){

        (currentTempBubble.newsId).push(newsId);

    }

    currentTempBubble.divContent = newContent+" "+content;

    newContent = "<div id='mainContent_"+counter+"' class='bubbleContent' totalDiv='"+currentTempBubble.total+"' currentDiv='"+visibleDiv+"'>"+newContent+""+content+"</div>";

    (currentTempBubble.infobubble).setContent(newContent);

    return newContent;

}

MainMap.prototype['updateContent'] = MainMap.prototype.updateContent;



MainMap.prototype.updateNewsid = function(newsId,id){

    (this.InfoWindowArray[id].newsId).push(newsId);

}

MainMap.prototype['updateNewsid'] = MainMap.prototype.updateNewsid;



MainMap.prototype.createContent = function(userImage,name,News,id,type,bubbleId,newsId,arrowFlag){

    var content = "<div id='content"+bubbleId+"_"+id+"' class='infoBubbleClass'";

     if(type == 'second'){

            content+= "style='display:none' ";

     }

    var UserImage = "";

    if(userImage){

        if(userImage.indexOf('://') > 0) 

             UserImage =  userImage;

        else

             UserImage = baseUrl+"uploads/"+ userImage;

        

    } else {

        UserImage = baseUrl+"www/images/img-prof40x40.jpg";

    }

    content+= "><div style='float:left;width:20%'><img height='55' width='55' class='userImage' src='"+UserImage+"'/></div>";

	content += "<div class='markerNews' align='left' style='float:right;width:70%'><span id='userName"+bubbleId+"_"+id+"'class='userName'>"+name+"</span><span id='news"+bubbleId+"_"+id+"' style='margin-top:5px;display:block'>"+News.substring(0,100);

    if(News.length>350) {

        content += "..<span style='color:blue;cursor:pointer;' onclick='focusOnNews("+newsId+")'>More</span></span></div></div>";

    } else {

        content += "</span></div></div>";

    }

    if(type == 'first'){

        content += "<div id='controller' style='margin-top:30px;'><div style='float:left;width:70%;' align='left'><span id='detail_"+bubbleId+"'>";

        if(News != 'No News')

            content += "<a href='"+baseUrl+"info/news/nwid/"+newsId+"'>More details</a>";

        content += "</span></div><div style='float:right;width:30%;'><div id='prevDiv_"+bubbleId+"' align='left' style='float:left;width:50%;cursor:pointer;font-weight:bold;display:none;font: normal 10px laot-LightItalic;color: #4275cd;' onclick=showHideDivContent('prev','"+bubbleId+"')><img src='www/images/previous_button.png'/></div><div id='nextDiv_"+bubbleId+"' align='right'  style='float:right;width:50%;cursor:pointer;font-weight:bold;font: normal 10px laot-LightItalic;color: #4275cd;";

        if(arrowFlag)

            content += " display:none; ";

        else

            content += " display:block; ";

        content += "' onclick=showHideDivContent('next','"+bubbleId+"')><img src='www/images/next_button.png'/></div></div></div>";

    }

    

    return content;

};

MainMap.prototype['createContent'] = MainMap.prototype.createContent;



MainMap.prototype.createOtherContent = function(userImage,name,News,id,type,bubbleId,newsId,viewFlag){

    var content = "";

    if(viewFlag)

        content = "<div id='content"+bubbleId+"_"+id+"' ";

    

    var UserImage = "";

    if(userImage){

        if(userImage.indexOf('://') > 0) 

             UserImage =  userImage;

        else

             UserImage = baseUrl+"uploads/"+ userImage;

        

    } else {

        UserImage = baseUrl+"www/images/img-prof40x40.jpg";

    }

    

    if(viewFlag)

        content+=  "class='contentDivClass'>";

    content+= "<div style='float:left;width:20%'><img height='55' width='55' class='userImage' src='"+UserImage+"'/></div>";

	content += "<div class='markerNews' align='left' style='float:right;width:70%'><span id='userName"+bubbleId+"_"+id+"'class='userName'>"+name+"</span><span id='news"+bubbleId+"_"+id+"' style='margin-top:5px;display:block'>"+News.substring(0,100);

    if(News.length>350) {

        content += "..<span style='color:blue;cursor:pointer;' onclick='focusOnNews("+newsId+")'>More</span></span></div>";

    } else {

        content += "</span></div>";

    }

    if(viewFlag)

        content += "</div>";

    

    return content;

};

MainMap.prototype['createOtherContent'] = MainMap.prototype.createOtherContent;



MainMap.prototype.dragEvent = function(){

    var me = this;

   google.maps.event.addListener(this.marker, "dragend", function(event){

        (me.infoWindow).close();

        me.getPlaceByLatitue(event.latLng,'change'); 

   });

};

MainMap.prototype['dragEvent'] = MainMap.prototype.dragEvent;



MainMap.prototype.px = function(num) {

  if (num) {

    return num + 'px';

  }

  return num;

};

MainMap.prototype['px'] = MainMap.prototype.px;



MainMap.prototype.mapContent = function(){

    var downSlider = this.createImageDom('move',["dowmImage"],'clickableImage',[baseUrl+"www/images/map_arrow_down.png"],180);

    var upSlider = this.createImageDom('move',["upImage"],'clickableImage',[baseUrl+"www/images/map_arrow_up.png"],0);

    var rightSlider = this.createImageDom('move',["rightImage"],'clickableImage',[baseUrl+"www/images/map_arrow_right.png"],90);

    var leftSlider = this.createImageDom('move',["leftImage"],'clickableImage',[baseUrl+"www/images/map_arrow_left.png"],270);

    var zoomController = this.createImageDom('zoom',["zoomIn","zoomOut"],'clickableImage',[baseUrl+"www/images/zoom_in.png",baseUrl+"www/images/zoom_out.png"],'');

    var sliderController = this.createImageDom('slide',["sliderController"],'sliderBackground','','');

    

	map.controls[google.maps.ControlPosition.LEFT_CENTER].push(leftSlider);

    map.controls[google.maps.ControlPosition.RIGHT_CENTER].push(rightSlider);

    map.controls[google.maps.ControlPosition.TOP_CENTER].push(upSlider);

    map.controls[google.maps.ControlPosition.BOTTOM_CENTER].push(downSlider);

    map.controls[google.maps.ControlPosition.BOTTOM_LEFT].push(sliderController);

    map.controls[google.maps.ControlPosition.TOP_LEFT].push(zoomController);



    

};

MainMap.prototype['mapContent'] = MainMap.prototype.mapContent;







MainMap.prototype.createImageDom = function(type,id,className,src,angle) {

    var domToReturn = null;

    if(type == 'zoom') {

       var mainDiv = document.createElement('div'); 

       mainDiv.id = 'zoomController';

       mainDiv.style['padding'] = '5px';

    }

    if(src.length){

        for(i in src){

            var imageDom = document.createElement('IMG');

            imageDom.id = id[i];

            imageDom.src = src[i];

            imageDom.className = className;

            imageDom.style['padding'] = '5px';

            if(type == 'move') {

                this.createDomListener(imageDom,'move',angle);

                domToReturn = imageDom;

            }else{

               var secDiv = document.createElement('div'); 

               this.createDomListener(imageDom,id[i],angle);

               secDiv.appendChild(imageDom);

               mainDiv.appendChild(secDiv);

               domToReturn = mainDiv;

            }

        }

    } else {

       var mainDiv = document.createElement('div'); 

       mainDiv.id = "sliderController";

       mainDiv.className = className;

      

       

       var secDiv = document.createElement('div'); 

       secDiv.align = 'center';

       secDiv.style['fontWeight'] = 'bold';

       secDiv.style['marginBottom'] = '4px';

       

       var spanDom = document.createElement('span'); 

       spanDom.id = 'radious';

       spanDom.style['color'] = '#4276cd';

       spanDom.innerHTML = circleSize;

       secDiv.appendChild(spanDom);

       

       var spanDom = document.createElement('span'); 

       spanDom.style['color'] = '#4276cd';

       spanDom.innerHTML = ' Miles';

       

       secDiv.appendChild(spanDom);

       

       mainDiv.appendChild(secDiv);

       

       var secDiv = document.createElement('div'); 

       secDiv.id = 'slider';

       mainDiv.appendChild(secDiv);

       domToReturn = mainDiv

    }

    return domToReturn;

}

MainMap.prototype['createImageDom'] = MainMap.prototype.createImageDom;



MainMap.prototype.createDomListener = function(imageDom,type,angle){

    var me = this;

     google.maps.event.addDomListener(imageDom, 'click', function() {

        if(type == 'move')

            me.changeMapCenter(angle,.5);

        if(type == 'zoomIn')

            me.changeMapZoom('in');

        if(type == 'zoomOut')

            me.changeMapZoom('out')

            

    });

}

MainMap.prototype['createDomListener'] = MainMap.prototype.createDomListener;





MainMap.prototype.changeMapCenter = function(brng,dist){

    map = this.map;

    var centerOfMap = (map).getCenter();

    map.setCenter(centerOfMap.findPoint(brng,dist));

}

MainMap.prototype['changeMapCenter'] = MainMap.prototype.changeMapCenter;



MainMap.prototype.clearMapMarker = function(type){

    var marker = this.marker;

    var flag = true;

    var length = 1;

    for(i in marker){

        if(type != 'ALL'){

            if(i == 0){

                flag = false;

            }else{

                flag = true;

            }

        }else{

           length = 0

           flag = true;

        }

       

        if(flag){

            if($("#mainContent_"+i))

                $("#mainContent_"+i).html("");

            marker[i].setMap(null);

        }

    }

    (this.marker).length = length;

    (this.bubbleArray).length = length;

}

MainMap.prototype['clearMapMarker'] = MainMap.prototype.clearMapMarker;





MainMap.prototype.changeMapZoom = function(type){

    var zoom = this.map.getZoom();

    if(type == 'in') {

        zoom++;

        this.map.setZoom(zoom);

    } else {

        zoom--;

        this.map.setZoom(zoom);

    }

}

MainMap.prototype['changeMapZoom'] = MainMap.prototype.changeMapZoom;



MainMap.prototype.onMapDragen = function(type){

   

   if(type != 'FRIENDS'){

        showNewsScreen();

        var me = this;

        var newCenterPoint = me.flushMap(type);

        var doDefault = true;

        $("#links").find("a").each(function(){

           if($(this).attr('class') == 'blue'){

                doDefault = filterByArea(user_id,$(this).html());

           } 

        });

        //selectLink('',true);

        

        if(doDefault){

            if(type == 'ALL')

                latestNews(userLatitude,userLongitude,'INITIAL');

            if(type == 'CENTER')

                getAllNearestPoint(newCenterPoint.lat(),newCenterPoint.lng(),Number($("#radious").html()));

        }

    } else {

        var userPosition = this.map.getCenter();

        getAllNearestFriends(userPosition.lat(),userPosition.lng(),Number($("#radious").html()));

    }

    //hideNewsScreen();

}

MainMap.prototype['onMapDragen'] = MainMap.prototype.onMapDragen;



function filterByArea(user_id,linkText){

   

    if(linkText == 'View All')

        return true;

    if(linkText == 'Mine only')

        searchData(user_id,true,'selected',true);

    if(linkText == 'My Interests' )

        searchData(user_id,true,'interest',true);

    if(linkText == 'My Connections' )

        searchData(user_id,true,'myconnection',true);

    return false;

}



MainMap.prototype.flushMap = function(type){

    var me = this;

    me.clearMapMarker(type);

    var newCenterPoint = (me.map).getCenter();

 	if(distance(new google.maps.LatLng(userLatitude,userLongitude),newCenterPoint,'M')>(Number($('#radious').html()))){

		me.mapMoved = true;

	} else {

		me.mapMoved = false;

	}

    me.changeCircleRadious();

    return newCenterPoint;

}

MainMap.prototype['flushMap'] = MainMap.prototype.flushMap;



MainMap.prototype.changeCircleRadious = function(){

   var newCenterPoint = (this.map).getCenter();

   if(this.circle){

        (this.circle).changeCenter(newCenterPoint,Number($("#radious").html()));

    } 

}

MainMap.prototype['changeCircleRadious'] = MainMap.prototype.changeCircleRadious;



MainMap.prototype.searchNewsMarker = function(newsId,type){

    var bubbleArrayTemp = (this.bubbleArray);

    var loopflag = false;

    for(bubbleArrayCounter in bubbleArrayTemp) {

        var newsArray = bubbleArrayTemp[bubbleArrayCounter].newsId;

        for(newsArrayCounter in newsArray) {

           if(newsArray[newsArrayCounter] == newsId) {

                if(type == 'bounce')

                    this.bounceMarker(bubbleArrayCounter);

                else

                    this.removeBouncing(bubbleArrayCounter);

                loopflag = true;

                break

            }

        }

        if(loopflag)

            break;

    }

    return;

}

MainMap.prototype['searchNewsMarker'] = MainMap.prototype.searchNewsMarker;



MainMap.prototype.bounceMarker = function(markerId){

    if(markerId!=0)

        this.marker[markerId].setIcon(this.createIcon(UserMarker2));

    else

        this.marker[markerId].setIcon(this.createIcon(MainMarker2));

}

MainMap.prototype['bounceMarker'] = MainMap.prototype.bounceMarker;



MainMap.prototype.removeBouncing = function(markerId){

    if(markerId!=0)

        this.marker[markerId].setIcon(this.createIcon(UserMarker1));

    else

        this.marker[markerId].setIcon(this.createIcon(MainMarker1));

}

MainMap.prototype['removeBouncing'] = MainMap.prototype.removeBouncing;



/*------------------------------------------------------------------------*/

            /*------------      Related Map -------------*/

/*------------------------------------------------------------------------*/





MainMap.prototype.addressMarker = function(markerPosition,markerIcon,markerContent){

    var markerOptions = {

                     position:markerPosition,

                     map: this.map,

                     draggable:false,

                     icon:this.createIcon(markerIcon)

    }

    if(this.markerType == 'dragable'){

        markerOptions.draggable=true;

    }

    

    this.movingMarker = new google.maps.Marker(markerOptions);

    this.movingMarker.setMap(this.map); 

    var me = this;

    this.addressWindow(markerContent);

    google.maps.event.addListener(this.movingMarker, "dragend", function(event){

         me.centerPoint = event.latLng;

        (me.infoWindow).close();

         me.getCenterAddress(event.latLng,'CHANGE'); 

    });

    

};

MainMap.prototype['addressMarker'] = MainMap.prototype.addressMarker;



MainMap.prototype.addressWindow = function(address){

    this.infoWindow = new google.maps.InfoWindow({maxWidth:220});

    this.infoWindow.setContent(this.addressContent(address));

    this.infoWindow.setPosition((this.movingMarker).getPosition());

    this.infoWindow.open(this.map);   

};

MainMap.prototype['infoWindow'] = MainMap.prototype.infoWindow;



MainMap.prototype.addressContent = function(address){

    var content = "<table><tr><td valign='middle'>"+address+"</td>";

	if(this.profileImage)

		content+="<td valign='middle'>&nbsp;<img height='55' width='55' src='"+this.profileImage+"'/></td>";

	

	content+="</tr></table>";

    return content;

};

MainMap.prototype['addressContent'] = MainMap.prototype.addressContent;



MainMap.prototype.changeAddressContent = function(address){

	(this.movingMarker).setPosition(this.centerPoint);

    this.infoWindow.setContent(this.addressContent(address));

    this.infoWindow.setPosition((this.movingMarker).getPosition());

    this.infoWindow.open(this.map);   

};

MainMap.prototype['changeAddressContent'] = MainMap.prototype.changeAddressContent;



MainMap.prototype.getCenterAddress = function(latlng,flag) {

    var me = this;

    me.geocoder.geocode({

     'latLng': latlng

    }, function(results,status) {

     if (status == google.maps.GeocoderStatus.OK) {

         if (results[0]) {

            if($("#useNewAddress"))

                $("#useNewAddress").removeAttr('disabled');

            var formattedAddress = results[0].formatted_address;

            var latitudeLongitude = results[0].geometry.location;

			me.centerPoint = latlng;

			me.map.setCenter(latlng);

            me.centerData = formattedAddress;

			$("#Location").val(formattedAddress);

			if(flag == 'CHANGE')

                me.changeAddressContent(formattedAddress);

            else

                me.addressMarker(latitudeLongitude,me.icon,formattedAddress);

         }

     }

    });

};

MainMap.prototype['getPlaceByLatitue'] = MainMap.prototype.getPlaceByLatitue;