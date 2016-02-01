var isList=isList||false,isLogin=isLogin||false,isTouch=false,
mainMap,centerPosition,areaCircle,googleMapsCustomMarker,googleMapsAreaCircle,
userPosition,loadXhr,
postData=postData||[],postMarkers={},postMarkersCluster={},
defaultMinZoom=13,defaultMaxZoom=15,renderRadius=0.8,
defaultZoom=mapZoom(),postLimit=15,groupDistance=0.018939,
disableScroll=false,markerClick=false,isTouch=false,
postIcon={
	url: assetsBaseUrl+'www/images/template/post-icon35x50.png',
	width: 35, height: 50
},
locationIcon={
	url: assetsBaseUrl+'www/images/template/user-location-icon34x49.png',
	width: 34, height: 49
},
postActiveIcon={
	url: assetsBaseUrl+'www/images/template/post-active-icon35x51.png',
	width: 35, height: 51
};

loadStyle(assetsBaseUrl+'bower_components/jquery-ui/themes/base/jquery-ui.min.css');

require.config({paths: {
	'jquery': assetsBaseUrl+'bower_components/jquery/dist/jquery.min',
	'jquery-ui': assetsBaseUrl+'bower_components/jquery-ui/jquery-ui.min',
	'jquery-validate': assetsBaseUrl+'bower_components/jquery-validation/dist/jquery.validate.min',
	'textarea_autosize': assetsBaseUrl+'bower_components/textarea-autosize/src/jquery.textarea_autosize',
	'facebook-sdk': 'http://connect.facebook.net/en_US/sdk',
	'google.maps': 'https://maps.googleapis.com/maps/api/js?v=3&libraries=places&callback=renderView_callback'
}});
require(['google.maps']);
require(['jquery'], function(){
	$(function(){
		if ($('body').hasClass('touch')){
			isTouch = true;
		}

		$(window).on('resize', resizeHandler);
		resizeHandler();

		if (isLogin){
			var notification = function(){
				ajaxJson({
					url: baseUrl+'contacts/friends-notification',
					done: function(response){
						var menu = $('.main-menu');
						$('.community .count', menu).html(response.friends > 0 ? response.friends : '');
						$('.message .count', menu).html(response.messages > 0 ? response.messages : '');
						setTimeout(notification, 3600);
					},
					fail: function(data, textStatus, jqXHR){
						if (data && data.code == 401){
							window.location.href = baseUrl;
							return false;
						}
					}
				});
			};

			notification();
		} else {
			require(['jquery-validate'], function(){
				$('#login').validate({
					errorPlacement: function(error, element){},
					rules: {
						email: {
							required: true,
							email: true
						},
						password: {
							required: true
						}
					}
				});
			});
		}

		$('#menu-button').click(function(){
			$(this).next('ul').toggleClass('open');
		});

		$('.dropdown-toggle').click(function(e){
			e.preventDefault();
			e.stopPropagation();
			$('.dropdown-menu')
				.not($(this).parent().find('.dropdown-menu').toggle())
				.hide()
				.parent().find('.dropdown-toggle .caret.up').removeClass('up');
		});

		$(document).click(function(e){
			if (e.button == 2){
				return true;
			}
			$('.dropdown-menu').hide();
			$('.dropdown-toggle .caret.up').removeClass('up');
		});

		$('.community .dropdown-toggle').click(function(){
			var dropdown = $(this).parent();

			if (!$('.dropdown-menu', dropdown).is(':visible')){
				return true;
			}

			$('.load', dropdown).show();
			$('.empty,.community-row', dropdown).remove();

			ajaxJson({
				url: baseUrl+'contacts/requests',
				done: function(response){
					var load = $('.load', dropdown).hide();
					if (!response.data){
						load.after($('<li/>').addClass('empty')
							.append($('<span/>').text('No new followers')));
						return true;
					}
					for (var x in response.data){
						load.after($('<li/>').addClass('community-row').append(
							$('<a/>', {href: response.data[x].link}).append(
								$('<div/>').addClass('thumb').append(
									$('<img/>').attr({
										src: response.data[x].image,
										width: 40,
										height: 40
									})
								),
								$('<div/>').addClass('name').html(response.data[x].name + ' is<br/>following you')
							)
						));
					}
				}
			});
		});
	});
});
require(['facebook-sdk'], function(){
	window.fbAsyncInit = function(){
		FB.init({
			appId: facebook_appId,
			xfbml: true,
			cookie: true,
			version: 'v2.5'
		});
	};
});

