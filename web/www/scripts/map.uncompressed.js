var mainMap,
centerPosition,
areaCircle,
googleMapsCustomMarker,
googleMapsAreaCircle,
userPosition,
loadXhr,
scrollTarget,
postData=postData||[],
postMarkers={},
postMarkersCluster={},
defaultMinZoom=13,
defaultMaxZoom=15,
defaultZoom=viewPage=='post'?15:14,
renderRadius=opts.radius?opts.radius:1.5,
postLimit=15,
groupDistance=0.018939,
disableScroll=false,
markerClick=false,
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
	'common': assetsBaseUrl+'www/scripts/common',
	'facebook-sdk': 'https://connect.facebook.net/en_US/sdk',
	'google.maps': 'https://maps.googleapis.com/maps/api/js?key='+mapsKey+
		'&v=3&libraries=places&callback=initMap'
}});

require(['google.maps']);
require(['jquery','common'], function(){
	$(function(){
		scrollTarget = $(isTouch ? '.posts-container .posts' : window);

		$(window).on('resize', resizeHandler);
		resizeHandler();

		if (isLogin){
			$('#menu-button').click(function(){
				$(this).next('ul').toggleClass('open');
			});

			var notification = function(){
				ajaxJson({
					url: baseUrl+'contacts/friends-notification',
					failMessage: false,
					done: function(response){
						var menu = $('.main-menu');
						$('.community .count', menu).html(response.friends > 0 ?
								response.friends : '');
						$('.message .count', menu).html(response.messages > 0 ?
								response.messages : '');
						setTimeout(notification, 2000);
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
						for (var i in response.data){
							load.after($('<li/>').addClass('community-row').append(
								$('<a/>', {href: response.data[i].link}).append(
									$('<div/>').addClass('thumb').append(
										$('<img/>').attr({
											src: response.data[i].image,
											width: 40,
											height: 40
										})
									),
									$('<div/>').addClass('name').html(response.data[i].name +
										' is<br/>following you')
								)
							));
						}
					}
				});
			});
		} else {
			$('.main-menu a').click(function(e){
				e.preventDefault();
				e.stopPropagation();
				guestAction();
			});
		}

		if (viewPage=='profile'){
			require(['jquery-ui'], function(){
				$('strong.karma').tooltip({
					content: function(){return $(this).prop('title'); }
				});
				$('.action .message').on('click', function(){
						userMessageDialog(profile.id);
				});
				$('.action .follow').click(function(){
					var target=$(this);
					if (target.attr('disabled')){
						return false
					}
					target.attr('disabled', true);
					ajaxJson({
						url: baseUrl + 'contacts/friend',
						data: {
							action: isFriend ? 'reject' : 'follow',
							user: profile.id,
							total: 1
						},
						done: function(response){
							target.text(isFriend ? 'Follow' : 'Unfollow')
								.attr('disabled', false);
							if (response.total > 0){
								$('#noteTotal').html(response.total);
							} else {
								$('#noteTotal').hide();
							}
							isFriend = !isFriend;
						}
					});
				});
			});
		}
	});
});

require(['facebook-sdk'], function(){
	window.fbAsyncInit = function(){
		FB.init({
			appId: facebook_appId,
			xfbml: true,
			cookie: true,
			version: facebook_apiVer
		});
	};
});

