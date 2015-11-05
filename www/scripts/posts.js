var mainMap,areaCircle,loadXhr,
	// TODO: update on change user location
	userPosition,
	centerPosition,
	postLimit=15,defaultZoom=14,defaultRadius=0.8,groupDistance=0.018939,
	locationIcon=baseUrl+'www/images/template/user-location-icon.png',
	postIcon=baseUrl+'www/images/template/post-icon.png',
	postMarkers={},postMarkersCluster=[],
	disableScroll=false,markerClick=false;

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
	centerPosition = new google.maps.LatLng(mapCenter[0], mapCenter[1]);
	mainMap = new google.maps.Map(document.getElementById('map_canvas'), {
		center: centerPosition,
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
		userPosition = new google.maps.LatLng(userLocation[0], userLocation[1]);
		require(['jquery','jquery-ui'], function(){
			mainMap.panBy(listMap_centerOffset(), 0);

			areaCircle = new googleMapsAreaCircle({
				map: mainMap,
				center: centerPosition,
				radius: getRadius()
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
						var latlng = new google.maps.LatLng(position.coords.latitude,
							position.coords.longitude);
						mainMap.setZoom(defaultZoom);
						if (getDistance([latlng.lat(),latlng.lng()],
							[centerPosition.lat(),centerPosition.lng()]) <= 0){
							$('html, body').animate({scrollTop: '0px'}, 300);
							return true;
						}
						centerPosition = latlng;
						mainMap.setCenter(offsetCenter(mainMap,centerPosition,listMap_centerOffset(true),0));
						areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),listMap_centerOffset(),0), getRadius());
						postList_change();
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

			postList_locationMarker(mapCenter);

			for (var id in postData){
				postItem_render(id);
			}

			if (Object.size(postData) >= postLimit){
				$(window).bind('scroll.load', postList_scrollHandler).scroll();
			}

			google.maps.event.addListener(mainMap, 'dragstart', function(){
				$('#map_canvas :data(ui-tooltip)')
					.tooltip('close')
					.tooltip('option', 'disabled', true);
			});

			google.maps.event.addListener(mainMap, 'dragend', function(){
				$('#map_canvas :data(ui-tooltip)')
					.tooltip('option', 'disabled', false);
				google.maps.event.clearListeners(mainMap, 'idle');
				google.maps.event.addListenerOnce(mainMap, 'idle', function(){
					var newCenter = mainMap.getCenter();
					if (getDistance([newCenter.lat(),newCenter.lng()],
						[centerPosition.lat(),centerPosition.lng()]) <= 0){
						return true;
					}
					centerPosition = offsetCenter(mainMap,mainMap.getCenter(),listMap_centerOffset(),0);
					areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),listMap_centerOffset(),0), getRadius());
					postList_change();
				});
			});

			google.maps.event.addListener(mainMap, 'zoom_changed', function(){
				$('#map_canvas :data(ui-tooltip)')
					.tooltip('close')
					.tooltip('option', 'disabled', true)
					.tooltip('option', 'disabled', false);
				mainMap.setCenter(offsetCenter(mainMap,centerPosition,listMap_centerOffset(true),0));
				areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),listMap_centerOffset(),0), getRadius());
			});

			$(document).click(function(){
				if (!markerClick){
					$('.post').removeClass('higlight');
				}
				markerClick=false;
			});

			$(window).on('resize', function(){
				mainMap.setCenter(offsetCenter(mainMap,centerPosition,listMap_centerOffset(true),0));
				areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),listMap_centerOffset(),0), getRadius());
			});

			$(window).on('resize scroll', function(){
				$('#map_canvas :data(ui-tooltip)').tooltip('close');
			});

			$('#slider')
				.slider({
					max: 1.5,
					min: 0.5,
					step: 0.1,
					value: renderRadius,
					animate: true
				})
				.bind('slidestop', function(event, ui){
					areaCircle.changeCenter(areaCircle.center, ui.value);
					postList_change();
				});

			$('.post-new').click(function(e){
				e.preventDefault();
				$('.post-new-dialog').toggle();
			});
		});

		/**
		 * Renders post item.
		 */
		function postItem_render(id){
			var marker = postItem_marker(id, postData[id]);
			// TODO: edit post, comment, vote...
			$('.post[data-id="'+id+'"]').bind({
				mouseenter: function(){
					if (!marker.data('isRoot')){
						marker.setIcon({
							url: locationIcon,
							width: 46,
							height: 63
						});
					}
					marker.css({zIndex: 100001});
				},
				mouseleave: function(){
					if (!marker.data('isRoot')){
						marker.setIcon({
							url: postIcon,
							width: 46,
							height: 63
						});
					}
					marker.css({zIndex: ''});
				}
			});
		}

		/**
		 * Renders post item marker.
		 */
		function postItem_marker(id, location, data){
			data = data || {};

			if (!$.isEmptyObject(postMarkersCluster)){
				for (var group in postMarkersCluster){
					if (getDistance(location, postMarkers[group].getPosition(true)) <= groupDistance){
						if (postMarkersCluster[group][0]==0){
							postMarkersCluster[group][0]=id;
							postMarkers[group].data('id', id);
						} else {
							postMarkersCluster[group].push(id);
						}
						return postMarkers[group];
					}
				}
			}

			postMarkersCluster.push([id]);

			var group = Object.size(postMarkers),
				postMarker = postList_tooltipmarker({
					id: group,
					position: location,
					data: $.extend({id:id}, data),
					content: function(content, marker, event, ui){
						if ($.trim(content) !== ''){
							postTooltip_render(content, marker.getPosition(), event, ui);
							return true;
						}
						postTooltip_content(marker.data('id'), marker.getPosition(), event, ui);
					},
					close: function(event, ui){
						clearTimeout($(event.target).data('tooltip-mouseout'));
						$(event.target).data('tooltip-close', setTimeout(function(){
							ui.tooltip.remove();
							!$(event.target).data('tooltip-ajax') ||
								$(event.target).data('tooltip-ajax').abort();
							$(event.target).data('tooltip-content', '');
							$(event.target).parent().css({zIndex: ''});
						}, .1));
					}
				});

			google.maps.event.addListener(postMarker, 'click', function(e){
				postItem_higlight(this.data('id'));
				markerClick=true;
			});

			postMarkers[group] = postMarker;
			return postMarker;
		}

		/**
		 * Change list action.
		 */
		function postList_change(){
			$(window).unbind('scroll.load');
			$('html, body').animate({scrollTop: '0px'}, 300);
			if (Object.size(postMarkers)>0){
				$('#map_canvas :data(ui-tooltip)').tooltip('destroy');
				for (var group in postMarkers){
					postMarkers[group].remove();
				}
			}
			postData={};
			postMarkers={};
			postMarkersCluster=[];
			postList_locationMarker();
			if (loadXhr) loadXhr.abort();
			postList_load();
		}

		/**
		 * Ajax load posts.
		 */
		function postList_load(start){
			start = start || 0;
			var position = areaCircle.center;

			loadXhr = ajaxJson({
				url: baseUrl+'post/list',
				data: {
					radius: getRadius(),
					keywords: $('#postSearch [name=keywords]').val(),
					filter: $('#postSearch [name=filter]').val(),
					center: [
						areaCircle.center.lat(),
						areaCircle.center.lng()
					],
					start: start,
					// TODO: render
					// 'new': $('#addNewsForm [name=new\\[\\]]').map(function(){
						// return $(this).val();
					// }).get()
				},
				done: function(response){
					if (start == 0){
						$('.posts').html('');
					}

					if (response.empty){
						$('.posts').html(response.empty);
						return true;
					}

					for (var id in response.data){
						postData[id]=[response.data[id][0],response.data[id][1]];
						$('.posts').append(response.data[id][2]);
						postItem_render(id);
					}

					if (Object.size(response.data) >= postLimit){
						$(window).bind('scroll.load', postList_scrollHandler).scroll();
					}
				}
			})
		}

		function postList_tooltipmarker(options){
			var marker = new googleMapsCustomMarker({
				map: mainMap,
				id: 'markerGroup-'+options.id,
				position: new google.maps.LatLng(
					parseFloat(options.position[0]),
					parseFloat(options.position[1])
				),
				icon: {
					url: options.data.isRoot==true ? locationIcon : postIcon,
					width: 46,
					height: 63
				},
				data: options.data,
				addClass: options.data.isRoot===true ?'rootMarker':''
			});

			google.maps.event.addListener(marker, 'mouseover', function(e){
				var self = this, $markerElement = $('#' + this.id).find('img');
				this.css({zIndex: 100001});

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
						$('#map_canvas :data(ui-tooltip)').not(event.target).tooltip('close');

						if ($(event.target).tooltip('option', 'disabled')){
							return false;
						}

						options.content($(event.target).data('tooltip-content'), self, event, ui);
					},
					close: function(event, ui){
						if (typeof options.close === 'function'){
							options.close(event, ui);
						}
					}
				}).tooltip('open');
			});

			return marker;
		}

		/**
		 * Load posts on window scroll action.
		 */
		function postList_scrollHandler(){
			if (disableScroll){
				return false;
			}

			if ($(window).scrollTop() + $(window).height() > $(document).height() - $(window).height() * 0.7){
				$(window).unbind('scroll.load');
				postList_load(Object.size(postData));
			}
		}

		/**
		 * Renders user location marker.
		 */
		function postList_locationMarker(center){
			var center = [areaCircle.center.lat(),areaCircle.center.lng()];
			if (getDistance(userLocation, center) <= getRadius()){
				postItem_marker(0, userLocation, {isRoot:true});
			} else if (mainMap.getBounds().contains(userPosition)){
				postMarkers[0] = postList_tooltipmarker({
					id: 0,
					position: userLocation,
					data: {isRoot:true},
					content: function(content, marker, event, ui){
						if ($.trim(content) !== ''){
							$('.ui-tooltip-content', ui.tooltip).html(content);
							return true;
						}
						$.ajax({
							url: baseUrl+'post/user-tooltip',
							type: 'GET',
							dataType: 'html'
						}).done(function(response){
							$(event.target).data('tooltip-content', response);
							$('.ui-tooltip-content', ui.tooltip).html(response);
							ui.tooltip.position($(event.target).tooltip('option', 'position'));
						})
					}
				});
			}
		}

		/**
		 * googleMapsCustomMarker
		 */
		function googleMapsCustomMarker(opts){
			this.opts = opts;
			this.setMap(opts.map);
		}
		googleMapsCustomMarker.prototype = new google.maps.OverlayView();
		googleMapsCustomMarker.prototype.draw = function(){
			var self = this;

			if (!self.div){
				self.div = $('<div/>')
					.addClass(this.opts.addClass)
					.css($.extend({position: 'absolute', cursor: 'pointer'}, this.opts.css))
					.append($('<img/>', {
						src: self.opts.icon.url,
						width: self.opts.icon.width,
						height: self.opts.icon.height
					}));

				if ($.trim(self.opts.id) !== ''){
					self.div.attr('id', self.opts.id);
					self.id = self.opts.id;
				}

				self.div.show();

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
		googleMapsCustomMarker.prototype.getPosition = function(array){
			return array === true ? [this.opts.position.lat(),this.opts.position.lng()] :
				this.opts.position;
		};
		googleMapsCustomMarker.prototype.setIcon = function(icon){
			if (this.div){
				var resetPosition = this.opts.icon.width != icon.width ||
					this.opts.icon.height != icon.height;
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
			}

			return this;
		};
		googleMapsCustomMarker.prototype.css = function(css){
			return $(this.div).css(css);
		};
		googleMapsCustomMarker.prototype.data = function(param, value){
			if (typeof value !== 'undefined'){
				this.opts.data[param] = value;
				return value;
			}

			return typeof this.opts.data[param] !== 'undefined' ?
				this.opts.data[param] : null;
		};
	});

	/**
	 * googleMapsAreaCircle
	 */
	function googleMapsAreaCircle(options){
		this.setValues(options);
		this.makeCircle();
		return this;
	};
	googleMapsAreaCircle.prototype = new google.maps.OverlayView;
	googleMapsAreaCircle.prototype.draw = function(){}
	googleMapsAreaCircle.prototype.makeCircle = function(){
		this.polyArea = new google.maps.Polygon({
			map: this.map,
			paths: [
				this.drawCircle(this.center, 1000, 1),
				this.drawCircle(this.center, this.radius, -1)
			],
			strokeColor: '#0000FF',
			strokeOpacity: 0.1,
			strokeWeight: 0.1,
			fillColor: '#000000',
			fillOpacity: 0.3,
			draggable: false
		});
	}
	googleMapsAreaCircle.prototype.changeCenter = function(center,radius){
		this.polyArea.setMap(null);
		this.center = center;
		this.radius = radius;
		this.makeCircle();
	}
	googleMapsAreaCircle.prototype.drawCircle = function(point, userRadius, dir){
		var d2r = Math.PI / 180;
		var r2d = 180 / Math.PI;
		var earthsradius = 3963;
		var points = 32;
		var rlat = (userRadius / earthsradius) * r2d;
		var rlng = rlat / Math.cos(point.lat() * d2r);
		var extp = new Array(); 

		if (dir == 1){
			var start = 0;
			var end = points + 1;
		} else {
			var start = points + 1;
			var end = 0;
		}

		for (var i = start; (dir == 1 ? i < end : i > end); i = i + dir){
			var theta = Math.PI * (i / (points/2));
			ey = point.lng() + (rlng * Math.cos(theta));
			ex = point.lat() + (rlat * Math.sin(theta));
			extp.push(new google.maps.LatLng(ex, ey));
		}

		return extp;
	}
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
	clearTimeout($(event.target).data('tooltip-mouseout'));

	ui.tooltip.bind({
		mouseenter: function(){
			!$(event.target).data('tooltip-ajax') ||
				$(event.target).data('tooltip-ajax').abort();
			clearTimeout($(event.target).data('tooltip-mouseout'));
			clearTimeout($(event.target).data('tooltip-close'));
			$(this).stop(true);
		},
		mouseleave: function(){
			$(this).remove();
			clearTimeout($(event.target).data('tooltip-close'));
			!$(event.target).data('tooltip-ajax') ||
				$(event.target).data('tooltip-ajax').abort();
			$(event.target).data('tooltip-mouseout', setTimeout(function(){
				$(event.target).data('tooltip-content', '');
				$(event.target).parent().css({zIndex: ''});
			}, .1));
		}
	});

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
			keywords: $('#postSearch [name=keywords]').val(),
			filter: $('#postSearch [name=filter]').val(),
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
		// TODO: open read more content
		// xxx.find('.moreButton').click();
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
 * Returns map center offcet.
 */