function renderView_callback(){
	centerPosition = isList ?
		new google.maps.LatLng(opts.latitude,opts.longitude):
		new google.maps.LatLng(post.lat,post.lng);
	mainMap = new google.maps.Map(document.getElementById('map_canvas'), {
		zoom: isList?defaultZoom:15,
		minZoom: defaultMinZoom,
		maxZoom: defaultMaxZoom,
		disableDefaultUI: true,
		scrollwheel: false,
		disableDoubleClickZoom: true,
		styles: [{
			featureType: 'poi',
			stylers: [{visibility: 'off'}]
		}]
	});

	google.maps.event.addListenerOnce(mainMap, 'idle', function(){
		mainMap.setCenter(offsetCenter(mainMap,centerPosition,offsetCenterX(true),offsetCenterY(true)));
		require(['jquery','jquery-ui'], function(){
			require(['textarea_autosize']);
			$(function(){
				if (!isList){
					if (isLogin){
						postSearch_searchButton($('form.postSearch'));
					}

					postMarkers[0] = postList_tooltipmarker({
						map: mainMap,
						id: 0,
						icon: postIcon,
						position: [post.lat,post.lng],
						content: function(content, marker, event, ui){
							$('.ui-tooltip-content', ui.tooltip)
								.html(userAddressTooltip(post.address, owner.image));
							ui.tooltip.position($(event.target).tooltip('option', 'position'));
						},
						openTooltip: true
					});

					var postContainer = $('.post[data-id='+post.id+']');
					postItem_renderContent(post.id,postContainer);

					return true;
				}

				userPosition=new google.maps.LatLng(user.location[0], user.location[1]);

				$('form.postSearch').each(function(){
					var form = $(this);
					if ($.trim($('[name=keywords]', form).val()) !== ''){
						postSearch_clearButton(form);
					} else {
						postSearch_searchButton(form);
					}
				});

				areaCircle = new googleMapsAreaCircle({
					map: mainMap,
					center: offsetCenter(mainMap,mainMap.getCenter(),offsetCenterX(),offsetCenterY()),
					radius: renderRadius
				});

				var controlUIzoomIn = $('<div/>', {title: 'Zoom in'}).addClass('zoom_in')
					.append($('<img/>', {src: '/www/images/template/zoom_in25x25.png'})
						.attr({width: 25, height: 25})).get(0);

				google.maps.event.addDomListener(controlUIzoomIn, 'click', function(){
					mainMap.setZoom(mainMap.getZoom()+1);
				});

				var controlUIzoomOut = $('<div/>', {title: 'Zoom out'}).addClass('zoom_out')
					.append($('<img/>', {src: '/www/images/template/zoom_out25x25.png'})
						.attr({width: 25, height: 25})).get(0);

				google.maps.event.addDomListener(controlUIzoomOut, 'click', function(){
					mainMap.setZoom(mainMap.getZoom()-1);
				});

				var controlUImyLocation = $('<div/>', {title: 'Zoom out'}).addClass('my_location')
					.append($('<img/>', {src: '/www/images/template/my_location.png'})
						.attr({width: 20, height: 18})).get(0);

				google.maps.event.addDomListener(controlUImyLocation, 'click', function(){
					var isPoint = typeof opts.point !== 'undefined';
					delete opts.point;
					mainMap.setZoom(defaultZoom);
					if (getDistance([userPosition.lat(),userPosition.lng()],
						[centerPosition.lat(),centerPosition.lng()]) <= 0){
						$('html, body').animate({scrollTop: '0px'}, 300);
						if (isPoint) postList_change();
						return true;
					}
					centerPosition = userPosition;
					mainMap.setCenter(offsetCenter(mainMap,centerPosition,offsetCenterX(true),offsetCenterY(true)));
					areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),offsetCenterX(),offsetCenterY()), getRadius());
					postList_change();
				});

				mainMap.controls[google.maps.ControlPosition.RIGHT_TOP].push(
					$('<div/>').addClass('customMapControl')
						.append(controlUIzoomIn, controlUIzoomOut, controlUImyLocation)[0]
				);

				postList_locationMarker([opts.latitude,opts.longitude]);

				if (typeof opts.point === 'undefined'){
					postList_load();
				} else {
					for (var id in postData){
						postItem_render(id);
					}

					if (Object.size(postData) >= postLimit){
						postList_scrollHandler(true);
					}
				}

				google.maps.event.addListener(mainMap, 'dragstart', function(){
					$('.post-new__dialog').remove();
					$('#map_canvas :data(ui-tooltip)')
						.tooltip('close')
						.tooltip('option', 'disabled', true);
					delete opts.point;
				});

				google.maps.event.addListener(mainMap, 'dragend', function(){
					$('#map_canvas :data(ui-tooltip)')
						.tooltip('option', 'disabled', false);
					google.maps.event.clearListeners(mainMap, 'idle');
					google.maps.event.addListenerOnce(mainMap, 'idle', function(){
						centerPosition = offsetCenter(mainMap,mainMap.getCenter(),offsetCenterX(),offsetCenterY());
						areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),offsetCenterX(),offsetCenterY()), getRadius());
						postList_change();
					});
				});

				google.maps.event.addListener(mainMap, 'zoom_changed', function(){
					$('#map_canvas :data(ui-tooltip)')
						.tooltip('close')
						.tooltip('option', 'disabled', true)
						.tooltip('option', 'disabled', false);
					mainMap.setCenter(offsetCenter(mainMap,centerPosition,offsetCenterX(true),offsetCenterY(true)));
					areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),offsetCenterX(),offsetCenterY()), getRadius());
				});

				$(document).click(function(){
					if (!markerClick){
						$('.post').removeClass('higlight');
					}
					markerClick=false;
				});

				$(window).on('resize', function(){
					defaultZoom=mapZoom();
					mainMap.setZoom(defaultZoom);
					mainMap.setCenter(offsetCenter(mainMap,centerPosition,offsetCenterX(true),offsetCenterY(true)));
					areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),offsetCenterX(),offsetCenterY()), getRadius());
				});

				$(window).on('resize scroll', function(){
					$('#map_canvas :data(ui-tooltip)').tooltip('close');
				});

				$('#slider').slider({
					max: 1.5,
					min: 0.5,
					step: 0.1,
					value: renderRadius,
					slide: function(event, ui){
						areaCircle.changeCenter(areaCircle.center, ui.value);
					},
					change: function(event, ui){
						areaCircle.changeCenter(areaCircle.center, ui.value);
						postList_change();
					}
				});

				$('.post-new img').click(function(){
					if ($('.post-new__dialog').size()){
						$('.post-new__dialog').fadeOut(50, function(){
							$(this).remove();
						});
						return true;
					}

					if (getCookie('newpost')==1){
						newPost_dialog();
						return true;
					}

					$('<div/>')
						.append(
							$('<p/>')
								.html('After you write something and hit "Post" you\'ll be asked to add the location the post relates to.')
						)
						.appendTo($('body'))
						.dialog({
							modal: true,
							resizable: false,
							drag: false,
							width: 350,
							dialogClass: 'dialog',
							buttons:{
								OK: function(){
									if ($('#newPost_notify').is(':checked')){
										setCookie('newpost', 1, 365);
									}
									$(this).dialog('close');
									newPost_dialog();
								}
							},
							open: function(event, ui){
								$(event.target).parent().find('.ui-dialog-buttonpane').append(
									$('<div/>')
										.append(
											$('<input/>').val(1)
												.attr({type:'checkbox',id:'newPost_notify',
													checked:getCookie('newpost')==1}),
											$('<label/>').attr({for:'newPost_notify'})
												.text('Don\'t show this message again')
										)
										.addClass('post-new__notify')
								);
							},
							beforeClose: function(event, ui){
								$(event.target).dialog('destroy').remove();
							}
						});
				});

				$('.postSearch input').keydown(function(e){
					if (e.keyCode == 13){
						e.preventDefault();
						var form = $(this).closest('form');
						postSearch_submit(form);
						return false;
					}
				});

				$('.postSearch').submit(function(e){
					e.preventDefault();
					opts.keywords=$(this).find('[name=keywords]').val();
					opts.filter=$(this).find('[name=filter]').val();
					postList_change();
				});

				$('.postSearch [name=filter]').change(function(){
					$(this).closest('form').submit();
				});

				$('.postSearch .dropdown-toggle').click(function(){
					$(this).find('.caret').toggleClass('up');
				});

				$('.postSearch .dropdown-menu a').click(function(e){
					var dropdown = $(this).closest('.dropdown'),
						text = $(this).text();
					e.preventDefault();
					$('option', dropdown).filter(function(){
						return $(this).text() == text; 
					}).prop('selected', true).change();
					$('.dropdown-toggle span:first-child', dropdown).text(text);
				});

				$('.user-location button').click(function(){
					var locationButton = $(this).attr('disabled', true);
					editLocationDialog({
						mapZoom: 14,
						markerIcon: assetsBaseUrl+'www/images/icons/icon_1.png',
						inputPlaceholder: 'Enter address',
						submitText: 'Use This Address',
						defaultAddress: user.address,
						center: centerPosition,
						infoWindowContent: function(address){
							return userAddressTooltip(address, user.image);
						},
						beforeClose: function(event, ui){
							locationButton.attr('disabled', false);
						},
						submit: function(map, event, position, place){
							var location = [position.lat(), position.lng()];

							if (getDistance(user.location,location) == 0){
								$(event.target).dialog('close');
								return true;
							}

							$('html, body').animate({scrollTop: 0}, 0);
							map.setOptions({draggable: false, zoomControl: false});

							var data = {
								latitude: position.lat(),
								longitude: position.lng()
							};

							if (place){
								data = $.extend(data, parsePlaceAddress(place));
							}

							ajaxJson({
								url: baseUrl + 'home/change-address',
								data: data,
								done: function(response){
									user.address = place.formatted_address;
									user.location = location;
									userPosition = position;
									centerPosition = position;
									mainMap.setCenter(offsetCenter(mainMap,centerPosition,offsetCenterX(true),offsetCenterY(true)));
									areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),offsetCenterX(),offsetCenterY()),getRadius());
									postList_change();
									$(event.target).dialog('close');
								},
								fail: function(jqXHR, textStatus){
									map.setOptions({draggable: true, zoomControl: true});
									$('[name=address],[type=submit]', event.target)
										.attr('disabled', false);
								}
							});
						}
					});
				});
			});
		});
	});

	googleMapsCustomMarker = function(opts){
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

		if (typeof this.opts.ready === 'function'){
			this.opts.ready(this);
			delete this.opts.ready;
		}
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

	googleMapsAreaCircle = function(options){
		this.setValues(options);
		this.background = this.drawCircle(this.center, 1000, 1);
		this.makeCircle();
		return this;
	};
	googleMapsAreaCircle.prototype = new google.maps.OverlayView;
	googleMapsAreaCircle.prototype.draw = function(){}
	googleMapsAreaCircle.prototype.makeCircle = function(){
		this.polyArea = this.drawPolygon([this.background,
			this.drawCircle(this.center, this.radius, -1)]);
	}
	googleMapsAreaCircle.prototype.changeCenter = function(center,radius){
		var background = this.drawPolygon([this.background]);
		this.polyArea.setMap(null);
		this.center = center;
		this.radius = radius;
		this.makeCircle();
		background.setMap(null);
	}
	googleMapsAreaCircle.prototype.drawPolygon = function(paths){
		var polygon = new google.maps.Polygon({
			map: this.map,
			paths: paths,
			strokeColor: '#0000FF',
			strokeOpacity: 0.1,
			strokeWeight: 0.1,
			fillColor: '#000000',
			fillOpacity: 0.3,
			draggable: false
		});

		return polygon;
	}
	googleMapsAreaCircle.prototype.drawCircle = function(point, userRadius, dir){
		var d2r = Math.PI / 180;
		var r2d = 180 / Math.PI;
		var earthsradius = 3963;
		var points = 1000;
		var rlat = (userRadius / earthsradius) * r2d;
		var rlng = rlat / Math.cos(point.lat() * d2r);
		var extp = [];

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
}

function loadStyle(url){
    var link = document.createElement('link');
    link.type = 'text/css';
    link.rel = 'stylesheet';
    link.href = url;
    document.getElementsByTagName('head')[0].appendChild(link);
}

function resizeHandler(){
	var postsWidth=$('.posts-container').outerWidth()+16,
		windowWidth=$(window).width(),
		footerWidth=windowWidth-postsWidth;
	$('.footer').width(footerWidth-2).show();

	if (isTouch){
		var postsList = $('.posts');
		postsList.height($(window).height()-postsList.offset().top);
	}
}

function offsetCenter(map,latlng,offsetx,offsety){
	var scale = Math.pow(2, map.getZoom());
	var worldCoordinateCenter = map.getProjection().fromLatLngToPoint(latlng);
	var pixelOffset = new google.maps.Point((offsetx/scale) || 0,(offsety/scale) ||0)

	var worldCoordinateNewCenter = new google.maps.Point(
		worldCoordinateCenter.x - pixelOffset.x,
		worldCoordinateCenter.y + pixelOffset.y
	);

	return map.getProjection().fromPointToLatLng(worldCoordinateNewCenter);
}

function offsetCenterX(reverse){
	var postsWidth=document.getElementsByClassName('posts-container')[0].offsetWidth;
		windowWidth=window.outerWidth,
		offset=(windowWidth-postsWidth)/2-windowWidth/2;
	if (reverse) offset*=-1;
	return offset;
}

function offsetCenterY(reverse){
	var containerHeight=document.getElementsByClassName('map-container')[0].offsetHeight,
		footerHeight=document.getElementsByClassName('footer')[0].offsetHeight,
		offset=(containerHeight-footerHeight)/2 - containerHeight/2;
	if (reverse) offset*=-1;
	return offset;
}

function getDistance(p1, p2){
	p1[0] = parseFloat(p1[0]);
	p1[1] = parseFloat(p1[1]);
	p2[0] = parseFloat(p2[0]);
	p2[1] = parseFloat(p2[1]);

	if (p1[0] == p2[0] && p1[1] == p2[1]){
		return 0;
	}

	var theta = p1[1] - p2[1];
	var dist = Math.sin(p1[0] * Math.PI / 180) * Math.sin(p2[0] * Math.PI / 180) +
		Math.cos(p1[0] * Math.PI / 180) * Math.cos(p2[0] * Math.PI / 180) *
		Math.cos(theta * Math.PI / 180);
	var miles = (Math.acos(dist) * 180 / Math.PI) * 60 * 1.1515;
	return miles;
}

function getRadius(){
	if ($('#slider').data('ui-slider')){
		return $('#slider').slider('option', 'value');
	}
	return renderRadius;
}

function postSearch_searchButton(form){
	$('.keywords', form).append($('<img/>').addClass('search')
		.click(function(){
			postSearch_submit(form);
		})
		.attr({
			width: 14,
			height: 14,
			src: assetsBaseUrl+'www/images/template/search14x14.png',
			alt: 'search'
		})
	);
}

function postSearch_submit(form){
	var keywords = $('[name=keywords]', form);

	if ($.trim(keywords.val()) === ''){
		keywords.focus();
		return false;
	}

	$('.search', form).remove();

	if (isList){
		postSearch_clearButton(form);
	}

	form.submit();
}

function postSearch_clearButton(form){
	$('.keywords', form).append($('<img/>').addClass('clear')
		.attr({
			width: 14,
			height: 14,
			src: assetsBaseUrl+'www/images/template/close14x14.png',
			alt: 'clear'
		})
		.click(function(){
			$('[name=keywords]', form).val('');
			form.submit();
			$(this).remove();
			postSearch_searchButton(form);
		})
	);
}

function postList_tooltipmarker(options){
	var marker = new googleMapsCustomMarker({
		map: options.map,
		id: 'markerGroup-'+options.id,
		position: new google.maps.LatLng(
			parseFloat(options.position[0]),
			parseFloat(options.position[1])
		),
		icon: options.icon,
		data: options.data,
		addClass: options.addClass,
		ready: function(marker){
			$markerElement = $('#' + this.id).find('img');
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

					options.content($(event.target).data('tooltip-content'), marker, event, ui);
				},
				close: function(event, ui){
					if (typeof options.close === 'function'){
						options.close(event, ui);
					}
				}
			});

			if (options.openTooltip){
				$markerElement.tooltip('open');
				$(document).one('click', function(){
					$markerElement.tooltip('close');
				});
			}
		}
	});

	google.maps.event.addListener(marker, 'mouseover', function(e){
		this.css({zIndex: 100001});
	});

	return marker;
}