function initMap(){
	centerPosition = new google.maps.LatLng(opts.lat,opts.lng);
	mainMap = new google.maps.Map(document.getElementById('map_canvas'), {
		zoom: defaultZoom,
		minZoom: defaultMinZoom,
		maxZoom: defaultMaxZoom,
		disableDefaultUI: true,
		scrollwheel: false,
		disableDoubleClickZoom: true,
		styles: [{featureType:'poi',stylers:[{visibility:'off'}]}]
	});

	google.maps.event.addListenerOnce(mainMap, 'idle', function(){
		mainMap.setCenter(offsetCenter(mainMap,centerPosition,offsetCenterX(true),
			offsetCenterY(true)));

		if (viewPage=='post-offline'){
			if (!isLogin){
				$('.postSearch input').keydown(function(e){
						e.preventDefault();
						return guestAction();
				});
			}
			return true;
		}

		require(['jquery','jquery-ui'], function(){

			if (isLogin && (viewPage=='post' || viewPage=='posts')){
				require(['textarea_autosize']);
			}

			$(function(){
				if (viewPage=='post'){
					var searchForm = $('form.postSearch').submit(function(e){
						e.preventDefault();
						guestAction();
					});

					postSearch_searchButton(searchForm);

					postMarkers[0] = postList_tooltipmarker({
						map: mainMap,
						id: 0,
						icon: postIcon,
						position: [opts.lat,opts.lng],
						content: function(content, marker, event, ui){
							$('.ui-tooltip-content', ui.tooltip)
								.html(userAddressTooltip(post.address, post.owner.image));
							ui.tooltip.position($(event.target).tooltip('option', 'position'));
						},
						openTooltip: true
					});

					var postContainer = $('.post[data-id='+post.id+']').show();
					postItem_renderContent(post.id,postContainer);

					$('a.user_avatar[href=#],a.user_name[href=#]').click(function(e){
						e.preventDefault();
						guestAction();
					});

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
					center: offsetCenter(mainMap,mainMap.getCenter(),
						offsetCenterX(),offsetCenterY()),
					radius: renderRadius
				});

				var controlUIzoomIn = $('<div/>', {title: 'Zoom in'})
					.addClass('zoom_in')
					.append($('<img/>', {src: '/www/images/template/zoom_in25x25.png'})
						.attr({width: 25, height: 25})).get(0);

				google.maps.event.addDomListener(controlUIzoomIn, 'click', function(){
					mainMap.setZoom(mainMap.getZoom()+1);
				});

				var controlUIzoomOut = $('<div/>', {title: 'Zoom out'})
					.addClass('zoom_out')
					.append($('<img/>', {src: '/www/images/template/zoom_out25x25.png'})
						.attr({width: 25, height: 25})).get(0);

				google.maps.event.addDomListener(controlUIzoomOut, 'click', function(){
					mainMap.setZoom(mainMap.getZoom()-1);
				});

				var controlUImyLocation = $('<div/>', {title: 'Zoom out'})
					.addClass('my_location')
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
					mainMap.setCenter(offsetCenter(mainMap,centerPosition,
						offsetCenterX(true),offsetCenterY(true)));
					areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),
						offsetCenterX(),offsetCenterY()), getRadius());
					postList_change();
				});

				mainMap.controls[google.maps.ControlPosition.RIGHT_TOP].push(
					$('<div/>').addClass('customMapControl')
						.append(controlUIzoomIn, controlUIzoomOut, controlUImyLocation)[0]
				);

				postList_locationMarker([opts.lat,opts.lng]);

				var postDataSize = Object.size(postData);

				if (postDataSize>0){
					if (viewPage=='posts'){
						$('.posts .post').each(function(){
							postItem_render($(this).show().attr('data-id'));
						});
						if (postDataSize>=postLimit){
							postList_scrollHandler(true);
						}
					} else {
						for (var id in postData){
							postItem_marker(id, postData[id]);
						}
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
						centerPosition = offsetCenter(mainMap,mainMap.getCenter(),
							offsetCenterX(),offsetCenterY());
						areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),
							offsetCenterX(),offsetCenterY()), getRadius());
						postList_change();
					});
				});

				google.maps.event.addListener(mainMap, 'zoom_changed', function(){
					$('#map_canvas :data(ui-tooltip)')
						.tooltip('close')
						.tooltip('option', 'disabled', true)
						.tooltip('option', 'disabled', false);
					mainMap.setCenter(offsetCenter(mainMap,centerPosition,
						offsetCenterX(true),offsetCenterY(true)));
					areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),
						offsetCenterX(),offsetCenterY()), getRadius());
				});

				$(document).click(function(){
					if (!markerClick){
						$('.post').removeClass('higlight');
					}
					markerClick=false;
				});

				$(window).on('resize', function(){
					mainMap.setZoom(defaultZoom);
					mainMap.setCenter(offsetCenter(mainMap,centerPosition,
						offsetCenterX(true),offsetCenterY(true)));
					areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),
						offsetCenterX(),offsetCenterY()), getRadius());
				});

				$(window).on('resize scroll', function(){
					$('#map_canvas :data(ui-tooltip)').tooltip('close');
				});

				$('#slider').slider({
					max: 2.0,
					min: 0.25,
					step: 0.05,
					value: renderRadius,
					slide: function(event, ui){
						areaCircle.changeCenter(areaCircle.center, ui.value);
					},
					change: function(event, ui){
						areaCircle.changeCenter(areaCircle.center, ui.value);
						postList_change();
					}
				});

				if (viewPage=='posts'){
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
				}

				$('.postSearch input').keydown(function(e){
					if (e.keyCode == 13){
						e.preventDefault();
						if (!isLogin){
							return guestAction();
						}
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
					if (!isLogin){
						return guestAction();
					}
					var locationButton = $(this).attr('disabled', true);
					editLocationDialog({
						mapZoom: 14,
						markerIcon: assetsBaseUrl+'www/images/icons/icon_1.png',
						inputPlaceholder: 'Enter address',
						submitText: 'Use This Address',
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

							$('html, body').animate({scrollTop:0},0);
							map.setOptions({draggable: false, zoomControl: false});

							locationTimezone(position,function(timezone){
								var data = {
									radius: getRadius(),
									keywords: opts.keywords,
									latitude: position.lat(),
									longitude: position.lng(),
									timezone: timezone
								};

								if (viewPage=='profile'){
									data.filter=0;
									data.user_id=profile.id;
								} else {
									data.filter=opts.filter;
								}

								if (place){
									data = $.extend(data, parsePlaceAddress(place));
								}

								$('html, body').animate({scrollTop: 0}, 0);
								$('.posts-container .posts > .empty').remove();

								ajaxJson({
									url: baseUrl+'post/change-user-location',
									data:data,
									done:function(response){
										user.location = location;
										userPosition = position;
										centerPosition = position;

										postList_reset();

										mainMap.setCenter(offsetCenter(mainMap,centerPosition,
											offsetCenterX(true),offsetCenterY(true)));
										areaCircle.changeCenter(offsetCenter(mainMap, mainMap.getCenter(),
											offsetCenterX(),offsetCenterY()),getRadius());

										if (viewPage=='profile'){
											if (response.data !== null){
												for (var i in response.data){
													var id=response.data[i][0],
														data=[
															response.data[i][1],
															response.data[i][2]
														];
													postData[id]=data;
													postItem_marker(id, data);
												}
											}
										} else {
											if (response.data){
												for (var i in response.data){
													$('.posts-container .posts').append(response.data[i][3]);

													var id = response.data[i][0];
													postData[id]=[response.data[i][1],response.data[i][2]];
													postItem_render(id);
												}

												if (Object.size(response.data) >= postLimit){
													postList_scrollHandler();
												}
											} else {
												emptyPostsMessage();
											}
										}

										$(event.target).dialog('close');
									},
									fail:function(jqXHR,textStatus){
										map.setOptions({draggable: true, zoomControl: true});
										$('[name=address],[type=submit]', event.target)
											.attr('disabled', false);
									}
								});
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
				.append($('<img/>').prop({
					src:self.opts.icon.url,
					width:self.opts.icon.width,
					height:self.opts.icon.height
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
		var postsList = $('.posts-container .posts');
		postsList.height($(window).height()-postsList.offset().top);
	}

	$('.post-new__dialog textarea').css('max-height', newPost_InputHeight());
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

function getRadius(){
	if ($('#slider').data('ui-slider')){
		return $('#slider').slider('option', 'value');
	}
	return renderRadius;
}

function postSearch_searchButton(form){
	$('.keywords', form).append($('<img/>').addClass('search')
		.click(function(){
			if (!isLogin){
				return guestAction();
			}
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

	if (viewPage=='posts'||viewPage=='profile'){
		$('.keywords img', form).remove();
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
	if (!isLogin){
		var postTimeContainer=$('.post-time',postContainer),
			postTime=new Date(postTimeContainer.attr('data-time'));
		resetTimeAgo();
		postTimeContainer.text($.timeago(postTime))
			.attr('title',formatOutputDate(postTime));
	}

	$('.like,.dislike', postContainer).click(function(){
		var target = $(this),
			vote = $('.vote', postContainer);

		if (!isLogin){
			return guestAction();
		}

		if (vote.attr('disabled')){
			return false;
		}

		vote.attr('disabled', true);

		ajaxJson({
			url: baseUrl+'post/vote',
			data: {
				id: id,
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
		if (!isLogin){
			return guestAction();
		}
		$('.post-comment__new', postContainer).removeClass('hidden')
			.find('textarea').focus();
		$(this).remove();
	});

	$('.social_share .email', postContainer).click(function(e){
		e.preventDefault();
		if (!isLogin){
			return guestAction();
		}
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
								name: 'email',
								placeholder: 'Please enter receiver email address...'
							})
						),
						$('<div/>').append(
							$('<textarea/>', {
								name: 'body',
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
						$('<input/>', {type:'hidden',name:'id'}).val(id)
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
								email:{required:true,email:true},
								body:{required:true}
							},
							submitHandler: function(form){
								ajaxJson({
									url: baseUrl+'post/share-email',
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

	$('> .image img', postContainer).click(function(){
		var scrollTop = $(window).scrollTop(),
			offsetTop = $('.posts-container .posts').offset().top,
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

	$('.view-comments', postContainer).click(function(e){
		e.preventDefault();
		$('.view-comments', postContainer).hide();
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
				var editForm = $('<textarea>', {rows:1,name:'body'})
					.appendTo($('.post_text', postContainer).empty())
					.val(response.body)
					.bind('input paste keypress',validatePost)
					.textareaAutoSize()
					.focus();

				$('.social_share', postContainer).append(
					$('<div/>').addClass('edit-panel').append(
						$('<a/>').text('Change location')
							.click(function(e){
								e.preventDefault();
								editLocationDialog({
									mapZoom: 14,
									markerIcon: assetsBaseUrl+'www/images/icons/icon_1.png',
									inputPlaceholder: 'Enter address',
									submitText: 'Save Location',
									center: new google.maps.LatLng(response.latitude,response.longitude),
									infoWindowContent: function(address){
										return userAddressTooltip(address, user.image);
									},
									submit: function(map, dialogEvent, position, place){
										var data={};
										data.latitude=response.latitude=position.lat();
										data.longitude=response.longitude=position.lng();
										if (place){
											data=$.extend(data,parsePlaceAddress(place));
										}
										map.setOptions({draggable:false,zoomControl:false});
										ajaxJson({
											url:baseUrl+'post/save-location/id/'+id,
											data:data,
											done:function(saveResponse){
												if (viewPage=='posts'){
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
													post.address=saveResponse.address;
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
									url: baseUrl+'post/delete',
									data: {id:id},
									done: function(){
										if (viewPage!='posts'){
											window.location.href = baseUrl;
											return true;
										}

										$('.post[data-id="'+id+'"]').remove();
										postItem_delete(id);
										delete postData[id];
										$('.posts-container .posts input[value='+id+']').remove();
										if (Object.size(postData) == 0){
											emptyPostsMessage();
										}
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

								var body=editForm.val();
								if ($.trim(body)===''){
									editForm.focus();
									return false;
								}

								var editButtons = $('.edit-panel a', postContainer)
									.attr('disabled',true);

								ajaxJson({
									url:baseUrl+'post/save',
									data:{id:id,body:body},
									beforeSend:function(){editForm.attr('disabled',true); },
									done:function(saveResponse){
										$('.post_text', postContainer).html(saveResponse.html);
										$('.edit-panel', postContainer).remove();
										$('.default-panel', postContainer).show();
										$('.edit', postContainer).attr('disabled', false);
									},
									fail:function(data, textStatus, jqXHR){
										editButtons.attr('disabled',false);
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
				return guestAction();
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
						comment_render($(response.html).insertBefore(
							$('.post-comment__new', postContainer)));
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

function emptyPostsMessage(){
	$('.posts').append($('<div/>').addClass('post empty')
		.text('Be the first to post in this area!'))
}

function comment_render(comment){
	var id = comment.attr('data-id'),
		deleteButton = $('.post-comment__delete', comment);

	if (isLogin){
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
				url: baseUrl+'post/delete-comment',
				data: {id:id},
				done: function(response){
					comment.remove();
				}
			});
		});
	} else {
		$('a.post-comment__item-owner[href=#],'+
			'a.post-coment-user-name[href=#]', comment).click(function(e){
			e.preventDefault();
			guestAction();
		});

		var commentTimeContainer=$('.post-coment__date',comment),
			commentTime=new Date(commentTimeContainer.attr('data-time'));
		resetTimeAgo();
		$.timeago.settings.strings.suffixAgo='';
		commentTimeContainer.text($.timeago(commentTime))
			.attr('title',formatOutputDate(commentTime));
	}

	$('.moreButton', comment).click(function(e){
		e.preventDefault();

		if ($(this).attr('disabled')){
			return false;
		}

		$(this).attr('disabled', true);

		ajaxJson({
			url: baseUrl + 'post/read-more-comment',
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

function validatePost(){
	var body=$(this).val();
	if (/[<>]/.test(body)){
		$(this).val(body.replace(/[<>]/, ''));
		return false;
	}
	if (body.length > settings.bodyMaxLength){
		$(this).val(body.substring(0,settings.bodyMaxLength));
		alert('Sorry! You can not enter more then '+settings.bodyMaxLength+
			' charactes.');
	}
	return true;
}

function newPost_dialog(){
	var userBlock=$('<div/>').addClass('user')
		.append($('<img/>',{width:43,height:43,src:user.image}),
			$('<p/>').text(user.name));
	var postBlockFl=$('<textarea/>',{name:'body',
				placeholder:'Share something about a location...'})
		.css('max-height', newPost_InputHeight())
		.textareaAutoSize()
		.bind('input paste keypress',validatePost)
		.focus(function(){
			var bodyEl=$(this);
			bodyEl.attr('placeholder-data',bodyEl.attr('placeholder'))
				.removeAttr('placeholder');
		})
		.blur(function(){
			var bodyEl=$(this);
			bodyEl.attr('placeholder',bodyEl.attr('placeholder-data'))
				.removeAttr('placeholder-data');
		});
	var postBlock=$('<div/>').addClass('edit').append(postBlockFl);
	var actionBlock=$('<div/>').addClass('action')
		.append(
			$('<img/>',{width:27,height:20,
					src:assetsBaseUrl+'www/images/icon-camera.png'})
				.click(function(){
					$(this).closest('form').find('[type=file]').click();
				}),
			$('<input/>',{type:'file',name:'image',accept:'image/*',size:'1'})
				.hide()
				.change(function(){
					var targetEl=$(this),
						containerEl=targetEl.parent();
					containerEl.find('.image').remove();

					if ($.trim(targetEl.val()) === ''){
						return true;
					}

					if ($.inArray(this.files[0]['type'],['image/gif','image/jpeg','image/png'])<0){
						alert('Invalid file type');
						targetEl.val('');
						return false;
					}

					var imageContainer=$('<div/>');
					if (typeof window.FileReader !== 'undefined'){
						var reader=new FileReader();
						reader.onload=function(e){
							imageContainer.prepend($('<img/>').addClass('preview')
								.attr('src', e.target.result));
						}
						reader.readAsDataURL(this.files[0]);
					} else {
						imageContainer.html(targetEl.val());
					}
					containerEl.prepend($('<div/>').addClass('image').append(imageContainer
						.append(
							$('<img/>',{width:12,height:12,
								src:assetsBaseUrl+'www/images/delete-icon12x12.png'})
								.addClass('delete')
								.click(function(){
									$('.image',containerEl).remove();
									$('[type=file]',containerEl).val('');
								})
						)
					));
				}),
			$('<input/>',{type:'submit'}).val('Post')
		);
	var newDialog=$('<div/>').addClass('post-new__dialog');
	var formBlock=$('<form/>').addClass('wrapper').submit(function(e){
		e.preventDefault();

		var body=postBlockFl.val();
		if ($.trim(body) === ''){
			postBlockFl.focus();
			return false;
		}

		var formFields=$(this).find('textarea,input').attr('disabled',true);

		ajaxJson({
			url: baseUrl+'post/before-save',
			data: {body:body},
			done: function(response){
				if (response.post_id){
					$('<div/>').appendTo('body')
						.text('Another user has already shared that same link: '+
							'do you want to see that post?')
						.dialog({
							width: 450,
							modal:true,
							buttons: [{
								text:'Cancel',
								click:function(){
									$(this).dialog('close');
								}
							},{
								text:'See post',
								id:'view-post',
								click:function(){return true; }
							},{
								text:'Post anyway',
								click:function(){
									newPost_addressDialog(newDialog);
									$(this).dialog('close');
								}
							}],
							beforeClose: function(event,ui){
								formFields.attr('disabled',false);
								$(event.target).dialog('destroy').remove();
							},
							open:function(event,ui){
								$('#view-post').wrap($('<a/>',{
									href:baseUrl+'post/'+response.post_id,
									target:'_blank'
								}));
							}
						});
				} else {
					newPost_addressDialog(newDialog);
				}
			},
			fail: function(){
				formFields.attr('disabled',false);
			}
		});
	});

	formBlock.append(userBlock,postBlock);

	if (isLogin && user.is_admin == 1){
		$('<div/>').addClass('options').text('options')
			.appendTo(formBlock)
			.on('click', function(){
				var self=$(this);
				if (self.attr('disabled')){
					return false;
				}
				var urlParams='',
					formData=$('textarea,input[type=hidden]',formBlock).filter(function(){
						return $.trim($(this).val())!=='';
					});

				if (formData.size()){
					urlParams+='?'+formData.serialize();
				}

				self.attr('disabled',true);
				$('<div/>').appendTo($('body'))
					.append(
						$('<div/>').text('Loading...'),
						$('<iframe/>',{
							src:baseUrl+'post/post-options'+urlParams,
							frameborder:0,
							width:'100%',
							id:'post-options'
						})
					).dialog({
						modal:true,
						resizable:false,
						drag:false,
						width:500,
						dialogClass:'dialog fixed new-post-dialog',
						buttons:{
							OK:function(){
								var iframeBody=$(this).find('iframe').contents();
								$('form',iframeBody).submit();
							}
						},
						beforeClose: function(event, ui){
							$(this).dialog('destroy').remove();
							self.attr('disabled',false);
						}
					});
			});
	}
	formBlock.append(actionBlock);
	newDialog.append(formBlock).appendTo('body').fadeIn(150);
	$(postBlockFl).focus();
}

function newPost_InputHeight(){
	return $(window).height()/2;
}

function newPost_addressDialog(dialog){
	editLocationDialog({
		mapZoom: 14,
		markerIcon: assetsBaseUrl+'www/images/icons/icon_1.png',
		inputPlaceholder: 'Enter address',
		submitText: 'Post from here',
		cancelButton: true,
		center: centerPosition,
		infoWindowContent: function(address){
			return userAddressTooltip(address, user.image);
		},
		beforeClose: function(event, ui){
			$('textarea,input',dialog).attr('disabled', false);
		},
		submit: function(map, dialogEvent, position, place){
			map.setOptions({draggable: false, zoomControl: false});
			newPost_save(position,place);
		}
	});
}

function newPost_save(position,place){
	var form = $('.post-new__dialog form'),
		image = $('[name=image]', form),
		reset = (getDistance([centerPosition.lat(),centerPosition.lng()],
			[position.lat(),position.lng()]) > getRadius()),
		data = new FormData();

	form.find('textarea,input[type=hidden]').each(function(){
		var el=$(this);
		data.append(el.attr('name'),el.val());
	});

	data.append('latitude', position.lat());
	data.append('longitude', position.lng());

	if (place){
		var addressData=parsePlaceAddress(place);
		if (Object.size(addressData)){
			for (var field in addressData){
				data.append(field,addressData[field]);
			}
		}
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
			$('.posts-container .posts > .empty').remove();

			if (reset){
				postList_reset();
				centerPosition=position;
				mainMap.setCenter(offsetCenter(mainMap,centerPosition,offsetCenterX(true),offsetCenterY(true)));
				areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),offsetCenterX(),offsetCenterY()),getRadius());

				for (var i in response.data){
					$('.posts-container .posts').append(response.data[i][3]);
				}

				if (Object.size(response.data) >= postLimit){
					postList_scrollHandler();
				}
			} else {
				for (var i in response.data){
					$('.posts-container .posts').prepend(response.data[i][3]);
					break;
				}
			}

			for (var i in response.data){
				var id = response.data[i][0];
				postData[id]=[response.data[i][1],response.data[i][2]];
				postItem_render(id);
			}

			$('.posts-container .posts').prepend($('<input/>')
				.attr({type: 'hidden', name: 'new[]'})
				.val(response.data[0][0]));

			$('.location-dialog').dialog('close');
			$('.post-new__dialog').remove();
		}
	});
}

function postList_change(){
	$('html, body').animate({scrollTop: '0px'}, 300);
	postList_reset();
	postList_load();
}

function postList_reset(){
	scrollTarget.unbind('scroll.load');
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

	if (viewPage!='profile'){
		$('.posts-container .posts').html('');
	}
}

function postList_load(start){
	start = start || 0;
	var position=areaCircle.center,data={
		radius:getRadius(),
		keywords:opts.keywords,
		latitude:areaCircle.center.lat(),
		longitude:areaCircle.center.lng(),
		start:start
	};

	if (viewPage=='profile'){
		data.filter=0;
		data.user_id=profile.id;
	} else {
		data.filter=opts.filter;
		data.point=opts.point;
		data.new=$('.posts-container .posts [name=new\\[\\]]').map(function(){
			return $(this).val();
		}).get();
	}

	loadXhr = ajaxJson({
		url: baseUrl+'post/load',
		data: data,
		done: function(response){
			if (viewPage=='profile'){
				if (response.data !== null){
					for (var i in response.data){
						var id=response.data[i][0],
							data=[
								response.data[i][1],
								response.data[i][2]
							];
						postData[id]=data;
						postItem_marker(id, data);
					}
				}

				return true;
			}

			if (response.empty){
				$('.posts-container .posts').html(response.empty);
				return true;
			}

			for (var i in response.data){
				var id = response.data[i][0],
					latitude = response.data[i][1],
					longitude = response.data[i][2],
					body = response.data[i][3];
				postData[id]=[latitude,longitude];
				$('.posts-container .posts').append(body);
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

function postTooltip_content(position, event, ui, start){
	!$(event.target).data('tooltip-ajax') ||
		$(event.target).data('tooltip-ajax').abort();

	var url, postTooltip, data = {};

	if (!opts.point || getDistance([position.lat(),position.lng()],
		[opts.lat,opts.lng])<=groupDistance){
		data.start=start;
		data.latitude=position.lat();
		data.longitude=position.lng();
		data.keywords=opts.keywords;
		if (viewPage=='profile'){
			data.filter=0;
			data.user_id=profile.id;
		} else {
			data.filter=opts.filter;
			data.point=opts.point;
			data.readmore=1;
		}
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
		postTooltip_content(position, event, ui, link.attr('data-start'));
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
			postTooltip_content(marker.getPosition(), event, ui, 0);
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
	scrollTarget.bind('scroll.load', function(){
		if (disableScroll){
			return false;
		}

		var scrollTop = $(this).scrollTop()+$(this).height(),
			scrollHeight = isTouch ? this.scrollHeight : $(document).height();

		if ((scrollHeight-scrollTop) < $(window).height()*3){
			$(this).unbind('scroll.load');
			postList_load(Object.size(postData));
		}

	});

	if (scroll == true){
		scrollTarget.scroll();
	}
}

function postItem_more(postContainer){
	var link = $('.post_text .moreButton', postContainer);
	if (!link.size() || link.attr('disabled')){
		return false;
	}
	link.attr('disabled', true);

	ajaxJson({
		url: baseUrl+'post/read-more',
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

function guestAction(){
	if (confirm('You are not logged in. Click OK to log in or sign up.')){
		window.location.href = baseUrl;
	}
	return false;
}
