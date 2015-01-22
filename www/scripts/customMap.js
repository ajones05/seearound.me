function CustomMap(opt_options) {
    this.setValues(opt_options);
    this.createMap();
    return this;
};

CustomMap.prototype = new google.maps.OverlayView;

//On add function 
CustomMap.prototype.onAdd = function() {
 var me = this;
 this.listeners_ = [
   google.maps.event.addListener(this, 'position_changed',
       function() {}),
   google.maps.event.addListener(this, 'text_changed',
       function() {})
 ];
}

//onRemove function to remove all the listeners
CustomMap.prototype.onRemove = function() {
 for (var i = 0, I = this.listeners_.length; i < I; ++i) {
   google.maps.event.removeListener(this.listeners_[i]);
 }
};

CustomMap.prototype.createMap = function(){
    this.geocoder = new google.maps.Geocoder();
    //this.getPlaceByLatitue(this.centerPoint,'initialize'); 
    var myOptions = {
		zoom: 15,
		center: this.centerPoint,
        disableDefaultUI: true,
        panControl: false,
    	zoomControl: true,
        scaleControl: false,
    	streetViewControl: false,
    	overviewMapControl:false,
		mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    this.map = new google.maps.Map(this.mapElement, myOptions);
        
    this.createMarker();
    
    if(this.mapType == 'clickable') {
        this.mapClick();
    }
};
CustomMap.prototype['createMap'] = CustomMap.prototype.createMap;


CustomMap.prototype.createMarker = function(){
    if(this.markerType == 'dragable') {
        this.marker = new google.maps.Marker({
                 position: this.centerPoint,
                 map: this.map,
                 draggable:true,
                 icon:this.createIcon()
        });
    } else {
        this.marker = new google.maps.Marker({
                 position: this.centerPoint,
                 map: this.map,
                 draggable:false,
                 icon:this.createIcon()
        });
    }
    (this.marker).setMap(this.map);
    this.infoWindow();
  
    if(this.markerType == 'dragable') {
        this.dragEvent();
    }
};
CustomMap.prototype['createMarker'] = CustomMap.prototype.createMarker;


CustomMap.prototype.createIcon = function() {
    var icon = this.icon;
    var image = new google.maps.MarkerImage(icon.image,null, null, null, new google.maps.Size(icon.height,icon.width));
    return image;
};
CustomMap.prototype['createIcon'] = CustomMap.prototype.createIcon;


CustomMap.prototype.getPlaceByLatitue = function(latlng,type) {
    var me = this;
    (me.geocoder).geocode({
      'latLng': latlng
    }, function(results,status) {
        if (status == google.maps.GeocoderStatus.OK) {
            if (results[0]) {
				 var formattedAddress = results[0].formatted_address;
				 var latitudeLongitude = results[0].geometry.location;
                 if(type!='initialize'){
					 if(type == 'nochange'){
						 (me.marker).setPosition(latitudeLongitude);
						 me.changeCenter(latitudeLongitude,me.address);
					 }else{
						 me.address = formattedAddress;
						 if(me.relatedMap!=null){
							(me.relatedMap.marker).setPosition(latitudeLongitude);
							(me.relatedMap).changeCenter(latitudeLongitude,formattedAddress)
						 }
						 (me.marker).setPosition(latitudeLongitude);
						 me.changeCenter(latitudeLongitude,formattedAddress);
					 }
					 
                 } 
                 return;
            }
        }
    });
    
};
CustomMap.prototype['getPlaceByLatitue'] = CustomMap.prototype.getPlaceByLatitue;

CustomMap.prototype.changeCenter = function(latLng,address){
    var me = this.map;
    var infoWindow = (this.infoWindow);
    me.setCenter(latLng);
    infoWindow.setContent(this.createContent(address));
    infoWindow.setPosition((this.marker).getPosition());
    infoWindow.open(this.map);
};
CustomMap.prototype['changeCenter'] = CustomMap.prototype.changeCenter;

CustomMap.prototype.mapClick = function(){
    var me = this;
   google.maps.event.addListener(this.map, "click", function(event){
        (me.infoWindow).close();
        me.getPlaceByLatitue(event.latLng,'change'); 
   });
};
CustomMap.prototype['mapClick'] = CustomMap.prototype.mapClick;

CustomMap.prototype.infoWindow = function(){
    this.infoWindow = new google.maps.InfoWindow({maxWidth:220});
    this.infoWindow.setContent(this.createContent(this.address));
    this.infoWindow.setPosition((this.marker).getPosition());
    this.infoWindow.open(this.map);   
};
CustomMap.prototype['infoWindow'] = CustomMap.prototype.infoWindow;

CustomMap.prototype.createContent = function(address){
    var content = "<table><tr><td><b>Address:</b></td></tr><tr><td>"+address+"</td></tr></table>";
    return content;
};
CustomMap.prototype['createContent'] = CustomMap.prototype.createContent;


CustomMap.prototype.dragEvent = function(){
    var me = this;
   google.maps.event.addListener(this.marker, "dragend", function(event){
        (me.infoWindow).close();
        me.getPlaceByLatitue(event.latLng,'change'); 
   });
};
CustomMap.prototype['dragEvent'] = CustomMap.prototype.dragEvent;

CustomMap.prototype.px = function(num) {
  if (num) {
    return num + 'px';
  }
  return num;
};
CustomMap.prototype['px'] = CustomMap.prototype.px;