function postItem_imageDimensions(width, height){
	var dialogWidth = width,
		dialogHeight = height,
		minWidth = 150,
		minHeight = 150,
		maxWidth = 980,
		maxHeight = Math.max($(window).height()-150,540);

	if (dialogWidth < minWidth && dialogHeight < minHeight){
		var scaleUp = Math.max(
			(minWidth || dialogWidth) / dialogWidth,
			(minHeight || dialogHeight) / dialogHeight
		);

		if (scaleUp > 1){
			dialogWidth = dialogWidth * scaleUp;
			dialogHeight = dialogHeight * scaleUp;
		}
	} else if (dialogWidth > maxWidth || dialogHeight > maxHeight){
		var scaleDown = Math.min(
			(maxWidth || dialogWidth) / dialogWidth,
			(maxHeight || dialogHeight) / dialogHeight
		);

		if (scaleDown < 1){
			dialogWidth = dialogWidth * scaleDown;
			dialogHeight = dialogHeight * scaleDown;
		}
	}

	return [dialogWidth, dialogHeight];
}

function postItem_renderContent(id, postContainer){
	if (isLogin){
		$('.like,.dislike', postContainer).click(function(){
			var target = $(this),
				vote = $('.vote', postContainer);

			if (vote.attr('disabled')){
				return false;
			}

			vote.attr('disabled', true);

			ajaxJson({
				url: baseUrl+'home/vote',
				data: {
					news_id: id,
					vote: (target.hasClass('like') ? 1 : -1)
				},
				done: function(response){
					$('.like,.dislike', postContainer).removeClass('active');
					$('._3_copy', vote).html(response.vote);
					switch (response.active){
						case '-1':
							$('.dislike', postContainer).addClass('active');
							break;
						case '1':
							$('.like', postContainer).addClass('active');
							break;
					}
					vote.attr('disabled', false);
				},
				fail: function(){
					vote.attr('disabled', false);
				}
			});
		});

		$('.add-comment', postContainer).click(function(e){
			e.preventDefault();
			$('.post-comment__new', postContainer).removeClass('hidden')
				.find('textarea').focus();
			$(this).remove();
		});

		$('.social_share .email', postContainer).click(function(e){
			e.preventDefault();
			$('body').css({overflow: 'hidden'});
			$('<div/>').addClass('post__share-email')
				.append(
					$('<img/>').attr({
						width: 48,
						height: 48,
						src: assetsBaseUrl+'www/images/mail_send.gif'
					}),
					$('<span/>').addClass('title').text('Send Message'),
					$('<div/>').addClass('content').append(
						$('<form/>').append(
							$('<div/>').append(
								$('<input/>', {
									type: 'email',
									name: 'to',
									placeholder: 'Please enter receiver email address...'
								})
							),
							$('<div/>').append(
								$('<textarea/>', {
									name: 'message',
									placeholder: 'Please enter message...'
								})
							),
							$('<div/>').append(
								$('<input/>', {type:'submit',value:'Send'}),
								$('<input/>', {type:'button',value: 'Cancel'})
									.click(function(){
										$('.post__share-email').dialog('close');
									})
							),
							$('<input/>', {type:'hidden',name:'news_id'}).val(id)
						)
					)
				)
				.appendTo($('body'))
				.dialog({
					modal: true,
					resizable: false,
					drag: false,
					width: 540,
					height: 260,
					dialogClass: 'dialog',
					beforeClose: function(event, ui){
						$('body').css({overflow:'visible'});
						$(event.target).dialog('destroy').remove();
					},
					open: function(event, ui){
						$('form', event.target).submit(function(){
							return false;
						});
						require(['jquery-validate'], function(){
							$('form', event.target).validate({
								rules: {
									to: {required:true,email:true},
									message: {required:true}
								},
								submitHandler: function(form){
									ajaxJson({
										url: baseUrl+'info/public-message-email',
										data: $(form).serialize(),
										done: function(response){
											$(event.target).empty().append(
												$('<div/>').addClass('success').append(
													$('<img/>', {src: assetsBaseUrl+'www/images/correct.gif'}),
													$('<span/>').text('Message sent')
												)
											);
										}
									});
								}
							});
						});
					}
				});
		});
	}

	$('> .image img', postContainer).click(function(){
		var scrollTop = $(window).scrollTop(),
			offsetTop = $('.posts').offset().top,
			width = parseFloat($(this).attr('data-width')),
			height = parseFloat($(this).attr('data-height')),
			dimensions = postItem_imageDimensions(width, height);

		$('.posts-container').css({
			top: -(scrollTop - offsetTop),
			position: 'fixed'
		});

		$('<div/>')
			.append(
				$('<img/>', {
					src: $(this).attr('data-src'),
					width: dimensions[0],
					height: dimensions[1]
				}).addClass('post-image')
			)
			.appendTo($('body'))
			.dialog({
				modal: true,
				resizable: false,
				drag: false,
				width: dimensions[0]+10,
				height: dimensions[1]+15,
				dialogClass: 'dialog',
				open: function(event, ui){
					disableScroll = true;
					$('body').css('overflowX', 'auto');
					$(window).bind('resize.dialog', function(){
						dimensions = postItem_imageDimensions(width, height);
						$(event.target)
							.dialog('option', 'width', dimensions[0]+10)
							.dialog('option', 'height', dimensions[1]+15)
							.find('.post-image')
								.width(dimensions[0])
								.height(dimensions[1]);
					});
					$(this).closest('.ui-dialog').css({zIndex:50001});
				},
				beforeClose: function(event, ui){
					disableScroll = false;
					$('.posts-container').css({
						top: offsetTop,
						position: 'relative',
						maxHeight: 'auto'
					});
					$('body').css('overflowX', 'hidden');
					$('html,body').animate({scrollTop: scrollTop}, 0);
					$(event.target).dialog('destroy').remove();
					$(window).unbind('resize.dialog');
				}
			});
	});

	$('.post-comment__item', postContainer).each(function(){
		comment_render($(this));
	});

	$('.social_share .facebook', postContainer).click(function(e){
		var link = $(this).attr('href');
		e.preventDefault();
		require(['facebook-sdk'], function(){
			FB.ui({method:'share',href:link});
		});
	});

	$('.view-comments,.post-comments__more a', postContainer).click(function(e){
		e.preventDefault();
		$('.view-comments,.post-comments__more', postContainer).hide();
		ajaxJson({
			url: baseUrl+'post/comments',
			data: {
				id: id,
				start: $('.post-comment__item', postContainer).size()
			},
			done: function(response){
				if (!response.data){
					return true;
				}

				var commentsContainer = $('.post-coments', postContainer);

				for (var i in response.data){
					comment_render($(response.data[i]).prependTo(commentsContainer));
				}

				if (response.label){
					$('.view-comments', postContainer).html(response.label).show();
					$('.post-comments__more', postContainer).show();
				}
			}
		});
	});
	$('.edit', postContainer).click(function(e){
		e.preventDefault();

		if ($(this).attr('disabled')){
			return false;
		}

		$(this).attr('disabled', true);

		ajaxJson({
			url: baseUrl+'post/edit',
			data: {id:id},
			done: function(response){
				$('.default-panel', postContainer).hide();
				var editForm = $(response.html).appendTo($('.post_text', postContainer).empty());
				$('textarea', editForm)
					.bind('input paste keypress', function(){
						var body = $(this).val();

						if (/[<>]/.test(body)){
							$(this).val(body.replace(/[<>]/, ''));
							return false;
						}

						if (body.length > 500){
							$(this).val(body.substring(0, 499));
							alert("Sorry! You can not enter more then 500 charactes.");
							return false;
						}

						return true;
					})
					.textareaAutoSize()
					.focus();
				$('.social_share', postContainer).append(
					$('<div/>').addClass('edit-panel').append(
						$('<a/>').text('Change location')
							.click(function(e){
								e.preventDefault();
								var $address = $('[name=address]', editForm),
									$latitude = $('[name=latitude]', editForm),
									$longitude = $('[name=longitude]', editForm);

								editLocationDialog({
									mapZoom: 14,
									markerIcon: assetsBaseUrl+'www/images/icons/icon_1.png',
									inputPlaceholder: 'Enter address',
									submitText: 'Save Location',
									defaultAddress: $address.val(),
									center: new google.maps.LatLng($latitude.val(), $longitude.val()),
									infoWindowContent: function(address){
										return userAddressTooltip(address, user.image);
									},
									submit: function(map, dialogEvent, position, place){
										var address = place?place.formatted_address:'';
										map.setOptions({draggable: false, zoomControl: false});

										$address.val(address);
										$latitude.val(position.lat());
										$longitude.val(position.lng());

										ajaxJson({
											url: baseUrl + 'home/save-news-location',
											data: $('[name=id],[name=latitude],[name=longitude],[name=address]', postContainer).serialize(),
											done: function(response){
												if (isList){
													if (getDistance([centerPosition.lat(), centerPosition.lng()],
														[position.lat(), position.lng()])<=getRadius()){
														postItem_delete(id);
														postData[id][0]=position.lat();
														postData[id][1]=position.lng();
														postItem_marker(id, postData[id]);
													} else {
														centerPosition = position;
														mainMap.setCenter(offsetCenter(mainMap,centerPosition,offsetCenterX(true),offsetCenterY(true)));
														areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),offsetCenterX(),offsetCenterY()), getRadius());
														postList_change();
													}
												} else {
													post.address = address;
													centerPosition = position;
													postMarkers[0].setPosition(position);
													mainMap.setCenter(offsetCenter(mainMap,centerPosition,offsetCenterX(true),offsetCenterY(true)));
												}
												$(dialogEvent.target).dialog('close');
											},
											fail: function(jqXHR, textStatus){
												map.setOptions({draggable: true, zoomControl: true});
												$('[name=address],[type=submit]', dialogEvent.target).attr('disabled', false);
											}
										});
									}
								});
							}),
						$('<a/>').text('Delete')
							.click(function(e){
								e.preventDefault();

								if (!confirm('Are you sure you want to delete?')){
									return false;
								}

								if ($(this).attr('disabled')){
									return false;
								}

								var editButtons = $('.edit-panel a', postContainer)
									.attr('disabled', true);
								ajaxJson({
									url: baseUrl+'home/delete',
									data: {id:id},
									done: function(response){
										if (!isList){
											window.location.href = baseUrl;
											return true;
										}

										$('.post[data-id="'+id+'"]').remove();
										postItem_delete(id);
										delete postData[id];
										$('.posts input[value='+id+']').remove();
									},
									fail: function(data, textStatus, jqXHR){
										editButtons.attr('disabled', false);
									}
								});
							}),
						$('<a/>').text('Done Editing')
							.click(function(e){
								e.preventDefault();

								if ($(this).attr('disabled')){
									return false;
								}

								var body = $('textarea', editForm);

								if ($.trim(body.val()) === ''){
									body.focus();
									return false;
								}

								var editButtons = $('.edit-panel a', postContainer)
									.attr('disabled', true);

								ajaxJson({
									url: baseUrl+'home/save-news',
									data: editForm.serialize(),
									beforeSend: function(){
										body.attr('disabled', true);
									},
									done: function(response){
										$('.post_text', postContainer).html(response.html);
										$('.edit-panel', postContainer).remove();
										$('.default-panel', postContainer).show();
										$('.edit', postContainer).attr('disabled', false);
									},
									fail: function(data, textStatus, jqXHR){
										editButtons.attr('disabled', false);
									}
								});
							})
					)
				);
			}
		});
	});

	var input = $('.post-comment__new textarea', postContainer)
		.bind('input paste keypress', function(e){
			if (!isLogin){
				alert('Please register or log in to do that.');
				window.location.href = baseUrl;
				return false;
			}

			var target = $(this),
				body = target.val();

			if (/[<>]/.test(body)){
				target.val(body.replace(/[<>]/, ''));
				return false;
			}

			if (keyCode(e) === 13 && !e.shiftKey){
				if ($.trim(body) === ''){
					return false;
				}

				target.attr('disabled', true);

				ajaxJson({
					url: baseUrl+'post/comment',
					data: {post_id:id,comment:body},
					done: function(response){
						e.preventDefault();
						target.val('').css({height:''}).attr('disabled', false).focus();
						var loadMore = $('.post-comments__more', postContainer);
						comment_render($(response.html).insertBefore(loadMore.size() ?
							loadMore : $('.post-comment__new', postContainer)));
					},
					fail: function(data, textStatus, jqXHR){
						target.attr('disabled', false);
					}
				});
			}
		});

	require(['textarea_autosize'], function(){
		input.textareaAutoSize();
	});
}

