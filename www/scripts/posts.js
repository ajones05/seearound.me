var mainMap, defaultZoom=14, postMarkers={}, postMarkersCluster=[];
require.config({
    paths: {
		'jquery': '../../bower_components/jquery/dist/jquery.min',
		'jquery-ui': '../../bower_components/jquery-ui/jquery-ui.min',
		'google.maps': 'https://maps.googleapis.com/maps/api/js?v=3&sensor=false&callback=renderMap_callback'
	}
});

require(['google.maps','jquery','jquery-ui'], function(){
	loadCss('../../bower_components/jquery-ui/themes/base/jquery-ui.min.css');
});

function renderMap_callback(){
	mainMap = new google.maps.Map(document.getElementById('map_canvas'), {
		center: new google.maps.LatLng(mapCenter[0], mapCenter[1]),
		zoom: defaultZoom,
		minZoom: 13,
		maxZoom: 15,
		disableDefaultUI: true,
		scrollwheel: false,
		disableDoubleClickZoom: true,
		styles: [{
			featureType: 'poi',
			stylers: [{visibility: 'off'}]   
		}]
	});

	google.maps.event.addListenerOnce(mainMap, 'idle', function(){
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

		require(['jquery','jquery-ui'], function(){
			for (var id in postData){
				postItem_marker(id);

				if (id > 0){
					$('.post[data-id="'+id+'"]').hover(function(){
						var group = postItem_findCluester($(this).attr('data-id'));
						postMarkers[group].setIcon({
							url: baseUrl+'www/images/template/user-location-icon.png',
							width: 46,
							height: 63
						});
						postMarkers[group].css({zIndex: 100001});
					}, function(){
						var group = postItem_findCluester($(this).attr('data-id'));
						postMarkers[group].setIcon({
							url: baseUrl+'www/images/template/post-icon.png',
							width: 46,
							height: 63
						});
						postMarkers[group].css({zIndex: 'inherit'});
					});
				}
			}

			$('#slider').slider();

			$('.post-new').click(function(e){
				e.preventDefault();
				$('.post-new-dialog').toggle();
			});
		});

		/**
		 * Renders post item marker.
		 */
		function postItem_marker(id){
			if (!$.isEmptyObject(postMarkersCluster)){
				for (var group in postMarkersCluster){
					if (getDistance(postData[id], postData[postMarkersCluster[group][0]]) <= 30){
						postMarkersCluster[group].push(id);
						return false;
					}
				}
			}

			postMarkersCluster.push([id]);

			var group = postMarkersCluster.length-1;
			var postMarker = new googleMapsCustomMarker({
				map: mainMap,
				id: 'markerGroup-'+group,
				position: new google.maps.LatLng(
					parseFloat(postData[id][0]),
					parseFloat(postData[id][1])
				),
				icon: {
					url: baseUrl + (postData[id][2] ? postData[id][2] :
						'www/images/template/post-icon.png'),
					width: 46,
					height: 63
				},
				data: {id: id}
			});

			google.maps.event.addListener(postMarker, 'mouseover', function(e){
				var self = this, $markerElement = $('#' + self.id).find('img');
				$('#' + self.id).css({zIndex: 100001});

				if ($markerElement.data('ui-tooltip')){
					return true;
				}

				$markerElement.tooltip({
					items: '*',
					show: 0,
					hide: .1,
					tooltipClass: 'postTooltip',
					content: 'Loading...',
					position: {
						of: $markerElement,
						my: 'center bottom',
						at: 'center top',
						using: function(position, feedback){
							$(this).find('.arrow').remove();

							var arrow = $('<div/>').addClass('arrow');

							if (feedback.vertical == 'bottom'){
								position.top = position.top - 17;
								arrow.addClass('bottom').appendTo(this);
							} else if (feedback.vertical == 'top'){
								position.top = position.top + 17;
								arrow.addClass('top').prependTo(this);
							}

							if (position.left == 0 || (position.left + $(this).outerWidth()) == $(window).width()){
								arrow.css({left: feedback.target.left-position.left+arrow.width()/3});
							}

							$(this).css(position);
						}
					},
					open: function(event, ui){
						var content = $(event.target).data('tooltip-content');

						if ($.trim(content) !== ''){
							postTooltip_render(content, self.opts.position, event, ui);
						} else {
							postTooltip_content(self.opts.data.id, self.opts.position, event, ui);
						}

						clearTimeout($(event.target).data('tooltip-mouseout'));
						ui.tooltip.hover(
							function(){
								!$(event.target).data('tooltip-ajax') ||
									$(event.target).data('tooltip-ajax').abort();
								clearTimeout($(event.target).data('tooltip-mouseout'));
								clearTimeout($(event.target).data('tooltip-close'));
								$(this).stop(true);
							}, function(){
								ui.tooltip.remove();
								clearTimeout($(event.target).data('tooltip-close'));
								!$(event.target).data('tooltip-ajax') ||
									$(event.target).data('tooltip-ajax').abort();
								$(event.target).data('tooltip-mouseout', setTimeout(function(){
									$(event.target).data('tooltip-content', '');
									$(event.target).parent().css({zIndex: 'inherit'});
								}, .1));
							}
						);
					},
					close: function(event, ui){
						clearTimeout($(event.target).data('tooltip-mouseout'));
						$(event.target).data('tooltip-close', setTimeout(function(){
							ui.tooltip.remove();
							!$(event.target).data('tooltip-ajax') ||
								$(event.target).data('tooltip-ajax').abort();
							$(event.target).data('tooltip-content', '');
							$(event.target).parent().css({zIndex: 'inherit'});
						}, .1));
					}
				}).tooltip('open');
			});

			google.maps.event.addListener(postMarker, 'click', function(e){
				postItem_higlight(this.opts.data.id);
			});

			postMarkers[group] = postMarker;
		}

		/**
		 * googleMapsCustomMarker
		 */
		function googleMapsCustomMarker(opts){
			this.opts = $.extend({
				id: '',
				hide: false
			}, opts);

			this.setMap(opts.map);
		}
		googleMapsCustomMarker.prototype = new google.maps.OverlayView();
		googleMapsCustomMarker.prototype.draw = function(){
			var self = this;

			if (!self.div){
				self.div = $('<div/>')
					.css({position: 'absolute', cursor: 'pointer'})
					.append($('<img/>', {
						src: self.opts.icon.url,
						width: self.opts.icon.width,
						height: self.opts.icon.height
					}));

				if ($.trim(self.opts.id) !== ''){
					self.div.attr('id', self.opts.id);
					self.id = self.opts.id;
				}

				if (self.opts.hide){
					self.div.hide();
				} else {
					self.div.show();
				}

				google.maps.event.addDomListener(self.div[0], 'click', function(event){
					google.maps.event.trigger(self, 'click');
				});

				google.maps.event.addDomListener(self.div[0], 'mouseover', function(event){
					google.maps.event.trigger(self, 'mouseover');
				});

				$(this.getPanes().overlayImage).append(self.div);
			}

			this.setPosition(this.opts.position);
		};
		googleMapsCustomMarker.prototype.remove = function(){
			$(this.div).remove();
			this.div = null;
			this.setMap(null);
		};
		googleMapsCustomMarker.prototype.setPosition = function(position){
			var point = this.getProjection().fromLatLngToDivPixel(position);

			if (point){
				$(this.div).css({
					left: point.x - this.opts.icon.width / 2,
					top: point.y - this.opts.icon.height
				})
			}

			this.opts.position = position;
		};
		googleMapsCustomMarker.prototype.setIcon = function(icon){
			var resetPosition = this.opts.icon.width != icon.width || this.opts.icon.height != icon.height;
			this.opts.icon = icon;

			$(this.div).find('img')
				.attr({
					src: icon.url,
					width: icon.width,
					height: icon.height
				}).css({
					width: icon.width,
					height: icon.height
				});

			this.setPosition(this.opts.position);
			return this;
		};
		googleMapsCustomMarker.prototype.css = function(css){
			return $(this.div).css(css);
		};
	});
};

