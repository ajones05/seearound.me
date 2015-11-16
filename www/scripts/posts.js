var mainMap,areaCircle,loadXhr,
	// TODO: update on change user location
	userPosition,
	centerPosition,
	postLimit=15,defaultRadius=0.8,groupDistance=0.018939,
	defaultZoom=14,defaultMinZoom=13,defaultMaxZoom=15,
	locationIcon=assetsBaseUrl+'www/images/template/user-location-icon.png',
	postIcon=assetsBaseUrl+'www/images/template/post-icon.png',
	postActiveIcon=assetsBaseUrl+'www/images/template/post-active-icon.png',
	postMarkers={},postMarkersCluster={},
	disableScroll=false,markerClick=false;

require.config({
    paths: {
		'jquery': assetsBaseUrl+'bower_components/jquery/dist/jquery.min',
		'jquery-ui': assetsBaseUrl+'bower_components/jquery-ui/jquery-ui.min',
		'textarea_autosize': assetsBaseUrl+'bower_components/textarea-autosize/src/jquery.textarea_autosize',
		'jquery-validate': assetsBaseUrl+'bower_components/jquery-validation/dist/jquery.validate.min',
		'facebook-sdk': 'http://connect.facebook.net/en_US/sdk',
		'google.maps': 'https://maps.googleapis.com/maps/api/js?v=3&sensor=false&libraries=places&callback=renderMap_callback'
	}
});

require(['google.maps','jquery','jquery-ui'], function(){
	loadCss(assetsBaseUrl+'bower_components/jquery-ui/themes/base/jquery-ui.min.css');
});

window.fbAsyncInit = function(){
	FB.init({
		appId: facebook_appId,
		xfbml: true,
		cookie: true,
		version: 'v2.5'
	});
};	

require(['facebook-sdk']);