function comment_render(comment){
	var id = comment.attr('data-id'),
		deleteButton = $('.post-comment__delete', comment);

	comment.bind({
		mouseenter: function(){
			deleteButton.show();
		},
		mouseleave: function(){
			deleteButton.hide();
		}
	});

	deleteButton.click(function(){
		if (!confirm('Are you sure you want to delete?')){
			return false;
		}

		ajaxJson({
			url: baseUrl+'home/delete-comment',
			data: {id:id},
			done: function(response){
				comment.remove();
			}
		});
	});
	
	$('.moreButton', comment).click(function(e){
		e.preventDefault();

		if ($(this).attr('disabled')){
			return false;
		}

		$(this).attr('disabled', true);

		ajaxJson({
			url: baseUrl + 'home/read-more-comment',
			data: {id:id},
			done: function(response){
				$('.post-comment__body span', comment).html(response.html);
			}
		});
	});
}

function postItem_delete(id){
	var group = postItem_findCluester(id);

	if (postMarkersCluster[group].length === 1){
		if (postMarkers[group].data('isRoot')){
			postMarkers[group].data('id', 0);
			postMarkersCluster[group][0]=0;
		} else {
			postMarkers[group].remove();
			delete postMarkers[group];
			delete postMarkersCluster[group];
		}
	} else {
		for (var i in postMarkersCluster[group]){
			if (postMarkersCluster[group][i] == id){
				postMarkersCluster[group].splice(i, 1);
				break;
			}
		}

		for (var i in postMarkersCluster[group]){
			postMarkers[group].data('id', postMarkersCluster[group][i]);
			break;
		}
	}
}

