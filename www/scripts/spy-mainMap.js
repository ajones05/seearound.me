function MainMap(opt_options) {
    this.setValues(opt_options);
    this.flag = 0;
    this.initialFlag = false;
    this.createMap();
    this.changedLatitude = null;
    this.changedLongitude = null;

	if (typeof this.showInfoWindow  === 'undefined'){
		this.showInfoWindow = true;
	}

    return this;
 };



MainMap.prototype = new google.maps.OverlayView;

//On add function 

MainMap.prototype.onAdd = function() {

 var me = this;

 this.listeners_ = [google.maps.event.addListener(this, 'position_changed',function() {}),google.maps.event.addListener(this, 'text_changed',function() {}) ];

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
     me.changedLatitude = me.centerPoint.lat();
     me.changedLongitude = me.centerPoint.lng();
     me.comment  = me.centerData.comment;
   
     var styles = [{
        featureType: "all",
        elementType: "geometry",
        stylers: [ 
          { saturation: 5 }, 
          { lightness: 45 },
          { gamma: 0.85 }  
        ] 
      }];
    
    
    var myOptions = {

		zoom: 14,

		center: me.centerPoint,

        disableDefaultUI: true,

        panControl: false,

    	zoomControl: false,

        scaleControl: false,

    	streetViewControl: false,

    	overviewMapControl:false,
        
        styles:styles,

	    mapTypeId: google.maps.MapTypeId.ROADMAP

    };

     map = me.map = new google.maps.Map(me.mapElement, myOptions);

     google.maps.event.addListener(map, 'click', function(event) {

            if(previousBox)

                 previousBox.removeAttr('style');

            previousBox = null;

     });

    if(me.mapType == 'MAIN') {
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
        });

		

        if(me.isMapDragable == 'dragable') {

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
       if(otherProfileExist){
         me.getCenterAddress(me.centerPoint,me.comment,'CENTER');
       } else {
         me.getCenterAddressMarker(me.centerPoint,'CENTER');
       }
     }
};

MainMap.prototype['createMap'] = MainMap.prototype.createMap;