/**
 * Location error handle.
 */
function handleLocationError(browserHasGeolocation){
	alert(browserHasGeolocation ?
		'Error: The Geolocation service failed.' :
		'Error: Your browser doesn\'t support geolocation.');
}

/**
 * Renders post tooltip.
 */
function postTooltip_render(content, position, event, ui){
	var tooltipContent = $('.ui-tooltip-content', ui.tooltip).html(content);

	$('a.more', tooltipContent).click(function(e){
		e.preventDefault();
		postItem_higlight($(this).attr('data-id'));
	});
	$('a.prev,a.next', tooltipContent).click(function(e){
		e.preventDefault();
		var link = $(this);
		if (link.attr('disabled')){
			return false;
		}
		link.attr('disabled', true);
		postTooltip_content(link.attr('data-id'), position, event, ui);
	});

	ui.tooltip.position($(event.target).tooltip('option', 'position'));
	ui.tooltip.focus();
}

/**
 * Loads post tooltip content.
 */
function postTooltip_content(id, position, event, ui){
	!$(event.target).data('tooltip-ajax') ||
		$(event.target).data('tooltip-ajax').abort();

	$(event.target).data('tooltip-ajax', $.ajax({
		url: baseUrl + 'post/tooltip',
		data: {
			id: id,
			lat: position.lat(),
			lng: position.lng(),
			readmore: 1
		},
		type: 'POST',
		dataType: 'html'
	}).done(function(response){
		$(event.target).data('tooltip-content', response);
		postTooltip_render(response, position, event, ui);
	}));
}

/**
 * Higlights post item.
 */
function postItem_higlight(id){
	var postEl = $('.post[data-id="'+id+'"]');
	if (postEl.size()){
		$('html, body').animate({
			scrollTop: (postEl.offset().top - $('.navbar').outerHeight()) - 1
		}, 1000);
	}

	$('.post').removeClass('higlight');

	var group = postItem_findCluester(id);
	for (var i in postMarkersCluster[group]){
		$('.post[data-id="'+postMarkersCluster[group][i]+'"]')
			.addClass('higlight');
	}
}

/**
 * Higlights post item.
 */
function postItem_findCluester(id){
	for (var group in postMarkersCluster){
		for (var i in postMarkersCluster[group]){
			if (postMarkersCluster[group][i] == id){
				return group;
			}
		}
	}
	return false;
}

/**
 * Convert number radians.
 */
var rad = function(x){
  return x * Math.PI / 180;
};

/**
 * Returns the distance between two points in meters.
 */
var getDistance = function(p1, p2){
  var R = 6378137;
  var dLat = rad(p2[0] - p1[0]);
  var dLong = rad(p2[1] - p1[1]);
  var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(rad(p1[1])) * Math.cos(rad(p2[1])) *
    Math.sin(dLong / 2) * Math.sin(dLong / 2);
  var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  var d = R * c;
  return d;
};

/**
 * Async load CSS files.
 */
function loadCss(url){
    var link = document.createElement("link");
    link.type = "text/css";
    link.rel = "stylesheet";
    link.href = url;
    document.getElementsByTagName("head")[0].appendChild(link);
}