// TODO: require from common js
function keyCode(event){
	return event.keyCode || event.which;
}

// TODO: require from common js
function editLocationDialog(options){
	$('body').css({overflow: 'hidden'});

	var $form = $('<form/>').attr({autocomplete: false});

	$('<div/>', {'class': 'location-dialog'})
		.append(
			$('<div/>', {id: 'map-canvas'}),
			$('<div/>').addClass('panel').append(
				$form.append(
					$('<input/>', {type: 'text', name: 'address', placeholder: options.inputPlaceholder}),
					$('<input/>', {type: 'submit'}).val('').addClass('search'),
					$('<input/>', {type: 'button'}).val(options.submitText).addClass('save')
				)
			)
		)
		.appendTo('body')
		.dialog({
			modal: true,
			resizable: false,
			drag: false,
			width: 980,
			height: 500,
			dialogClass: 'dialog',
			beforeClose: function(event, ui){
				if (typeof options.beforeClose === 'function'){
					options.beforeClose(event, ui);
				}
				$('.pac-container').remove();
				$('body').css({overflow: 'visible'});
				$(event.target).dialog('destroy').remove();
			},
			open: function(dialogEvent, ui){
				var $editAddress = $('[name=address]', dialogEvent.target)
						.val(options.defaultAddress),
					$submitField = $('[type=submit]', dialogEvent.target),
					$map = $('#map-canvas', dialogEvent.target),
					autocomplete = new google.maps.places.Autocomplete($editAddress[0]),
					geocoder = new google.maps.Geocoder(),
					renderLocation = true,
					renderPlace = false;

				var updateMarker = function(event){
					geocoder.geocode({
						latLng: event.latLng
					}, function(results, status){
						var address = '';

						if (status == google.maps.GeocoderStatus.OK){
							address = results[0].formatted_address;
							renderPlace = results[0];
						} else {
							renderPlace = false;
						}

						infowindow.setContent(options.infoWindowContent(address));
						infowindow.open(map, marker);
						setGoogleMapsAutocompleteValue($editAddress, address);
						renderLocation = true;
					});
				}

				var renderLocationAddress = function(place){
					var address = place.formatted_address,
						location = place.geometry.location;
					infowindow.setContent(options.infoWindowContent(address));
					map.setCenter(location);
					marker.setPosition(location);
					$editAddress.val(address);
					setGoogleMapsAutocompleteValue($editAddress, address);
					renderLocation = true;
					renderPlace = place;
				}

				var map = new google.maps.Map($map[0], {
					zoom: options.mapZoom,
					center: options.center,
					styles: [{
						featureType: 'poi',
						stylers: [{visibility: 'off'}]
					}]
				});

				var marker = new google.maps.Marker({
					draggable: true,
					position: map.getCenter(),
					icon: options.markerIcon
				});

				var infowindow = new google.maps.InfoWindow({
					maxWidth: 220,
					content: options.infoWindowContent(options.defaultAddress)
				});

				google.maps.event.addListenerOnce(map, 'idle', function(){
					marker.setMap(map);
					infowindow.open(map, marker);
				});

				google.maps.event.addListener(map, 'click', function(mapEvent){
					infowindow.close();
					marker.setPosition(mapEvent.latLng);
					updateMarker(mapEvent);
				});

				google.maps.event.addListener(marker, 'dragend', function(markerEvent){
					updateMarker(markerEvent);
				});

				google.maps.event.addListener(autocomplete, 'place_changed', function(){
					var place = autocomplete.getPlace();

					if (!place || typeof place.geometry === 'undefined'){
						return false;
					}

					renderLocationAddress(place);
				});

				$editAddress.on('input', function(){
					renderLocation = false;
				});

				$('form', dialogEvent.target).submit(function(e){
					e.preventDefault();

					if (renderLocation){
						return true;
					}

					var value = $.trim($editAddress.val());

					if (value === ''){
						$editAddress.focus();
						return false;
					}

					$submitField.attr('disabled', true);
					$editAddress.attr('disabled', true);

					geocoder.geocode({
						address: value
					}, function(results, status){
						if (status == google.maps.GeocoderStatus.OK){
							renderLocationAddress(results[0]);
						} else {
							alert('Sorry! We are unable to find this location.');
							renderLocation = false;
							renderPlace = false;
						}

						$submitField.attr('disabled', false);
						$editAddress.attr('disabled', false);
					});
				});

				$('.save', dialogEvent.target).click(function(){
					if (!renderLocation){
						$editAddress.focus();
						return false;
					}

					$('input', dialogEvent.target).attr('disabled', true);
					options.submit(map, dialogEvent, marker.getPosition(), renderPlace);
				});

				if (options.cancelButton === true){
					$form.prepend(
						$('<input/>', {type: 'button'}).addClass('cancel')
							.val('Cancel')
							.click(function(){
								$(dialogEvent.target).dialog('close');
							})
					);
				}
			}
		});
}