MainMap.prototype.changeMapCenter = function(latLng) {

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



MainMap.prototype.createMarkersOnMap = function(dataForMarkers,myPosition,markerIcon){
    var markerPosition,markerContent,previousPosition;

    var me = this;

    for(i in dataForMarkers){

        

        markerPosition = new google.maps.LatLng(parseFloat(dataForMarkers[i]['latitude']),parseFloat(dataForMarkers[i]['longitude']));

        var resultPosition = this.findPrevious(markerPosition);

        if(resultPosition!=-1){

            previousPosition = resultPosition;

        }

        markerContent = {name:dataForMarkers[i]['Name'],id:dataForMarkers[i]['id'],news:dataForMarkers[i]['news'],userImage:dataForMarkers[i]['Profile_image'],user_id:dataForMarkers[i]['user_id']};

        if(resultPosition==-1){

            var marker = new google.maps.Marker({

                     position:markerPosition,

                     map: this.map,

                     draggable:false,

                     icon:this.createIcon(markerIcon)

            });

            (marker).setMap(this.map);

            (this.marker).push(marker);

            markerArray.push(marker);

            bubbleContent = me.bubbleArray;

            (me.bubbleArray).push(this.createInfoWindow(marker,markerContent.name,markerContent.news,markerContent.id,markerContent.userImage,markerContent.user_id));

        } else {

            var allowToUpdate = true;

            if(resultPosition==0){

               if(!(me.bubbleArray[previousPosition].newsId).length){

                 var currentBubble = (me.bubbleArray[previousPosition]);

                 currentBubble.newsId[0] = markerContent.id;

                 currentBubble.user_id[0] = markerContent.user_id;

                 currentBubble.total = 1;

                 currentBubble.currentNewsId = markerContent.id; 

                 me.bubbleArray[previousPosition].divContent =  me.createContent(markerContent.userImage,markerContent.name,markerContent.news,1,'first',previousPosition,markerContent.id,true);

                 me.bubbleArray[previousPosition].contentArgs[0] = ([markerContent.userImage,markerContent.name,markerContent.news,1,'first',previousPosition,markerContent.id]);

                 allowToUpdate = false; 

               }

            }

            if(me.findData(me.bubbleArray[previousPosition].newsId,markerContent.id,'CHECK')){

                 var currentBubble = (me.bubbleArray[previousPosition]);

                (currentBubble.newsId).push(markerContent.id);

                (currentBubble.user_id).push(markerContent.user_id);

                 currentBubble.total++;

                 me.bubbleArray[previousPosition].divContent =  me.createContent(me.bubbleArray[previousPosition].contentArgs[0][0],me.bubbleArray[previousPosition].contentArgs[0][1],me.bubbleArray[previousPosition].contentArgs[0][2],me.bubbleArray[previousPosition].contentArgs[0][3],me.bubbleArray[previousPosition].contentArgs[0][4],me.bubbleArray[previousPosition].contentArgs[0][5],me.bubbleArray[previousPosition].contentArgs[0][6],me.bubbleArray[previousPosition].contentArgs[0][7],false);

                (me.bubbleArray[previousPosition].contentArgs).push([markerContent.userImage,markerContent.name,markerContent.news,++(currentBubble.total),'second',previousPosition,markerContent.id]);

            }

            

        }

    }

    

    

   

}

MainMap.prototype.createMarkersOnMap1 = function(dataForMarkers, markerIcon){
	var me = this;

	for (i in dataForMarkers){
		var markerPosition = new google.maps.LatLng(parseFloat(dataForMarkers[i]['latitude']), parseFloat(dataForMarkers[i]['longitude'])),
			resultPosition = this.findPrevious(markerPosition),
			markerContent = {
				id: dataForMarkers[i]['id'],
				news: dataForMarkers[i]['news'],
				user_id: dataForMarkers[i]['user']['id'],
				name: dataForMarkers[i]['user']['name'],
				userImage: dataForMarkers[i]['user']['image']
			};

		if (resultPosition == -1){
			var marker = new google.maps.Marker({
				map: this.map,
				position: markerPosition,
				map: this.map,
				draggable: false,
				icon: this.createIcon(markerIcon)
			});

			this.marker.push(marker);
			markerArray.push(marker);
			bubbleContent = me.bubbleArray;

			me.bubbleArray.push(this.createInfoWindow(
				marker,
				markerContent.name,
				markerContent.news,
				markerContent.id,
				markerContent.userImage,
				markerContent.user_id
			));
		} else {
			var previousPosition = resultPosition;

			if (resultPosition == 0){
				if (!me.bubbleArray[previousPosition].newsId.length){
					var currentBubble = me.bubbleArray[previousPosition];

					currentBubble.newsId[0] = markerContent.id;
					currentBubble.user_id[0] = markerContent.user_id;
					currentBubble.total = 1;
					currentBubble.currentNewsId = markerContent.id;

					me.bubbleArray[previousPosition].divContent = me.createContent(
						markerContent.userImage,
						markerContent.name,
						markerContent.news,
						1,
						'first',
						previousPosition,
						markerContent.id,
						true
					);

					me.bubbleArray[previousPosition].contentArgs[0] = [
						markerContent.userImage,
						markerContent.name,
						markerContent.news,
						1,
						'first',
						previousPosition,
						markerContent.id
					];
				}
			}

			if (me.findData(me.bubbleArray[previousPosition].newsId, markerContent.id, 'CHECK')){
				var currentBubble = me.bubbleArray[previousPosition];

				currentBubble.newsId.push(markerContent.id);
				currentBubble.user_id.push(markerContent.user_id);
				currentBubble.total++;

				me.bubbleArray[previousPosition].divContent = me.createContent(
					me.bubbleArray[previousPosition].contentArgs[0][0],
					me.bubbleArray[previousPosition].contentArgs[0][1],
					me.bubbleArray[previousPosition].contentArgs[0][2],
					me.bubbleArray[previousPosition].contentArgs[0][3],
					me.bubbleArray[previousPosition].contentArgs[0][4],
					me.bubbleArray[previousPosition].contentArgs[0][5],
					me.bubbleArray[previousPosition].contentArgs[0][6],
					me.bubbleArray[previousPosition].contentArgs[0][7],
					false
				);

				me.bubbleArray[previousPosition].contentArgs.push([
					markerContent.userImage,
					markerContent.name,
					markerContent.news,
					++currentBubble.total,
					'second',
					previousPosition,
					markerContent.id
				]);
			}
		}
	}
}

MainMap.prototype['createMarkersOnMap'] = MainMap.prototype.createMarkersOnMap;

MainMap.prototype.createMarker = function(markerPosition,markerIcon,type,markerContent){

    var previousPosition = this.findPrevious(markerPosition);

    var me = this;

    if(previousPosition==-1){

        

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

                    radious:getRadius(),

                    center:markerPosition

            });

            this.circle = circle;

           

        }

        

        bubbleContent = me.bubbleArray;

        (me.bubbleArray).push(this.createInfoWindow(marker,markerContent.name,markerContent.news,markerContent.id,markerContent.userImage,markerContent.user_id));

    }

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

     var content = "<div id='mainContent_"+id+"' class='bubbleContent' totalDiv='1' currentDiv='1'  onmouseover='setPopupCloseTimer(0)'  onmouseout='setPopupCloseTimer(700)'>"+content+"</div>";

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

                if(((me.bubbleArray[id].newsId).length<=1) || (me.bubbleArray[id].contentArgs[0][2] == 'This is me!')){

                    entryFlag = false;

                }

            }

            

            if(entryFlag){

                $(document).find("#mainContent_"+id).each(function(){

                    $(this).remove();

                })

                content = me.bubbleArray[id].divContent;

                content = "<div id='mainContent_"+id+"' class='bubbleContent' totalDiv='"+(me.bubbleArray[id].newsId).length+"' currentDiv='1' onmouseover='setPopupCloseTimer(0)'  onmouseout='setPopupCloseTimer(700)'>"+content+"</div>";

                infoWindow.setContent(content);

                 

            }

            if(id == 0){

                if((me.bubbleArray[id].user_id).length == 1){

                     $(document).find("#mainContent_"+id).each(function(){

                            $(this).remove();

                     })

                     content = me.bubbleArray[id].divContent;

                     content = "<div id='mainContent_"+id+"' class='bubbleContent' totalDiv='"+(me.bubbleArray[id].newsId).length+"' currentDiv='1'  onmouseover='setPopupCloseTimer(0)'  onmouseout='setPopupCloseTimer(700)'>"+content+"</div>";

                     infoWindow.setContent(content);

                }

            }

            entryFlag = false;

        } 

        

        infoWindow.open(this.map,this);

        currentIdOfMarker = me.findPrevious(marker.getPosition());

        previousBubble = infoWindow;

        setPopupCloseTimer(0);

     });

     

     google.maps.event.addListener(marker, "mouseout",function(){

        setPopupCloseTimer(700);

     });

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

    if(this.findData(this.bubbleArray[counter].newsId,newsId,'CHECK')){

        (currentTempBubble.newsId).push(newsId);

    }

    currentTempBubble.divContent = newContent;

    newContent = "<div id='mainContent_"+counter+"' class='bubbleContent' totalDiv='"+currentTempBubble.total+"' currentDiv='"+visibleDiv+"'  onmouseover='setPopupCloseTimer(0)'  onmouseout='setPopupCloseTimer(700)'>"+newContent+"</div>";

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
    newContent = "<div id='mainContent_"+counter+"' class='bubbleContent' totalDiv='"+currentTempBubble.total+"' currentDiv='"+visibleDiv+"'  onmouseover='setPopupCloseTimer(0)'  onmouseout='setPopupCloseTimer(700)'>"+newContent+""+content+"</div>";

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

        content += "<div id='controller' style='margin-top: 20px;'><div style='float:left;width:88%; margin-top: 3px;' align='right'><span id='detail_"+bubbleId+"'>";

        if(News != 'This is me!')

            content += "<a href='"+baseUrl+"info/news/nwid/"+newsId+"'>More details</a>";

        content += "</span></div><div style='float:left;width:9%; padding-left:5px; margin-top: 3px'><div class='narrow-flag' id='prevDiv_"+bubbleId+"' align='left' style='width:50%;cursor:pointer;font-weight:bold;display:none;font:italic 12px lato-Bold;color: #4275cd;' onclick=showHideDivContent('prev','"+bubbleId+"')><</div><div class='narrow-flag'  id='nextDiv_"+bubbleId+"' align='right'  style='width:50%;cursor:pointer;font-weight:bold;font: italic 12px lato-Bold;color: #4275cd;";

        if(arrowFlag)

            content += " display:none; ";

        else

            content += " display:block; ";

        content += "' onclick=showHideDivContent('next','"+bubbleId+"')>></div></div></div>";

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

    var zoomController = this.createImageDom('zoom',["zoomIn","zoomOut"],'clickableImage',[baseUrl+"www/images/zoom_in.gif",baseUrl+"www/images/zoom_out.gif"],'');

    var sliderController = this.createImageDom('slide',["sliderController"],'sliderBackground','','');

    

	//map.controls[google.maps.ControlPosition.LEFT_CENTER].push(leftSlider);

    //map.controls[google.maps.ControlPosition.RIGHT_CENTER].push(rightSlider);

    //map.controls[google.maps.ControlPosition.TOP_CENTER].push(upSlider);

    //map.controls[google.maps.ControlPosition.BOTTOM_CENTER].push(downSlider);

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
            imageDom.style['cursor'] = 'pointer';            

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

       //secDiv.style['fontWeight'] = 'bold';

       //secDiv.style['marginBottom'] = '4px';

       

       var spanDom = document.createElement('span'); 

       spanDom.id = 'radious';

       spanDom.style['color'] = '#4276cd';

       spanDom.innerHTML = getRadius();

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
	globalStart = 0;

	if (type == 'FRIENDS'){
		// TODO: fix
		var userPosition = this.map.getCenter();
		getAllNearestFriends(userPosition.lat(), userPosition.lng(), getRadius());		
	} else {
		this.flushMap(type);

		if (type == 'ALL'){
			latestNews();
		} else if (type == 'CENTER'){
			getAllNearestPoint();
		}
	}
}

