var mainMap, defaultZoom=14;
$(function(){
	$.ajax({
		url: 'https://maps.googleapis.com/maps/api/js?v=3.x&sensor=false&callback=initMap',
		dataType: 'script',
		cache: true
	});
});
function initMap(){
	mainMap = new google.maps.Map(document.getElementById('map_canvas'), {
		center: new google.maps.LatLng(mapCenter[0], mapCenter[1]),
		zoom: defaultZoom,
		minZoom: 13,
		maxZoom: 15,
		disableDefaultUI: true,
		scrollwheel: false
	});

	$('#slider').slider();

	$('.post-new').click(function(e){
		e.preventDefault();
		$('.post-new-dialog').toggle();
	});

	// TODO: combine icon
	var controlUIzoomIn = $('<div/>', {title: 'Zoom in'}).addClass('zoom_in')
		.append($('<img/>', {src: '/www/images/template/zoom_in25x25.png'}).attr({width: 25, height: 25})).get(0);

	google.maps.event.addDomListener(controlUIzoomIn, 'click', function(){
		mainMap.setZoom(mainMap.getZoom()+1);
	});

	// TODO: combine icon
	var controlUIzoomOut = $('<div/>', {title: 'Zoom out'}).addClass('zoom_out')
		.append($('<img/>', {src: '/www/images/template/zoom_out25x25.png'}).attr({width: 25, height: 25})).get(0);

	google.maps.event.addDomListener(controlUIzoomOut, 'click', function(){
		mainMap.setZoom(mainMap.getZoom()-1);
	});

	// TODO: combine icon
	var controlUImyLocation = $('<div/>', {title: 'Zoom out'}).addClass('my_location')
		.append($('<img/>', {src: '/www/images/template/my_location.png'}).attr({width: 20, height: 18})).get(0);

	google.maps.event.addDomListener(controlUImyLocation, 'click', function(){
		if (navigator.geolocation){
			navigator.geolocation.getCurrentPosition(function(position){
				mainMap.setCenter({
					lat: position.coords.latitude,
					lng: position.coords.longitude
				});
				mainMap.setZoom(defaultZoom);
			}, function(){
				handleLocationError(true);
			});
		} else {
			handleLocationError(false);
		}
	});

	mainMap.controls[google.maps.ControlPosition.RIGHT_TOP].push(
		$('<div/>').addClass('customMapControl')
			.append(controlUIzoomIn, controlUIzoomOut, controlUImyLocation)[0]
	);
}
function handleLocationError(browserHasGeolocation){
	alert(browserHasGeolocation ?
		'Error: The Geolocation service failed.' :
		'Error: Your browser doesn\'t support geolocation.');
}