function newPost_dialog(){
	var newDialog = $('<div/>').addClass('post-new__dialog')
		.append(
			$('<form/>').addClass('wrapper')
				.append(
					$('<div/>').addClass('user')
						.append($('<img/>')
							.attr({width:43,height:43,src:user.image}))
						.append($('<p/>').text(user.name)),
					$('<div/>').addClass('edit')
						.append($('<textarea/>')
							.attr({name:'news',placeholder:'Share something about a location...'})
							.textareaAutoSize()
							.bind('input paste keypress', function(){
								var body = $(this).val();
								if (/[<>]/.test(body)){
									$(this).val(body.replace(/[<>]/, ''));
									return false;
								}
								if (body.length > 500){
									$(this).val(body.substring(0, 499));
									alert('Sorry! You can not enter more then 500 charactes.');
								}
							})
							.focus(function(){
								var bodyField = $('textarea', newDialog);
								bodyField.attr('placeholder-data', bodyField.attr('placeholder'))
									.removeAttr('placeholder');
							})
							.blur(function(){
								var bodyField = $('textarea', newDialog);
								bodyField.attr('placeholder', bodyField.attr('placeholder-data'))
									.removeAttr('placeholder-data');
							})
						),
					$('<div/>').addClass('action')
						.append(
							$('<img/>')
								.attr({width:27,height:20,
									src:assetsBaseUrl+'www/images/icon-camera.png'})
								.click(function(){
									$('[type=file]',newDialog).click();
								}),
							$('<input/>').hide()
								.attr({type:'file',name:'image',accept:'image/*',size:'1'})
								.change(function(){
									$('.image').remove();
									$(this).parent().prepend(
										$('<div/>').addClass('image').html($(this).val()).append(
											$('<img/>')
												.attr({width:12,height:12,
													src:assetsBaseUrl+'www/images/delete-icon12x12.png'})
												.click(function(){
													$('.image',newDialog).remove();
													$('[type=file]',newDialog).val('');
												})
										)
									);
								}),
							$('<input/>').attr({type:'submit'}).val('Post')
						)
				).submit(function(e){
					e.preventDefault();

					var bodyField = $('textarea', newDialog);
					if ($.trim(bodyField.val()) === ''){
						bodyField.focus();
						return false;
					}

					$('textarea,input', newDialog).attr('disabled', true);

					editLocationDialog({
						mapZoom: 14,
						markerIcon: assetsBaseUrl+'www/images/icons/icon_1.png',
						inputPlaceholder: 'Enter address',
						submitText: 'Post from here',
						cancelButton: true,
						defaultAddress: user.address,
						center: centerPosition,
						infoWindowContent: function(address){
							return userAddressTooltip(address, user.image);
						},
						beforeClose: function(event, ui){
							$('textarea,input', newDialog).attr('disabled', false);
						},
						submit: function(map, dialogEvent, position, place){
							map.setOptions({draggable: false, zoomControl: false});
							newPost_save(position, place?place.formatted_address:'');
						}
					});
				})
		)
		.appendTo('body')
		.fadeIn(150);
	$('textarea', newDialog).focus();
}