MainMap.prototype['onMapDragen'] = MainMap.prototype.onMapDragen;

MainMap.prototype.flushMap = function(type){

    var me = this;

    me.clearMapMarker(type);

    var newCenterPoint = (me.map).getCenter();

	var circleRadious = Number($('#radious').html());

	if(!circleRadious){

		circleRadious = 0.8;

	}

 	if(distance(new google.maps.LatLng(userLatitude,userLongitude),newCenterPoint,'M')>(Number(circleRadious))){

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

	if (this.showInfoWindow) {
		this.addressWindow(markerContent);
	}

        google.maps.event.addListener(this.movingMarker, "dragend", function(event){
        
         me.centerPoint = event.latLng;

        (me.infoWindow).close();

         me.changedLatitude = event.latLng.lat();

         me.changedLongitude = event.latLng.lng();

         me.getCenterAddressMarker(event.latLng,'CHANGE'); 

    });
 };

MainMap.prototype['addressMarker'] = MainMap.prototype.addressMarker;



MainMap.prototype.addressWindow = function(address){

    var sizeObj = new google.maps.Size(0, -37, 'px', 'px');
    this.infoWindow = new google.maps.InfoWindow({maxWidth:220, pixelOffset : sizeObj});

    this.infoWindow.setContent(this.addressContent(address));

    this.infoWindow.setPosition((this.movingMarker).getPosition());
    this.infoWindow.open(this.map); 
    setTimeout(function(){
        var parent= jQuery(".gm-style-iw").parent();
        parent.children("div").eq(0).children("div").eq(1).css({"background-color": "#fff", "box-shadow": "none"});
        parent.children("div").eq(0).children("div").eq(0).css({"border-top-color": "#fff"});
        parent.children("div").eq(0).children("div").eq(2).children("div").eq(0).children("div").eq(0).css({"box-shadow": "none"});
        parent.children("div").eq(0).children("div").eq(2).children("div").eq(1).children("div").eq(0).css({"box-shadow": "none"});
        parent.children("div").eq(2).children("img").attr("src", "/www/images/iw_close.gif");
        parent.children("div").eq(2).children("img").css({"position": "absolute","left": "2px","top": "0px","width": "11px","height": "11px"});
    }, 2500);
};

MainMap.prototype['infoWindow'] = MainMap.prototype.infoWindow;



MainMap.prototype.addressContent = function(address){

    /* var content = "<table><tr><td valign='middle'>"+address+"</td>";

	if(this.profileImage)
	 	 content+="<td valign='middle'>&nbsp;<img  style='margin-left:105px;' height='55' width='55' src='"+this.profileImage+"'/></td>";
         //content+="<td valign='middle'>&nbsp;<img height='55' width='55' src='"+this.profileImage+"'/></td>";
   	    content+="</tr></table>";

    return content;*/
    
    var content = "<div class='profile-map-info'>";
        content+= "<div style='float:left;margin-right:8px;'><img class='user-img' src='"+baseUrl+this.profileImage+"'/></div>";            
        content+= "<div class='user-address'>"+address+"</div>";
        content+=  "</div>";
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



MainMap.prototype.getCenterAddress = function(latlng,comment,flag) {

    var me = this;

    me.geocoder.geocode({

     'latLng': latlng

    }, function(results,status) {

     if (status == google.maps.GeocoderStatus.OK) {

         if (results[0]) {

            if($("#useNewAddress"))

                $("#useNewAddress").removeAttr('disabled');

           if(otherProfileExist){
             var formattedAddress  = me.comment;   
            } else {
             var formattedAddress  = results[0].formatted_address;   
            }
            
            //

            var latitudeLongitude = results[0].geometry.location;

			me.centerPoint = latlng;

			me.map.setCenter(latlng);

            me.centerData = formattedAddress;

			$("#Location").val(formattedAddress);

			if(flag == 'CHANGE'){
          
                me.changeAddressContent(formattedAddress);

            } else
                me.addressMarker(latitudeLongitude,me.icon,formattedAddress);

          }
        }
      });
  };
  
  
  MainMap.prototype.getCenterAddressMarker = function(latlng,flag) {

    var me = this;

    me.geocoder.geocode({

     'latLng': latlng

    }, function(results,status) {

     if (status == google.maps.GeocoderStatus.OK) {

         if (results[0]) {

            if($("#useNewAddress"))

                $("#useNewAddress").removeAttr('disabled');
              var formattedAddress  = results[0].formatted_address;   
          
              var latitudeLongitude = results[0].geometry.location;

		  	  me.centerPoint = latlng;

			  me.map.setCenter(latlng);

              me.centerData = formattedAddress;

			  $("#Location").val(formattedAddress);

			  if(flag == 'CHANGE'){
                me.changeAddressContent(formattedAddress);
            } else
                me.addressMarker(latitudeLongitude,me.icon,formattedAddress);
          }
        }
      });
  };

MainMap.prototype['getPlaceByLatitue'] = MainMap.prototype.getPlaceByLatitue;





function closeMapPopup(){

    if(popupCloseTimer){

        setTimeout(function(){

            if(popupCloseTimer){

                 if(previousBubble){

                    previousBubble.close();

                    previousBubble = null;

                 }

                 popupCloseTimer = 0; 

            }

        },popupCloseTimer);

    }

}



function setPopupCloseTimer(timerValue){

    popupCloseTimer = timerValue;

    if(timerValue)

        closeMapPopup()

}

