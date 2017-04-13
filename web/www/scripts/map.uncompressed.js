var gl={},
mainMap,
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
postLimit=viewPage =='community'?30:15,
groupDistance=0.03,
disableScroll=false,
markerClick=false,
addressFl=['street_number','street_name','city','state','country','zip'],
categoryMarker={
	1:assetsBaseUrl+'www/images/popover/icons/Icons-lg/ic-lg-food.png',
	2:assetsBaseUrl+'www/images/popover/icons/Icons-lg/ic-lg-safety.png',
	3:assetsBaseUrl+'www/images/popover/icons/Icons-lg/ic-lg-events.png',
	4:assetsBaseUrl+'www/images/popover/icons/Icons-lg/ic-lg-development.png',
	5:assetsBaseUrl+'www/images/popover/icons/Icons-lg/ic-lg-other.png'
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

		$(document).on('click', '.dropdown-toggle,.drp-t', function(e){
			e.preventDefault();
			e.stopPropagation();
			var activeDropdown=$(this).parent().find('.dropdown-menu,.drp');
			if (activeDropdown.parent().hasClass('details')){
				if (activeDropdown.is(':hidden')){
					var spaceBottom=$(window).height() - ($(this).offset().top -
						$(document).scrollTop());
					if (spaceBottom < activeDropdown.height()+25){
						activeDropdown.addClass('top');
					}
				} else {
					activeDropdown.removeClass('top');
				}
			}
			activeDropdown.toggle();
			$('.dropdown-menu,.drp')
				.not(activeDropdown.trigger('closeDrp'))
				.hide()
				.parent().find('.dropdown-toggle .caret,'+
					'.drp-t .caret').removeClass('up');
		});

		$(document).click(function(e){
			if (e.button == 2){
				return true;
			}
			$('.dropdown-menu,.drp').filter(':visible')
				.trigger('closeDrp').hide().removeClass('top');
			$('.dropdown-toggle .caret,'+
				'.drp-t .caret').removeClass('up');
		});

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

			$('.community .dropdown-toggle').click(function(){
				var dropdown = $(this).parent();

				if (!$('.dropdown-menu', dropdown).is(':hidden')){
					return;
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

		if (viewPage=='community'){
			if ($('.posts .post').size()>=postLimit){
				postList_scrollHandler(true);
			}
			$('#peopleSearch').submit(function(e){
				e.preventDefault();
			});
			require(['jquery-ui'], function(){
				$('#peopleSearch [name=keywords]').autocomplete({
					minLength: 1,
					source: function (request, callback){
						ajaxJson({
							url:baseUrl+'contacts/search',
							data:{keywords:request.term},
							done: function(response){
								callback(response.data);
							}
						});
					},
					focus: function (event, ui){
						$(event.target).val(ui.item.name);
						return false;
					},
					select: function (event, ui){
						$(event.target).val(ui.item.name).attr('disabled',true);
						window.location.href=baseUrl+'profile/'+ui.item.id;
						return false;
					}
				}).data('ui-autocomplete')._renderItem=function(ul,item){
					return $(item.html).data('item.autocomplete',item).appendTo(ul);
				};
			});
			$('.posts').on('click','.post-comment__delete',function(){
				var self=$(this);

				if (self.hasClass('disabled')){
					return;
				}

				var userCnt=$(this).closest('.post'),
					name=$('.post-coment-user-name',userCnt).text();
				if (!confirm('Are you sure you want to unfollow '+name+'?')){
					return;
				}

				self.addClass('disabled');

				ajaxJson({
					url: baseUrl + 'contacts/friend',
					data: {
						action: 'reject',
						user: userCnt.attr('data-id')
					},
					done: function(response){
						userCnt.remove();
						postList_reset();
						postList_load();
					}
				});
			});
			$('.posts').on('click','.msg',function(){
				userMessageDialog($(this).closest('.post').attr('data-id'));
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
					var self=$(this);

					if (self.hasClass('disabled')){
						return;
					}

					self.addClass('disabled');

					ajaxJson({
						url: baseUrl + 'contacts/friend',
						data: {
							action: isFriend ? 'reject' : 'follow',
							user: profile.id,
							total: 1
						},
						done: function(response){
							self.text(isFriend ? 'Follow' : 'Unfollow')
								.removeClass('disabled');;
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
					var postContainer = $('.posts>.post').show(),
					postId=postContainer.attr('data-id'),
					searchForm = $('form.postSearch').submit(function(e){
						e.preventDefault();
						guestAction();
					});

					postSearch_searchButton(searchForm);

					postMarkers[0] = postList_tooltipmarker({
						map: mainMap,
						id: 0,
						icon: {
							url:getPostMarker(postData[postId]),
							width: 37,
							height: 53
						},
						position: [opts.lat,opts.lng],
						content: function(content, marker, event, ui){
							var tooltip=userAddressTooltip(postData[postId].address,
								postData[postId].user_image);
							$('.ui-tooltip-content', ui.tooltip).html(tooltip);
							ui.tooltip.position($(event.target).tooltip('option', 'position'));
						},
						openTooltip: true,
						addClass: 'mg'
					});

					postItem_renderContent(postId,postContainer);

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
							postItem_marker(id,[postData[id].lat,postData[id].lng]);
						}
					}
				}

				google.maps.event.addListener(mainMap, 'dragstart', function(){
					closeNpd();
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
					var editPostDlg=renderEditPostDlg($('.pnd .dnp'));
					$('.pnd img').on('click',function(){
						if (editPostDlg.is(':visible')){
							editPostDlg.fadeOut(50,closeNpd);
						} else {
							editPostDlg.fadeIn(150).find('textarea').focus();
						}
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
					if (viewPage!='community'){
						opts.filter=$(this).find('[name=filter]').val();
					}
					opts.category_id=[];
					$(this).find('[name=category_id\\[\\]] [selected]').each(function(){
						opts.category_id.push($(this).val());
					});
					postList_change();
				});

				$('.postSearch').find('[name=filter],[name=category_id\\[\\]]')
					.on('change',function(){
						$(this).closest('form').submit();
				});

				$('.postSearch .drp-t').click(function(){
					$(this).find('.caret').toggleClass('up');
				});

				$('.filter .cat .drp.hsel li').on('click',function(e){
					e.stopImmediatePropagation();
					var drp=$(this).parent().parent().unbind('closeDrp.filterCat');

					if (!$(this).hasClass('ac') || $(this).parent().find('li.ac').size()>1){
						$(this).toggleClass('ac');
					} else {
						alert('Please choose at least one category. Otherwise you won\'t see any posts!');
					}

					drp.bind('closeDrp.filterCat',function(){
						drp.find('[name=category_id\\[\\]] option').each(function(){
							var option = $(this),
								optionText = option.text();
							drp.find('li').each(function(){
								if (optionText == $(this).text()){
									option.attr('selected',$(this).hasClass('ac'));
								}
							});
						})
						drp.unbind('closeDrp.filterCat')
							.closest('form').submit();
					});
				});

				$(document).on('click','.dropdown-menu.hsel>*,.drp.hsel li',function(e){
					e.preventDefault();
					var drpCn=$(this).closest('.dropdown'),
						drpSel=drpCn.find('select'),
						drpOpts=drpSel.find('option'),
						selText=$(this).text();
					if (!drpSel.is('[multiple]')){
						drpOpts.removeAttr('selected');
						$(this).parent().find('li').removeClass('ac');
						$(this).addClass('ac');
					} else {
						$(this).toggleClass('ac');
					}
					drpOpts.filter(function(){
						return $(this).text()==selText;
					}).each(function(){
						$(this).attr('selected',!$(this).is('[selected]'));
					});
					drpSel.change();
					drpCn.find('.dropdown-toggle span:first-child').text(selText);
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

							if (viewPage!='community'){
								$('html, body').animate({scrollTop:0},0);
							}

							map.setOptions({draggable: false, zoomControl: false});

							locationTimezone(position,function(timezone){
								var data = {
									radius: getRadius(),
									keywords: opts.keywords,
									category_id: opts.category_id,
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

								if (viewPage!='community'){
									$('html, body').animate({scrollTop: 0}, 0);
									$('.posts-container .posts > .empty').remove();
								}

								ajaxJson({
									url: baseUrl+'post/change-user-location',
									data:data,
									done:function(response){
										user.location = location;
										userPosition = position;
										centerPosition = position;

										mainMap.setCenter(offsetCenter(mainMap,centerPosition,
											offsetCenterX(true),offsetCenterY(true)));
										areaCircle.changeCenter(offsetCenter(mainMap, mainMap.getCenter(),
											offsetCenterX(),offsetCenterY()),getRadius());

										postList_reset();

										if (viewPage=='profile'){
											if (response.data !== null){
												for (var i in response.data){
													var row=response.data[i];
													postData[row.id]=row;
													postItem_marker(row.id,[row.lat,row.lng]);
												}
											}
										} else {
											if (response.data){
												for (var i in response.data){
													var row=response.data[i];
													if (viewPage!='community'){
														$('.posts-container .posts').append(row.html);
													}
													postData[row.id]=row;
													postItem_render(row.id);
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
	googleMapsCustomMarker.prototype.setIconDimensions = function(w,h){
		if (this.div){
			this.opts.icon.width = w;
			this.opts.icon.height = h;
			$(this.div).find('img')
				.attr({
					width: w,
					height: h
				}).css({
					width: w,
					height: h
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

	$('.dnp textarea').css('max-height', newPost_InputHeight());
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

	if (viewPage=='posts'||viewPage=='profile'||viewPage=='community'){
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
		var postTimeContainer=$('.time',postContainer),
			postTime=new Date(postTimeContainer.attr('data-time'));
		resetTimeAgo();
		postTimeContainer.text($.timeago(postTime))
			.attr('title',formatOutputDate(postTime));
	}

	$('.like',postContainer).click(function(){
		if (!isLogin){
			return guestAction();
		}

		var self=$(this);

		if (self.hasClass('disabled')){
			return;
		}

		self.addClass('disabled');

		ajaxJson({
			url: baseUrl+'post/vote',
			data:{id:id,vote:1},
			done: function(response){
				if (response.active==1){
					self.addClass('active');
				} else {
					self.removeClass('active');
				}
				$('.count',postContainer).html(response.vote);
				self.removeClass('disabled');
			},
			fail: function(){
				self.removeClass('disabled');
			}
		});
	});

	$('.message',postContainer).on('click',function(){
		userMessageDialog(postData[id].user_id);
	});

	$('.follow,.unfollow',postContainer).on('click', function(){
		var self=$(this);

		if (self.hasClass('disabled')){
			return;
		}

		self.addClass('disabled');

		ajaxJson(baseUrl+'contacts/friend',{
			data: {
				action: self.hasClass('follow') ? 'follow' : 'reject',
				user: postData[id].user_id
			},
			done: function(response){
				self.removeClass('disabled');
				$('li:has(.follow,.unfollow)',postContainer).toggle();
			}
		});
	});

	$('.block',postContainer).on('click',function(){
		var self=$(this);

		if (self.hasClass('disabled')){
			return;
		}

		self.addClass('disabled');

		ajaxJson(baseUrl+'post/block-user',{
			data: {user_id:postData[id].user_id},
			done: function(response){
				self.removeClass('disabled');
			}
		});
	});

	$('.flag',postContainer).on('click', function(){
		var self=$(this);

		if (self.hasClass('disabled')){
			return;
		}

		self.addClass('disabled');

		ajaxJson(baseUrl+'post/flag',{
			data: {id:id},
			done: function(response){
				self.removeClass('disabled');
				alertDialog('Thanks, this post has been flagged and will be reviewed.');
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

	$('.dropdown-menu .email', postContainer).click(function(){
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

	$('.dropdown-menu .delete', postContainer).on('click',function(){
		var self=$(this);
		self.closest('.dropdown-menu').hide();

		if (!confirm('Are you sure you want to delete?')){
			return;
		}

		if (self.hasClass('disabled')){
			return;
		}

		self.addClass('disabled');

		ajaxJson({
			url:baseUrl+'post/delete',
			data:{id:id},
			done: function(){deletePostHandler(id); },
			fail: function(){self.removeClass('disabled'); }
		});
	});

	$('.dropdown-menu .fb', postContainer).click(function(){
		require(['facebook-sdk'], function(){
			FB.ui({method:'share',href:baseUrl+'post/'+id});
		});
	});

	$('.post-coments .more span', postContainer).click(function(e){
		if ($(this).hasClass('disabled')){
			return;
		}
		var commMore=$(this).addClass('disabled');
		ajaxJson({
			url: baseUrl+'post/comments',
			data: {
				id: id,
				start: $('.post-comment__item', postContainer).size()
			},
			done: function(response){
				if (response.data==null){
					commMore.remove();
					return;
				}

				var moreLabel=commMore.parent();
				for (var i in response.data){
					comment_render($(response.data[i]).insertAfter(moreLabel));
				}

				if (response.label){
					commMore.html(response.label).removeClass('disabled');
				} else {
					commMore.remove();
				}
			}
		});
	});

	$('.dropdown-menu .edit', postContainer).click(function(){
		var self=$(this);
		if (self.hasClass('disabled')){
			return;
		}

		self.addClass('disabled');

		$('body').append('<div class="ui-widget-overlay ui-front" style="z-index: 50001;"></div>');

		$('html, body').animate({
			scrollTop: (postContainer.offset().top - $('.navbar').outerHeight()) - 7
		}, 1000);

		$('body').css({overflow: 'hidden'});

		ajaxJson({
			url: baseUrl+'post/edit',
			data: {id:id},
			done: function(response){
				var editDlg=$(response.body).css('z-index',50002)
					.appendTo($('body')).show();
				$('input[type=button]',editDlg).on('click',function(){
					closeEditDlg(editDlg,id);
				});
				renderEditPostDlg(editDlg,id);
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

function deletePostHandler(id){
	if (viewPage!='posts'){
		window.location.href=baseUrl;
		return;
	}

	$('.post[data-id="'+id+'"]').remove();
	postItem_delete(id);
	delete postData[id];
	$('.posts-container .posts input[value='+id+']').remove();
	if (Object.size(postData) == 0){
		emptyPostsMessage();
	}
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

		if ($(this).hasClass('disabled')){
			return false;
		}

		$(this).addClass('disabled', true);

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
			postMarkers[group].remove();
			delete postMarkers[group];
			delete postMarkersCluster[group];
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

function newPost_InputHeight(){
	return $(window).height()/2;
}

function newPost_save(dlg,postId){
	var imgEl=$('[name=image]',dlg),
		isNew=typeof postId === 'undefined' ? true : false,
		mapRadius=getRadius(),
		data=new FormData();
	if (!isNew){
		data.append('id',postId);
	}
	$('select,textarea,input[type=hidden]',dlg).each(function(){
		var el=$(this);
		data.append(el.attr('name'),el.val());
	});
	if ($.trim(imgEl.val())!==''){
		data.append('image', imgEl[0].files[0]);
	}
	var latitude=data.get('latitude'),
		longitude=data.get('longitude')
		reset=false;
	if (getDistance([centerPosition.lat(),centerPosition.lng()],
		[latitude,longitude])>mapRadius){
		data.append('reset', 1);
		reset=true;
	}

	ajaxJson({
		url: baseUrl+'post/save',
		data: data,
		cache: false,
		contentType: false,
		processData: false,
		done: function(response){
			if (isNew){
				$('.posts-container .posts > .empty').remove();
				$('html, body').animate({scrollTop: 0}, 0);
			}

			if (reset){
				postList_reset();
				centerPosition=new google.maps.LatLng(latitude,longitude);
				mainMap.setCenter(offsetCenter(mainMap,centerPosition,offsetCenterX(true),offsetCenterY(true)));
				areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),offsetCenterX(),offsetCenterY()),mapRadius);

				for (var i in response.data){
					$('.posts-container .posts').append(response.data[i]['html']);
				}

				if (Object.size(response.data) >= postLimit){
					postList_scrollHandler();
				}

				for (var i in response.data){
					var row=response.data[i];
					postData[row.id]=row;
					postItem_render(row.id);
				}
			} else if (isNew) {
				$('.posts-container .posts').prepend(response.update.html);
				postData[response.update.id]=response.update;
				postItem_render(response.update.id);
			}
			if (isNew){
				$('.posts-container .posts').prepend($('<input/>')
					.attr({type: 'hidden', name: 'new[]'})
					.val(reset?response.data[0].id:response.update.id));
				closeNpd();
			} else {
				if (!reset){
					$('.post[data-id='+postId+']').replaceWith(response.update.html);
					postItem_delete(postId);
					postData[postId]=response.update;
					postItem_render(postId);
				}
				closeEditDlg(dlg,postId);
			}
		}
	});
}

function postList_change(){
	if (viewPage!='community'){
		$('html, body').animate({scrollTop: '0px'}, 300);
	}
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

	if (viewPage!='profile' && viewPage!='community'){
		$('.posts-container .posts').html('');
	}
}

function postList_load(start){
	start = start || 0;
	var position=areaCircle.center,data={
		radius:getRadius(),
		keywords:opts.keywords,
		category_id:opts.category_id,
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

	if (viewPage == 'community'){
		data.nohtml=1;
	}

	loadXhr = ajaxJson({
		url: baseUrl+'post/load',
		data: data,
		done: function(response){
			if (viewPage=='profile'){
				if (response.data !== null){
					for (var i in response.data){
						var row=response.data[i];
						postData[row.id]=row;
						postItem_marker(row.id,[row.lat,row.lng]);
					}
				}

				return true;
			}

			if (viewPage != 'community'){
				if (response.empty){
					$('.posts-container .posts').html(response.empty);
					return true;
				}
			}

			for (var i in response.data){
				var post=response.data[i];
				postData[post.id]=post;
				if (viewPage != 'community'){
					$('.posts-container .posts').append(post.html);
				}
				postItem_render(post.id);
			}

			if (viewPage != 'community'){
				if (Object.size(response.data) >= postLimit){
					postList_scrollHandler(true);
				}
			}
		}
	})
}

function postTooltip_content(position, event, ui, start){
	var tooltipAjax=$(event.target).data('tooltip-ajax');
	!tooltipAjax || tooltipAjax.abort();

	var data = {
		start:start,
		latitude:position.lat(),
		longitude:position.lng(),
		keywords:opts.keywords,
		category_id:opts.category_id
	};

	if (viewPage=='profile'){
		data.filter=0;
		data.user_id=profile.id;
	} else {
		data.filter=opts.filter;
		if (viewPage != 'community'){
			data.point=opts.point;
			data.readmore=1;
		}
	}

	$(event.target).data('tooltip-ajax', $.ajax({
		url: baseUrl+'post/tooltip',
		data: data,
		type: 'POST',
		dataType: 'html'
	}).done(function(response){
		$(event.target).data('tooltip-content', response);
		postTooltip_render(response, position, event, ui);
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
		var link=$(this);
		if (link.hasClass('disabled')){
			return;
		}
		link.addClass('disabled');
		postTooltip_content(position, event, ui, link.attr('data-start'));
	});

	ui.tooltip.position($(event.target).tooltip('option', 'position'));
	ui.tooltip.focus();
}

function postItem_render(id){
	postData[id].marker=postItem_marker(id,[postData[id].lat,postData[id].lng]);
	if (viewPage == 'community'){
		return;
	}
	var postContainer=$('.post[data-id="'+id+'"]');
	postItem_renderContent(id, postContainer);
	postContainer.bind({
		mouseenter: function(){
			var marker=postData[id].marker;
			marker.setIcon({
				url:getPostMarker(postData[id]),
				width:46,
				height:60
			});
			marker.css({zIndex: 100001});
			if (marker.div){
				marker.div.addClass('ac');
			}
		},
		mouseleave: function(){
			var marker=postData[id].marker;
			marker.setIconDimensions(37,53);
			marker.setIcon({
				url:getPostMarker(postData[id]),
				width:37,
				height:53
			});
			marker.css({zIndex: ''});
			if (marker.div){
				marker.div.removeClass('ac');
			}
		}
	});
	$('.body .moreButton',postContainer).click(function(e){
		e.preventDefault();
		postItem_more($(this).closest('.post'));
	});
}

function postItem_marker(id, location, data){
	data = data || {};
	console.log('post location: '+id+' - '+location[0]+','+location[1]);
	if (!$.isEmptyObject(postMarkersCluster)){
		for (var group in postMarkersCluster){
			if (getDistance(location, postMarkers[group].getPosition(true)) < groupDistance){
				if (postMarkersCluster[group][0]==0){
					postMarkersCluster[group][0]=id;
					postMarkers[group].data('id', id);
				} else {
					postMarkersCluster[group].push(id);
				}
				console.log('exist group: '+group)
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
console.log('new group: '+newGroup)
	var postMarker = postList_tooltipmarker({
		map: mainMap,
		id: newGroup,
		position: location,
		data: $.extend({id:id}, data),
		addClass: 'mg',
		icon: {
			url:getPostMarker(postData[id]),
			width: 37,
			height: 53
		},
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
			scrollHeight = isTouch ? this.scrollHeight : $(document).height(),
			maxHeight=$(window).height();

		if (viewPage != 'community'){
			maxHeight *= 3;
		}

		if ((scrollHeight-scrollTop) < maxHeight){
			$(this).unbind('scroll.load');
			if (viewPage =='community'){
				community_load($('.posts .post').size());
			} else {
				postList_load(Object.size(postData));
			}
		}

	});

	if (scroll == true){
		scrollTarget.scroll();
	}
}

function postItem_more(postContainer){
	var link = $('.body .moreButton', postContainer);
	if (!link.size() || link.hasClass('disabled')){
		return false;
	}
	link.addClass('disabled');

	ajaxJson({
		url: baseUrl+'post/read-more',
		data: {id:postContainer.attr('data-id')},
		done: function(response){
			$('.body', postContainer).html(response.html);
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
		var rowId=postMarkersCluster[group][i];
		postItem_more($('.post[data-id="'+rowId+'"]')
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

function getPostMarker(data){
	return data.cid ? categoryMarker[data.cid] :
		assetsBaseUrl+'www/images/template/post-icon35x50.png'
}

function guestAction(){
	if (confirm('You are not logged in. Click OK to log in or sign up.')){
		window.location.href = baseUrl;
	}
	return false;
}

function closeNpd(){
	var dnp=$('.dnp').hide().attr('style','');
	dnp.find('[name=body],[name=image],[name=latitude],[name=longitude]').val('');
	dnp.find('[name=category_id]').prop('selectedIndex',0);
	dnp.find('textarea').css('height','auto');
	dnp.find('textarea,input').attr('disabled',false);
	dnp.find('.image,[name=user_id]').remove();
	dnp.find('.ic.ac,.drp li.ac').removeClass('ac');
	for (var i in addressFl){
		$('[name='+addressFl[i]+']').val('');
	}
}

function renderEditPostDlg(dlg,postId){
	var isNew=typeof postId === 'undefined' ? true : false;
	$('textarea',dlg)
		.css('max-height',newPost_InputHeight())
		.on('input paste keypress',validatePost)
		.on('focus', function(){
			$(this).attr('placeholder-data',$(this).attr('placeholder'))
				.removeAttr('placeholder');
		})
		.on('blur', function(){
			$(this).attr('placeholder',$(this).attr('placeholder-data'))
			.removeAttr('placeholder-data');
		});
	require(['textarea_autosize'],function(){
		$('textarea',dlg).textareaAutoSize();
	});

	$('form',dlg).on('submit',function(e){
		e.preventDefault();
		var form=$(this),
			textEl=form.find('[name=body]'),
			textVal=textEl.val(),
			catEl=form.find('[name=category_id]'),
			latEl=form.find('[name=latitude]'),
			lngEl=form.find('[name=longitude]'),
			submitBtn=form.find('[type=submit]'),
			elErrs={},errLen=0;

		if (submitBtn.data('ui-tooltip') != null){
			submitBtn.tooltip('destroy');
		}

		if ($.trim(textVal) === ''){
			elErrs.text=true;
			errLen++;
		}
		if ($.trim(catEl.val()) === ''){
			elErrs.cat=true;
			errLen++;
		}

		if ($.trim(latEl.val())==='' || $.trim(lngEl.val())===''){
			errLen++;
			elErrs.loc=true;
		}

		if (errLen>0){
			var errMsg='Please add';

			if (elErrs.text===true){
				errMsg+=' text';
			}

			if (elErrs.loc===true){
				if (elErrs.text===true){
					if (errLen==3){
						errMsg+=', ';
					} else {
						errMsg+=' and ';
					}
				} else {
					errMsg+=' ';
				}

				errMsg+='location';
			}
			if (elErrs.cat===true){
				if (errLen>1){
					errMsg+=' and ';
				} else {
					errMsg+=' ';
				}

				errMsg+='category';
			}

			errMsg+='.';

			submitBtn.tooltip({
				items: '*',
				show: 0,
				hide: .1,
				tooltipClass: 'postTooltip',
				content: errMsg,
				position: {
					of: submitBtn,
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
				}
			}).tooltip('open');

			return false;
		}

		var formFields=form.find('textarea,input,.image .delete')
			.attr('disabled',true);

		if (typeof postId !== 'undefined'){
			return newPost_save(dlg,postId);
		}

		ajaxJson({
			url: baseUrl+'post/before-save',
			data: {body:textVal},
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
									newPost_save(dlg);
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
					newPost_save(dlg);
				}
			},
			fail: function(){
				formFields.attr('disabled',false);
			}
		});
	}).find('textarea').on('input',function(){
		$(this).closest('form').find(':data(ui-tooltip)').tooltip('destroy');
	});

	$('.options',dlg).on('click',function(){
		var self=$(this);
		if (self.prop('disabled')){
			return;
		}
		var urlParams='',
			formData=self.closest('form').find('select,textarea,input[type=hidden]')
				.filter(function(){
					return $.trim($(this).val())!=='';
				});

		if (formData.size()){
			urlParams+='?'+formData.serialize();
		}

		self.prop('disabled',true);
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
					self.prop('disabled',false);
				}
			});
		});

	$('.loc',dlg).on('click',function(){
		var locDlg=$(this);
		if (locDlg.prop('disabled')){
			return;
		}
		var lat=$.trim($('[name=latitude]',dlg).val()),
			lng=$.trim($('[name=longitude]',dlg).val());
		locDlg.prop('disabled',true);
		editLocationDialog({
			mapZoom: 14,
			markerIcon: assetsBaseUrl+'www/images/icons/icon_1.png',
			inputPlaceholder: 'Enter address',
			submitText: 'Use this location',
			cancelButton: true,
			center: lat!=='' && lng!=='' ? new google.maps.LatLng(lat,lng) :
				centerPosition,
			infoWindowContent: function(address){
				return userAddressTooltip(address, user.image);
			},
			beforeClose: function(){
				locDlg.prop('disabled',false)
					.closest('form').find(':data(ui-tooltip)')
						.tooltip('destroy');
			},
			submit: function(map, dialogEvent, position, place){
				var addr=parsePlaceAddress(place);
				for (var i in addressFl){
					var name=addressFl[i];
					$('[name='+name+']').val(typeof addr[name] !== 'undefined' ?
						addr[name] : '');
				}
				$('[name=latitude]').val(position.lat());
				$('[name=longitude]').val(position.lng());

				locDlg.addClass('ac').prop('disabled',false);
				$(dialogEvent.target).dialog('close');
			}
		});
	});
	$('[name=category_id]',dlg).on('change',function(){
		$(this).closest('form').find(':data(ui-tooltip)')
			.tooltip('destroy');
		$(this).closest('.cat').addClass('ac');
	});
	$('.pic',dlg).on('click',function(){
		$(this).closest('form').find('[name=image]').click();
	});
	$('[name=image]',dlg).on('change',function(){
		var targetEl=$(this),
			containerEl=targetEl.parent(),
			imgIc=containerEl.find('.pic');
		containerEl.find('.image').remove();

		if ($.trim(targetEl.val()) === ''){
			imgIc.removeClass('ac');
			return true;
		}

		if ($.inArray(this.files[0]['type'],['image/gif','image/jpeg','image/png'])<0){
			alert('Invalid file type');
			targetEl.val('');
			imgIc.removeClass('ac');
			return false;

		}

		imgIc.addClass('ac');

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
						if ($(this).is('[disabled]')){
							return;
						}
						$('.image',containerEl).remove();
						$('[type=file]',containerEl).val('');
						imgIc.removeClass('ac');
					})
			)
		));
	});

	return dlg;
}
function closeEditDlg(dlg,id){
	dlg.remove();
	$('body').css({overflow: 'visible'});
	$('.ui-widget-overlay.ui-front').remove();
	$('.post[data-id="'+id+'"] .edit').removeClass('disabled');
}
function community_load(start){
	start = start || 0;
	ajaxJson({
		url: baseUrl+'contacts/friends-list-load',
		data: {start:start || 0},
		done: function(response){
			for (var i in response.data){
				$('.posts-container .posts').append(response.data[i]);
			}
			if (Object.size(response.data) >= postLimit){
				postList_scrollHandler(true);
			}
		}
	})
}