function newPost_save(location, address){
	var form = $('.post-new__dialog form'),
		image = $('[name=image]', form),
		reset = (getDistance([centerPosition.lat(),centerPosition.lng()],
			[location.lat(),location.lng()]) > getRadius()),
		data = new FormData();
	data.append('news', $('[name=news]', form).val());
	data.append('latitude', location.lat());
	data.append('longitude', location.lng());

	if ($.trim(address)!==''){
		data.append('address', address);
	}

	if ($.trim(image.val())!==''){
		data.append('image', image[0].files[0]);
	}

	if (reset){
		data.append('reset', 1);
	}

	ajaxJson({
		url: baseUrl+'post/new',
		data: data,
		cache: false,
		contentType: false,
		processData: false,
		done: function(response){
			$('html, body').animate({scrollTop: 0}, 0);
			$('.posts > .empty').remove();

			if (reset){
				postList_reset();
				centerPosition = location;
				mainMap.setCenter(offsetCenter(mainMap,centerPosition,offsetCenterX(true),offsetCenterY(true)));
				areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),offsetCenterX(),offsetCenterY()),getRadius());

				for (var i in response.data){
					$('.posts').append(response.data[i][3]);
				}

				if (Object.size(response.data) >= postLimit){
					postList_scrollHandler();
				}
			} else {
				for (var i in response.data){
					$('.posts').prepend(response.data[i][3]);
					break;
				}
			}

			for (var i in response.data){
				var id = response.data[i][0];
				postData[id]=[response.data[i][1],response.data[i][2]];
				postItem_render(id);
			}

			$('.posts').prepend($('<input/>')
				.attr({type: 'hidden', name: 'new[]'})
				.val(response.data[0][0]));

			$('.location-dialog').dialog('close');
			$('.post-new__dialog').remove();
		}
	});
}

function mapZoom(){
	return window.innerWidth > 1400 ? 15 : 14;
}

function postList_change(){
	$('html, body').animate({scrollTop: '0px'}, 300);
	postList_reset();
	postList_load();
}

function postList_reset(){
	$(isTouch ? '.posts' : window).unbind('scroll.load');
	if (loadXhr) loadXhr.abort();
	if (!$.isEmptyObject(postMarkers)>0){
		$('#map_canvas :data(ui-tooltip)').tooltip('destroy');
		for (var group in postMarkers){
			postMarkers[group].remove();
		}
	}
	postData={};
	postMarkers={};
	postMarkersCluster={};
	postList_locationMarker();
	$('.posts').html('');
}

