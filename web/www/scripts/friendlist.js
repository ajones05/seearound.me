function deleteRowDialog(el,id,name){
	if ($(el).attr('disabled')){
		return false;
	}
	if (!confirm('Are you sure you want to unfollow '+name+'?')){
		return false;
	}
	$(el).attr('disabled',true);
	ajaxJson({
		url:baseUrl+'contacts/friend',
		data:{user:id,action:'reject'},
		done:function(){
			$(el).closest('.invtFrndList').remove();
			loadFriendNews();
		}
	});
}
(function($){
var newsMap, userPosition, newsMapRady = false, newsMarkers = {}, newsMarkersCluster = [],
	newsMapCircle, currentLocationMarke;

	$(function(){
		userPosition = new google.maps.LatLng(profileData.latitude, profileData.longitude);

		renderNewsMap({
			center: userPosition,
			isListing: true
		});

		currentLocationMarker = new NewsMarker({
			position: newsMap.getCenter(),
			map: newsMap,
			id: 'currentLocationMarker',
			icon: {
				url: baseUrl + 'www/images/icons/icon_1.png',
				width: 25,
				height: 36
			},
			addClass: 'newsMarker'
		});

		google.maps.event.addListener(currentLocationMarker, 'mouseover', function(){
			var self = this, $markerElement = $('#' + self.id);

			if ($markerElement.data('ui-tooltip')){
				return true;
			}

			$markerElement
				.tooltip({
					items: '*',
					show: 0,
					hide: .1,
					tooltipClass: 'news-tooltip',
					content: newsUserTooltipContent(imagePath, userName, 'This is me!'),
					position: {
						of: $markerElement,
						my: "center bottom",
						at: "center top",
						using: function(position, feedback){
							position.top = position.top - 17;
							$(this).css(position);
							$("<div>").addClass("arrow").appendTo(this);
						}
					},
					close: function(event, ui){
						ui.tooltip.hover(
							function(){$(this).stop(true).show(); },
							function(){$(this).remove(); }
						);
					}
			})
			.tooltip('open');
		});

		$('#zoomIn').click(function(){
			var zoom = newsMap.getZoom();
			newsMap.setZoom(++zoom);
		});

		$('#zoomOut').click(function(){
			var zoom = newsMap.getZoom();
			newsMap.setZoom(--zoom);
		});

		newsMapCircle = new AreaCircle({
			map: newsMap,
			center: newsMap.getCenter(),
			radious: getRadius()
		});

		$("#slider")
			.slider({
				max: 2.0,
				min: 0.25,
				step: 0.05,
				value: getRadius(),
				animate: true
			})
			.bind("slidestop", function(event, ui){
				newsMapCircle.changeCenter(newsMap.getCenter(), getRadius());
				loadFriendNews();
			})
			.bind("slide", function(event, ui){
				$("#radious").html(ui.value == 1 ? '1.0' : ui.value);
			});

		google.maps.event.addListener(newsMap, 'dragend', function(){
			google.maps.event.clearListeners(newsMap, 'idle');
			google.maps.event.addListener(newsMap, 'idle', function(){
				newsMapCircle.changeCenter(newsMap.getCenter(), getRadius());
				loadFriendNews();
			});
		});

		$('#searchNews').submit(function(e){
			var $form = $(this),
				$searchInput = $('[name=keywords]', $form),
				$searchIcon = $('.search', $form);

			e.preventDefault();

			if ($.trim($searchInput.val()) === ''){
				$searchInput.focus();
				return false;
			}

			$('.clear', $form).remove();
			$searchIcon.hide();
			$searchInput.attr('disabled', true);

			loadFriendNews(function(){
				$searchInput.attr('disabled', false);

				$('.sIcn', $form).append(
					$('<img/>', {'class': 'clear', src: baseUrl + 'www/images/close_12x12.png'}).click(function(){
						$(this).remove();
						$searchInput.val('');
						loadFriendNews(function(){$searchIcon.show(); });
					})
				);
			});
		});

		$("#locationButton").click(function(){
			editLocationDialog({
				mapZoom: 14,
				markerIcon: baseUrl + 'www/images/icons/icon_1.png',
				inputPlaceholder: 'Enter address',
				submitText: 'Use This Address',
				defaultAddress: profileData.address,
				center: newsMapCircle.center,
				infoWindowContent: function(address){
					return userAddressTooltip(address, imagePath);
				},
				submit: function(map, dialogEvent, position, place){
					if (latlngDistance(position,userPosition)<=0){
						$(dialogEvent.target).dialog('close');
						return true;
					}

					$('html,body').animate({scrollTop:0},0);
					map.setOptions({draggable:false,zoomControl:false});

					locationTimezone(position,function(timezone){
						var data = {
							latitude:position.lat(),
							longitude:position.lng(),
							timezone:timezone,
							keywords:$('#searchNews [name=keywords]').val(),
							radius:getRadius()
						};

						if (place){
							data = $.extend(data, parsePlaceAddress(place));
						}

						ajaxJson({
							url:baseUrl+'contacts/change-address',
							data:data,
							done:function(response){
								userPosition = position;

								profileData.address = place.formatted_address;
								profileData.latitude = position.lat();
								profileData.longitude = position.lng();

								newsMap.setCenter(position);
								newsMapCircle.changeCenter(position, 0.8);
								currentLocationMarker.setPosition(position);

								resetMarkersBefore();

								if (response.posts){
									for (var i in response.posts){
										renderListingMarker(response.posts[i]);
									}
									resetMarkersAfter();
								}

								$(dialogEvent.target).dialog('close');
							},
							fail:function(jqXHR, textStatus){
								map.setOptions({draggable:true,zoomControl:true});
								$('[name=address],[type=submit]',dialogEvent.target)
									.attr('disabled',false);
							}
						});
					});
				}
			});
		});

		if (typeof posts !== 'undefined'){
			resetMarkersCluster();

			for (var i in posts){
				renderListingMarker(posts[i]);
			}

			resetMarkersAfter();
		}

		$("#search")
			.val('')
			.autocomplete({
				minLength: 1,
				source: function (request, callback){
					$("#sacrchWait").show();
					ajaxJson({
						url:baseUrl+'contacts/search',
						data:{keywords:request.term},
						done: function(response){
							callback(response.result);
							$("#sacrchWait").hide();
						},
						fail: function(){
							$("#sacrchWait").hide();
						}
					});
				},
				focus: function (event, ui){
					$("#search").val(ui.item.name);
					return false;
				},
				select: function (event, ui){
					window.location.href = baseUrl + "home/profile/user/" + ui.item.id;
				}
			})
			.data("ui-autocomplete")._renderItem = function (ul, item){
				return $("<li/>")
					.data("item.autocomplete", item)
					.append(
						$('<a/>').append(
							$('<div/>', {'class': 'ui_image'}).append($('<img/>', {height: 50, width: 50, src: item.image})),
							$('<div/>', {'class': 'ui_main_text'}).append(
								$('<span/>', {'class': 'ui_name'}).text(item.name),
								$('<br/>'),
								$('<span/>', {'class': 'ui_address'}).text(item.address)
							)
						)
					)
					.appendTo(ul);
			};

		$('.postClass_after').click(function(){
			var loadBlock=$(this),loadImg=loadBlock.find('img').show();
			ajaxJson({
				url:baseUrl+'contacts/friends-list-load',
				data:{offset:$('.invtFrndList:not(#friendList)').size()},
				done:function(response){
					loadImg.hide();

					if (response.data){
						for (var i in response.data){
							$("#friendList").append(response.data[i]);
						}
					}

					if (!response.data || response.data.length < 15){
						loadBlock.remove();
					}
				}
			});
		});
	});

function loadFriendNews(callback){
	resetMarkersBefore();

	ajaxJson({
		url:baseUrl+'contacts/load-friend-news',
		data: {
			keywords:$('#searchNews [name=keywords]').val(),
			latitude:newsMap.getCenter().lat(),
			longitude:newsMap.getCenter().lng(),
			radius:getRadius()
		},
		done: function(response){
			if (typeof callback == 'function'){
				callback();
			}

			if (!response.data){
				return false;
			}

			for (var i in response.data){
				renderListingMarker(response.data[i]);
			}

			resetMarkersAfter();
		}
	});
}
function resetMarkersBefore(){
	if (!$.isEmptyObject(newsMarkers)){
		for (var id in newsMarkers){
			newsMarkers[id].remove();
			delete newsMarkers[id];
		}
	}

	resetMarkersCluster();
}
function resetMarkersAfter(){
	resetMarkersCluster();

	if (newsMapRady){
		setTimeout(function(){
			updateMarkersCluster();
		}, .1);
	} else {
		google.maps.event.addListener(newsMap, 'idle', function(){
			updateMarkersCluster();
		});
	}
}

function createNewsMarker(options, contentCallback){
	var marker = new NewsMarker($.extend({
		id: 'newsMarker' + options.data.post[0]
	}, options));

	google.maps.event.addListener(marker, 'mouseover', function(){
		var self = this, $markerElement = $('#' + self.id);

		if ($markerElement.data('ui-tooltip')){
			return true;
		}

		$markerElement
			.tooltip({
				items: '*',
				show: 0,
				hide: .1,
				tooltipClass: 'news-tooltip',
				content: contentCallback(self),
				position: {
					of: $markerElement,
					my: "center bottom",
					at: "center top",
					using: function(position, feedback){
						position.top = position.top - 17;
						$(this).css(position);
						$("<div>").addClass("arrow").appendTo(this);
					}
				},
				open: function(event, ui){
					ui.tooltip.hover(
						function(){
							$(this).stop(true).show();
						},
						function(){
							$(this).remove();
						}
					);
				},
				close: function(event, ui){
					var isOpen = false;

					ui.tooltip.hover(
						function(){
							$(this).stop(true).show();
							isOpen = true;
						},
						function(){
							$(event.target).tooltip('close');
						}
					);

					setTimeout(function(){
						if (isOpen){
							return false;
						}

						$(ui.tooltip).remove();

						if ($(event.target).is(':ui-tooltip')){
							$(event.target).tooltip('destroy');
						}
					}, .1);
				}
		})
		.tooltip('open');
	});

	newsMarkers[options.data.post[0]] = marker;

	return marker;
}

function newsUserTooltipContent(image, name, body){
	var content = '<div class="tooltip-content"><img src="' + image + '" /><div><h4>' + name + '</h4>';

	if ($.trim(body) !== ''){
		content += '<div>' + body.substring(0, 500) + '</div>';
	}

	content += '</div></div>';

	return content;
}

function renderNewsMap(options){
	var options = $.extend({
		zoom: 14,
		minZoom: 13,
		maxZoom: 15,
		disableDefaultUI: true,
		panControl: false,
		zoomControl: false,
		scaleControl: false,
		streetViewControl: false,
		overviewMapControl: false,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		isListing: false
	}, options);

	newsMap = new google.maps.Map($('#map_canvas')[0], options);

	google.maps.event.addListener(newsMap, 'idle', function(){
		newsMapRady = true;
	});

	google.maps.event.addListener(newsMap, 'dragstart', function(){
		$('.newsMarker:data(ui-tooltip)')
			.tooltip('close')
			.tooltip('option', 'disabled', true);
	});

	google.maps.event.addListener(newsMap, 'dragend', function(){
		$('.newsMarker:data(ui-tooltip)')
			.tooltip('option', 'disabled', false);
	});

	google.maps.event.addListener(newsMap, 'zoom_changed', function(){
		$('.newsMarker:data(ui-tooltip)')
			.tooltip('close')
			.tooltip('option', 'disabled', true)
			.tooltip('option', 'disabled', false);
	});

	$(window).resize(function(){
		$('.newsMarker:data(ui-tooltip)').tooltip('close');
	});
}

function getRadius(){
	var radius = Number($("#radious").html());

	if (radius > 0){
		return radius;
	}

	return 0.8;
}

function renderListingMarker(post){
	var position = new google.maps.LatLng(parseFloat(post[1]), parseFloat(post[2]));
	var marker = createNewsMarker({
		map:newsMap,
		position:position,
		data:{post:post},
		icon:{url:baseUrl+'www/images/icons/icon_2.png',width:20,height:29},
		hide:true,
		addClass:'newsMarker'
	}, function(newsMarker){
			var tooltip = '<div class="tooltip-content">' +
			'<img src="' + baseUrl+newsMarker.opts.data.post[5] + '" />' +
				'<div>' +
					'<h4>' + newsMarker.opts.data.post[4] + '</h4>' +
					'<div>' + newsMarker.opts.data.post[3] + '</div>' +
				'</div>' +
			'</div>' +
			'<div class="tooltip-footer">';
			if (getMarkerPosition(newsMarker.opts.data.post[0])[1].length == 1){
				tooltip += '<a href="' + baseUrl + 'post/' +
					newsMarker.opts.data.post[0] + '">More details</a>';
			} else {
				tooltip += '<a href="' + baseUrl + 'post/center/' +
					newsMarker.opts.data.post[1] + ',' + newsMarker.opts.data.post[2] +
					'?point=1">More details</a>';
			}

			tooltip += '</div>';

			return tooltip;
		}
	);
}

function getMarkerPosition(id){
	for (var groupId in newsMarkersCluster){
		for (var key in newsMarkersCluster[groupId]){
			if (newsMarkersCluster[groupId][key][0] == id){
				var group = newsMarkersCluster[groupId];

				if (newsMarkersCluster[groupId][0][0] == 0){
					group = [];

					for (var i = 1; i < newsMarkersCluster[groupId].length; i++){
						group.push(newsMarkersCluster[groupId][i]);

						if (newsMarkersCluster[groupId][i][0] == id){
							key = i - 1;
						}
					}
				}

				return [key, group];
			}
		}
	}

	return false;
}

function resetMarkersCluster(){
	newsMarkersCluster = [[[0, currentLocationMarker.getPosition()]]];

	if (!$.isEmptyObject(newsMarkers)){
		for (var id in newsMarkers){
			var group = false;

			for (var groupId in newsMarkersCluster){
				if (latlngDistance(newsMarkersCluster[groupId][0][1], newsMarkers[id].getPosition(), 'F') <= 0.018939){
					newsMarkersCluster[groupId].push([newsMarkers[id].opts.data.post[0], newsMarkers[id].getPosition()]);
					group = true;
					break;
				}
			}

			if (!group){
				newsMarkersCluster.push([[newsMarkers[id].opts.data.post[0], newsMarkers[id].getPosition()]]);
			}
		}
	}
}

function updateMarkersCluster(){
	if (newsMarkersCluster[0].length > 1){
		currentLocationMarker.hide();

		for (var i = 1; i < newsMarkersCluster[0].length; i++){
			newsMarkers[newsMarkersCluster[0][i][0]].setIcon({
				url: baseUrl + 'www/images/icons/icon_1.png',
				width: 25,
				height: 36
			});
		}
	} else {
		currentLocationMarker.show();
	}

	for (var groupId in newsMarkersCluster){
		var current = groupId == 0 ? 1 : 0,
			next = groupId == 0 ? 2 : 1;

		if (newsMarkersCluster[groupId].length > current){
			newsMarkers[newsMarkersCluster[groupId][current][0]].show();
		}

		if (newsMarkersCluster[groupId].length > next){
			for (next; next < newsMarkersCluster[groupId].length; next++){
				newsMarkers[newsMarkersCluster[groupId][next][0]].hide();
			}
		}
	}
};

/**
 * NewsMarker
 */
function NewsMarker(opts){
	this.opts = $.extend({
		id: '',
		addClass: '',
		hide: false
	}, opts);

	this.setMap(opts.map);
}

NewsMarker.prototype = new google.maps.OverlayView();

NewsMarker.prototype.draw = function(){
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

		if ($.trim(self.opts.addClass) !== ''){
			self.div.addClass(self.opts.addClass);
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

NewsMarker.prototype.remove = function(){
	$(this.div).remove();
	this.div = null;
	this.setMap(null);
};

NewsMarker.prototype.getPosition = function(){
	return this.opts.position;
};

NewsMarker.prototype.setPosition = function(position){
	var point = this.getProjection().fromLatLngToDivPixel(position);

	if (point){
		$(this.div).css({
			left: point.x - this.opts.icon.width / 2,
			top: point.y - this.opts.icon.height
		})
	}

	this.opts.position = position;
};

NewsMarker.prototype.setIcon = function(icon){
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

NewsMarker.prototype.show = function(){
	$(this.div).show();
};

NewsMarker.prototype.hide = function(){
	$(this.div).hide();
};

/**
 * AreaCircle
 */
function AreaCircle(options){
	this.setValues(options);
	this.makeCircle();
	return this;
};

AreaCircle.prototype = new google.maps.OverlayView;

AreaCircle.prototype.draw = function(){}

AreaCircle.prototype.makeCircle = function(){
	var polyArea =this.polyArea = new google.maps.Polygon({
		paths: [
			this.drawCircle(this.center, 1000, 1),
			this.drawCircle(this.center, this.radious, -1)
		],
		strokeColor: '#0000FF',
		strokeOpacity: 0.1,
		strokeWeight: 0.1,
		fillColor: '#000000',
		fillOpacity: 0.3,
		draggable: false
	});

	polyArea.setMap(this.map);
}

AreaCircle.prototype.changeCenter = function(center,radious){
	this.polyArea.setMap(null);
	this.center = center;
	this.radious = (radious > 0) ? radious : 0.8;
	this.makeCircle();
}

AreaCircle.prototype.drawCircle = function(point, userRadius, dir){
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
})(jQuery);