function listMap_centerOffset(reverse){
	var postsWidth=$('.posts').outerWidth()+16,
		windowWidth=$(window).width(),
		offset=(windowWidth-postsWidth)/2-windowWidth/2;
	if (reverse) offset*=-1;
	return offset;
}

/**
 * Returns offset map center.
 */
function offsetCenter(map,latlng,offsetx,offsety){
	var scale = Math.pow(2, map.getZoom());
	var worldCoordinateCenter = map.getProjection().fromLatLngToPoint(latlng);
	var pixelOffset = new google.maps.Point((offsetx/scale) || 0,(offsety/scale) ||0)

	var worldCoordinateNewCenter = new google.maps.Point(
		worldCoordinateCenter.x - pixelOffset.x,
		worldCoordinateCenter.y + pixelOffset.y
	);

	return map.getProjection().fromPointToLatLng(worldCoordinateNewCenter);;
}

/**
 * Returns area circle radius.
 */
function getRadius(){
	if ($('#slider').data('ui-slider')){
		return $('#slider').slider('option', 'value');
	}
	return renderRadius;
}

/**
 * Returns the distance between two points in meters.
 */
var getDistance = function(p1, p2){
	if (p1[0] === p2[0] && p1[1] === p2[1]){
		return 0;
	}

	var theta = p1[1] - p2[1];
	var dist = Math.sin(p1[0] * Math.PI / 180) * Math.sin(p2[0] * Math.PI / 180) +
		Math.cos(p1[0] * Math.PI / 180) * Math.cos(p2[0] * Math.PI / 180) *
		Math.cos(theta * Math.PI / 180);
	var miles = (Math.acos(dist) * 180 / Math.PI) * 60 * 1.1515;
	return miles;
};

/**
 * Returns length of a javascript object.
 */
Object.size = function(obj){
	var size = 0, key;
	for (key in obj){
		if (obj.hasOwnProperty(key)) size++;
	}
	return size;
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

// TODO: require from common js
function ajaxJson(url, settings){
	if (typeof url === 'string'){
		settings.url = url;
	} else if (typeof url === 'object'){
		settings = url;
	}

	settings.dataType = 'json';

	var jqxhr = $.ajax($.extend(settings, {type: 'POST'}))
		.done(function(data, textStatus, jqXHR){
			if (typeof data !== 'object' || data.status == 0){
				alert(typeof data === 'object' ? data.message : ERROR_MESSAGE);
				return false;
			}

			if (typeof settings.done === 'function'){
				return settings.done(data, textStatus, jqXHR);
			}
		});

	return jqxhr;
}