function postList_load(start){
	start = start || 0;
	var position = areaCircle.center;

	loadXhr = ajaxJson({
		url: baseUrl+'post/load',
		data: {
			radius: getRadius(),
			keywords: opts.keywords,
			filter: opts.filter,
			point: opts.point,
			latitude: areaCircle.center.lat(),
			longitude: areaCircle.center.lng(),
			start: start,
			'new': $('.posts [name=new\\[\\]]').map(function(){
				return $(this).val();
			}).get()
		},
		done: function(response){
			if (response.empty){
				$('.posts').html(response.empty);
				return true;
			}

			for (var i in response.data){
				var id = response.data[i][0],
					latitude = response.data[i][1],
					longitude = response.data[i][2],
					body = response.data[i][3];
				postData[id]=[latitude,longitude];
				$('.posts').append(body);
				postItem_render(id);
			}

			if (Object.size(response.data) >= postLimit){
				postList_scrollHandler(true);
			}
		}
	})
}

function postList_locationMarker(center){
	var center = [areaCircle.center.lat(),areaCircle.center.lng()];
	if (getDistance(user.location, center) <= getRadius()){
		postItem_marker(0, user.location, {isRoot:true});
	} else if (mainMap.getBounds().contains(userPosition)){
		postMarkers[0] = postList_tooltipmarker({
			map: mainMap,
			id: 0,
			position: user.location,
			data: {isRoot:true},
			icon: locationIcon,
			addClass: 'rootMarker',
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

function postTooltip_content(id, position, event, ui){
	!$(event.target).data('tooltip-ajax') ||
		$(event.target).data('tooltip-ajax').abort();

	var url, postTooltip, data = {};

	if (!opts.point || getDistance([position.lat(),position.lng()],
		[opts.latitude,opts.longitude])<=groupDistance){
		data.id=id;
		data.latitude=position.lat();
		data.longitude=position.lng();
		data.keywords=opts.keywords;
		data.filter=opts.filter;
		data.point=opts.point;
		data.readmore=1;
		url=baseUrl+'post/tooltip';
		postTooltip = true;
	} else {
		url=baseUrl+'post/user-tooltip';
		postTooltip = false;
	}

	$(event.target).data('tooltip-ajax', $.ajax({
		url: url,
		data: data,
		type: 'POST',
		dataType: 'html'
	}).done(function(response){
		$(event.target).data('tooltip-content', response);

		if (postTooltip){
			postTooltip_render(response, position, event, ui);
			return true;
		}

		$('.ui-tooltip-content', ui.tooltip).html(response);
		ui.tooltip.position($(event.target).tooltip('option', 'position'));
	}));
}

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
		},
		click: function(){
			markerClick=true;
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

function postItem_render(id){
	var marker = postItem_marker(id, postData[id]),
		postContainer = $('.post[data-id="'+id+'"]');
	postItem_renderContent(id, postContainer);
	postContainer.bind({
		mouseenter: function(){
			marker.setIcon(postActiveIcon);
			marker.css({zIndex: 100001});
		},
		mouseleave: function(){
			marker.setIcon(marker.data('isRoot')==true ?
				locationIcon : postIcon);
			marker.css({zIndex: ''});
		}
	});
	$('.post_text .moreButton', postContainer).click(function(e){
		e.preventDefault();
		postItem_more(postContainer);
	});
}

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

	var newGroup=0;

	if (!$.isEmptyObject(postMarkers)){
		for (var group in postMarkers){
			group = parseInt(group);
			if (group >= newGroup){
				newGroup = group+1;
			}
		}
	}

	postMarkersCluster[newGroup]=[id];

	var postMarker = postList_tooltipmarker({
		map: mainMap,
		id: newGroup,
		position: location,
		data: $.extend({id:id}, data),
		icon: data.isRoot==true ? locationIcon : postIcon,
		addClass: data.isRoot===true ?'rootMarker':'',
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

	postMarkers[newGroup] = postMarker;
	return postMarker;
}

function postList_scrollHandler(scroll){
	var target = $(isTouch ? '.posts' : window).bind('scroll.load', function(){
		if (disableScroll){
			return false;
		}

		var scrollTop = $(this).scrollTop()+$(this).height(),
			scrollHeight = isTouch ? this.scrollHeight : $(document).height();

		if (scrollTop > scrollHeight * 0.7){
			$(this).unbind('scroll.load');
			postList_load(Object.size(postData));
		}
	});

	if (scroll == true){
		target.scroll();
	}
}

function postItem_more(postContainer){
	var link = $('.post_text .moreButton', postContainer);
	if (!link.size() || link.attr('disabled')){
		return false;
	}
	link.attr('disabled', true);

	ajaxJson({
		url: baseUrl+'home/read-more-news',
		data: {id:postContainer.attr('data-id')},
		done: function(response){
			$('.post_text', postContainer).html(response.html);
			link.remove();
		}
	});
}

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
		postItem_more($('.post[data-id="'+postMarkersCluster[group][i]+'"]')
			.addClass('higlight'));
	}
}

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
				if (typeof settings.fail === 'function'){
					settings.fail(data, textStatus, jqXHR);
				}
				alert(typeof data === 'object' ? data.message : 'Internal server error');
				return false;
			}

			if (typeof settings.done === 'function'){
				return settings.done(data, textStatus, jqXHR);
			}
		}).fail(function(jqXHR, textStatus){
			if (jqXHR.readyState == 0 || jqXHR.status == 0){
				return true;
			}
			if (typeof settings.fail === 'function'){
				settings.fail({}, textStatus, jqXHR);
			}
			alert('Internal server error');
		});

	return jqxhr;
}

// TODO: require from common js
function setGoogleMapsAutocompleteValue(input, value){
	input.blur();
	$('.pac-container .pac-item').addClass('hidden');
	setTimeout(function(){
		input.val(value).change();
	}, .1);
}

// TODO: require from common js
function userAddressTooltip(address, image){
    return '<div class="location-dialog__user">' +
		'<img src="' + image + '" />' +
		'<div class="address">' + address + '</div>' +
	'</div>';
}

// TODO: move to common js
function setCookie(name, value, days){
    var d = new Date();
    d.setTime(d.getTime() + (days * 86400000));
    document.cookie = name+'='+value+'; expires='+d.toUTCString();
} 

// TODO: move to common js
function getCookie(name){
    var name = name+'=';
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
    }
    return '';
}

// TODO: move to common js
Object.size = function(obj){
	var size = 0, key;
	for (key in obj){
		if (obj.hasOwnProperty(key)) size++;
	}
	return size;
};

// TODO: move to common js
function parsePlaceAddress(place){
	var placeData = {}, addressFields = {
		street_number: 'street_number',
		route: 'street_name',
		locality: 'city',
		administrative_area_level_1: 'state',
		country: 'country',
		postal_code: 'zip'
	};

	for (var i in place.address_components){
		var component = place.address_components[i],
			type = component.types[0];
		if (addressFields[type]){
			placeData[addressFields[type]] = type=='route'?
				component.long_name:component.short_name;
		}
	}

	return placeData;
}