function renderMap_callback(){
	centerPosition = new google.maps.LatLng(mapCenter[0], mapCenter[1]);
	mainMap = new google.maps.Map(document.getElementById('map_canvas'), {
		center: centerPosition,
		zoom: defaultZoom,
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
		userPosition = new google.maps.LatLng(userLocation[0], userLocation[1]);
		require(['jquery','jquery-ui','textarea_autosize'], function(){
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
				mainMap.setZoom(defaultZoom);
				if (getDistance([userPosition.lat(),userPosition.lng()],
					[centerPosition.lat(),centerPosition.lng()]) <= 0){
					$('html, body').animate({scrollTop: '0px'}, 300);
					return true;
				}
				centerPosition = userPosition;
				mainMap.setCenter(offsetCenter(mainMap,centerPosition,listMap_centerOffset(true),0));
				areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),listMap_centerOffset(),0), getRadius());
				postList_change();
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
				mainMap.setZoom(defaultZoom);
				mainMap.setCenter(offsetCenter(mainMap,centerPosition,listMap_centerOffset(true),0));
				areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),listMap_centerOffset(),0), getRadius());
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

			$('.post-new').click(function(e){
				e.preventDefault();
				$('.post-new-dialog').toggle();
			});
		});

		/**
		 * Renders post item.
		 */
		function postItem_render(id){
			var marker = postItem_marker(id, postData[id]),
				postContainer = $('.post[data-id="'+id+'"]');
			postContainer.bind({
				mouseenter: function(){
					marker.setIcon({
						url: postActiveIcon,
						width: marker.opts.icon.width,
						height: marker.opts.icon.height
					});
					marker.css({zIndex: 100001});
				},
				mouseleave: function(){
					marker.setIcon({
						url: marker.data('isRoot')==true ?
							locationIcon : postIcon,
						width: marker.opts.icon.width,
						height: marker.opts.icon.height
					});
					marker.css({zIndex: ''});
				}
			});
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
						target.addClass('active');
						$('._3_copy', vote).html(response.vote);
					}
				});
			});
			$('.post_text .moreButton', postContainer).click(function(e){
				e.preventDefault();
				postItem_more(postContainer);
			});
			$('> .image img', postContainer).click(function(){
				var scrollTop = $(window).scrollTop(),
					offsetTop = $('.posts').offset().top,
					width = parseFloat($(this).attr('data-width')),
					height = parseFloat($(this).attr('data-height')),
					dimensions = postItem_imageDimensions(width, height);

				$('.posts').css({
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
							$('.posts').css({
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
			$('.add-comment', postContainer).click(function(e){
				e.preventDefault();
				$('.post-comment__new', postContainer).removeClass('hidden');
				$(this).remove();
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
							.bind('input paste keypress', postItem_editHandler)
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
												return userAddressTooltip(address, userImage);
											},
											submit: function(map, dialogEvent, position, address){
												map.setOptions({draggable: false, zoomControl: false});

												$address.val(address);
												$latitude.val(position.lat());
												$longitude.val(position.lng());

												ajaxJson({
													url: baseUrl + 'home/save-news-location',
													data: $('[name=id],[name=latitude],[name=longitude],[name=address]', postContainer).serialize(),
													done: function(response){
														if (getDistance([centerPosition.lat(), centerPosition.lng()],
															[position.lat(), position.lng()])<=getRadius()){
															postItem_delete(id);
															postData[id][0]=position.lat();
															postData[id][1]=position.lng();
															postItem_marker(id, postData[id]);
														} else {
															centerPosition = position;
															mainMap.setCenter(offsetCenter(mainMap,centerPosition,listMap_centerOffset(true),0));
															areaCircle.changeCenter(offsetCenter(mainMap,mainMap.getCenter(),listMap_centerOffset(),0), getRadius());
															postList_change();
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
												$('.post[data-id="'+id+'"]').remove();
												postItem_delete(id);
												delete postData[id];
												// TODO: remove from new posts
												// $('#addNewsForm [value=' + news_id + ']').remove();
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
			$('.social_share .facebook', postContainer).click(function(e){
				var target = $(this);
				e.preventDefault();
				require(['facebook-sdk'], function(){
					FB.ui({method:'share',href:target.attr('href')});
				});
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
			$('.post-comment__item', postContainer).each(function(){
				comment_render($(this));
			});

			$('.post-comment__new textarea', postContainer)
				.textareaAutoSize()
				.bind('input paste keypress', function(e){
					var target = $(this),
						body = target.val();

					if ($.trim(body) === ''){
						return false;
					}

					if (/[<>]/.test(body)){
						target.val(body.replace(/[<>]/, ''));
						return false;
					}

					if (keyCode(e) === 13 && !e.shiftKey){
						target.attr('disabled', true);

						ajaxJson({
							url: baseUrl+'post/comment',
							data: {post_id:id,comment:body},
							done: function(response){
								e.preventDefault();
								target.val('').css({height:''}).attr('disabled', false).blur();
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

			var max = 0;

			for (var group in postMarkers){
				if (group > max){
					max = group;
				}
			}

			var newGroup = ++max;

			postMarkersCluster[newGroup]=[id];

			var postMarker = postList_tooltipmarker({
					id: newGroup,
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

			postMarkers[newGroup] = postMarker;
			return postMarker;
		}

		/**
		 * Change list action.
		 */
		function postList_change(){
			$(window).unbind('scroll.load');
			$('html, body').animate({scrollTop: '0px'}, 300);
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
		postItem_more($('.post[data-id="'+postMarkersCluster[group][i]+'"]')
			.addClass('higlight'));
	}
}

/**
 * Delete post item.
 */
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
	}
}

/**
 * Post item read more handler.
 */
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
 * Returns post image dimensions.
 */
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

/**
 * Edit post validate handler.
 */
function postItem_editHandler(){
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
}

/**
 * Renders comment.
 */
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
				if (typeof settings.fail === 'function'){
					settings.fail(data, textStatus, jqXHR);
				}
				alert(typeof data === 'object' ? data.message : ERROR_MESSAGE);
				return false;
			}

			if (typeof settings.done === 'function'){
				return settings.done(data, textStatus, jqXHR);
			}
		}).fail(function(jqXHR, textStatus){
			if (typeof settings.fail === 'function'){
				settings.fail({}, textStatus, jqXHR);
			}
			alert('Internal server error');
		});

	return jqxhr;
}

// TODO: require from common js
function keyCode(event){
	return event.keyCode || event.which;
}

// TODO: require from common js
function editLocationDialog(options){
	$('body').css({overflow: 'hidden'});

	var $form = $('<form/>');

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
				$('body').css({overflow: 'visible'});
				$(event.target).dialog('destroy').remove();
			},
			open: function(dialogEvent, ui){
				var $editAddress = $('[name=address]', dialogEvent.target)
						.val(options.defaultAddress)
						.attr('disabled', false),
					$submitField = $('[type=submit]', dialogEvent.target)
						.attr('disabled', false),
					$map = $('#map-canvas', dialogEvent.target),
					autocomplete = new google.maps.places.Autocomplete($editAddress[0]),
					geocoder = new google.maps.Geocoder(),
					renderLocation = true;

				var updateMarker = function(event){
					geocoder.geocode({
						latLng: event.latLng
					}, function(results, status){
						var address = '';

						if (status == google.maps.GeocoderStatus.OK){
							address = results[0].formatted_address;
						}

						infowindow.setContent(options.infoWindowContent(address));
						infowindow.open(map, marker);
						setGoogleMapsAutocompleteValue($editAddress, address);
						renderLocation = true;
					});
				}

				var renderLocationAddress = function(address, location){
					infowindow.setContent(options.infoWindowContent(address));
					map.setCenter(location);
					marker.setPosition(location);
					$editAddress.val(address);
					setGoogleMapsAutocompleteValue($editAddress, address);
					renderLocation = true;
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

					renderLocationAddress(place.formatted_address, place.geometry.location);
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
							renderLocationAddress(results[0].formatted_address, results[0].geometry.location);
						} else {
							alert('Sorry! We are unable to find this location.');
							renderLocation = false;
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
					options.submit(map, dialogEvent, marker.getPosition(), $editAddress.val());
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
