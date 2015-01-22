function AreaCircle(opt_options) {
    this.setValues(opt_options);
    this.makeCircle();
    return this;
};
AreaCircle.prototype = new google.maps.OverlayView;

//On add function 
AreaCircle.prototype.onAdd = function() {
 var me = this;
 this.listeners_ = [
   google.maps.event.addListener(this, 'position_changed',
       function() {}),
   google.maps.event.addListener(this, 'text_changed',
       function() {})
 ]; 
}

//onRemove function to remove all the listeners
AreaCircle.prototype.onRemove = function() {
 for (var i = 0, I = this.listeners_.length; i < I; ++i) {
   google.maps.event.removeListener(this.listeners_[i]);
 }
};

AreaCircle.prototype.makeCircle = function(){
    var polyArea =this.polyArea = new google.maps.Polygon({
         paths: [this.drawCircle(this.center,1000,1),
                 this.drawCircle(this.center,this.radious,-1)],
         strokeColor: "#0000FF",
         strokeOpacity: 0.1,
         strokeWeight: 0.1,
         fillColor: "#000000",
         fillOpacity: 0.3,
         draggable: false,
        
    });
    polyArea.setMap(map); 
}

AreaCircle.prototype.changeCenter = function(center,radious){
    (this.polyArea).setMap(null);
    this.center = center;
    this.radious = (radious > 0)?(radious):0.8;
    this.makeCircle();
}

AreaCircle.prototype.getCenter = function(center,radious){
   return this.center;
}

AreaCircle.prototype.hideCircle = function(){
    (this.polyArea).setMap(null);
}

AreaCircle.prototype.drawCircle = function(point, userRadius, dir) { 
   
   var d2r = Math.PI / 180;   // degrees to radians 
   var r2d = 180 / Math.PI;   // radians to degrees 
   var earthsradius = 3963; // 3963 is the radius of the earth in miles

   var points = 32; 
   
   var rlat = (userRadius / earthsradius) * r2d; 
   var rlng = rlat / Math.cos(point.lat() * d2r); 


   var extp = new Array(); 
   if (dir==1)	{var start=0;var end=points+1} // one extra here makes sure we connect the
   else		{var start=points+1;var end=0}
   for (var i=start; (dir==1 ? i < end : i > end); i=i+dir)  
   { 
      var theta = Math.PI * (i / (points/2)); 
      ey = point.lng() + (rlng * Math.cos(theta)); // center a + radius x * cos(theta) 
      ex = point.lat() + (rlat * Math.sin(theta)); // center b + radius y * sin(theta) 
      extp.push(new google.maps.LatLng(ex, ey)); 
   } 
   return extp;
}