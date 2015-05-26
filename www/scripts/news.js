var newsMap, newsMapRady = false, newsMarkers = {}, newsMarkersCluster = [], newsMarkerClick = false,
	newsMapCircle, currentLocationMarker;

function renderNews($news){
	$('.textAreaClass', $news)
		.textareaAutoSize()
		.bind('input paste keypress', function(e){
			if (!isLogin){
				$('body').css({overflow: 'hidden'});

				$('<div/>', {'class': 'login-dialog'})
					.append(
						$('<div/>').append(
							$('<span/>').text('Please register or log in to do that.')
						),
						$('<div/>').append(
							$('<a/>', {href: baseUrl}).append(
								$('<input/>', {type: 'button'}).val('OK')
							)
						)
					)
					.appendTo($('body'))
					.dialog({
						modal: true,
						resizable: false,
						drag: false,
						width: 335,
						height: 105,
						dialogClass: 'colorbox',
						beforeClose: function(event, ui){
							$('body').css({overflow: 'visible'});
							$(event.target).dialog('destroy').remove();
						}
					});

				return false;
			}

			var $commentField = $(this),
				comment = $commentField.val();

			if (comment.length == 0){
				return true;
			}

			if (comment.length > 250){
				$commentField.val(comment = comment.substring(0, 250));
				alert("The comment should be less the 250 Characters");
				return false;
			}

			if (comment.indexOf('<') > 0 || comment.indexOf('>') > 0){
				alert('You enter invalid text');
				$commentField.val(comment.replace('<', '').replace('>', ''));
				return false;
			}

			if (keyCode(e) === 13){
				$commentField.attr('disabled', true);

				$('.commentLoading').show();

				$.ajax({
					url: baseUrl + 'home/add-new-comments',
					data: {
						comment: comment,
						news_id: getNewsID($commentField.closest('.scrpBox'))
					},
					type: 'POST',
					dataType: 'json',
					async: false
				}).done(function(response){
					if (response && response.status){
						e.preventDefault();
						$commentField.val('').height('auto').attr('disabled', false).blur();
						renderComments($(response.html).insertBefore($commentField.closest('.cmntList-last')));
						$('.commentLoading').hide();
					} else if (response){
						alert(response.error.message);
					} else {
						alert(ERROR_MESSAGE);
					}
				}).fail(function(jqXHR, textStatus){
					alert(textStatus);
				});
			} else {
				if (Number($("#newsData").height()) > 714){
					setThisHeight(Number($("#newsData").height()) + 100);
				}
			}
		});

	$('.edit-post', $news).click(function(){
		var $target = $(this).closest('.scrpBox'),
			news_id = getNewsID($target);

		$(this).attr('disabled', true);

		$.ajax({
			url: baseUrl + 'home/edit-news',
			data: {id: news_id},
			type: 'POST',
			dataType: 'json'
		}).done(function(response){
			if (response && response.status){
				$('.post-content, .news-footer', $target).hide();

				var $editNews = $('<textarea/>', {rows: 1, name: 'news'})
					.appendTo(
						$('<form/>', {'class': 'edit-news-content'})
							.append(
								$('<input/>', {type: 'hidden', name: 'latitude'}).val(response.news.latitude),
								$('<input/>', {type: 'hidden', name: 'longitude'}).val(response.news.longitude),
								$('<input/>', {type: 'hidden', name: 'address'}).val(response.news.address),
								$('<input/>', {type: 'hidden', name: 'id'}).val(news_id)
							)
							.prependTo($('.post-bottom', $target))
					)
					.val(response.news.news)
					.bind('input paste keypress', editNewsHandle)
					.textareaAutoSize()
					.focus();

				$('.news-footer', $target).after(
					$('<div/>', {'class': 'edit-news-panel'}).append(
						$('<input/>', {type: 'button', 'class': 'location-post'})
							.val('Change location')
							.click(function(){
								$('body').css({overflow: 'hidden'});

								$('<div/>', {'class': 'location-dialog'})
									.append(
										$('<div/>', {id: 'map-canvas'}),
										$('<div/>', {'class': 'panel'}).append(
											$('<form/>').append(
												$('<input/>', {type: 'text', name: 'address', placeholder: 'Enter address'}),
												$('<img/>', {'class': 'search', src: '/www/images/map_search.png'}),
												$('<input/>', {type: 'submit', value: 'Save Location'})
											)
										)
									)
									.appendTo($('body'))
									.dialog({
										modal: true,
										resizable: false,
										drag: false,
										width: 980,
										height: 500,
										dialogClass: 'colorbox',
										beforeClose: function(event, ui){
											$('body').css({overflow: 'visible'});
											$(event.target).dialog('destroy').remove();
										},
										open: function(event, ui){
											var $editForm = $('.edit-news-content', $target),
												$addressField = $('[name=address]', $editForm),
												$latitudeField = $('[name=latitude]', $editForm),
												$longitudeField = $('[name=longitude]', $editForm),
												newsAddress = $addressField.val(),
												$editAddress = $('[name=address]', event.target)
													.val(newsAddress)
													.attr('disabled', false),
												$submitField = $('[type=submit]', event.target)
													.attr('disabled', false),
												$map = $('#map-canvas', event.target),
												autocomplete = new google.maps.places.Autocomplete($editAddress[0]),
												centerLocation = new google.maps.LatLng($latitudeField.val(), $longitudeField.val()),
												geocoder = new google.maps.Geocoder(),
												renderLocation = true;

											var map = new google.maps.Map($map[0], {
												zoom: 14,
												center: centerLocation
											});

											var marker = new google.maps.Marker({
												draggable: true,
												position: map.getCenter(),
												icon: baseUrl + 'www/images/icons/icon_1.png'
											});

											var infowindow = new google.maps.InfoWindow({
												maxWidth: 220,
												content: userAddressTooltip(newsAddress, imagePath)
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

											$('.search', event.target).click(function(){
												var value = $.trim($editAddress.val());

												if (value === ''){
													$editAddress.focus();
													return false;
												}

												if ($('.pac-container .pac-item:not(.hidden)').size()){
													$editAddress.focus();
													return false;
												}

												$submitField.attr('disabled', true);

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
												});
											});

											$('form', event.target)
												.on('keyup keypress', function(e){
													if (keyCode(e) === 13){
														e.preventDefault();
														return false;
													}
												})
												.submit(function(e){
													e.preventDefault();

													if (!renderLocation){
														$editAddress.focus();
														return false;
													}

													$map.mask('Waiting...');
													$submitField.attr('disabled', true);

													$addressField.val($editAddress.val());
													$latitudeField.val(marker.getPosition().lat());
													$longitudeField.val(marker.getPosition().lng());

													$.ajax({
														url: baseUrl + 'home/save-news-location',
														data: $(':not([name=news])', $editForm).serialize(),
														type: 'POST',
														dataType: 'json',
														beforeSend: function(jqXHR, settings){
															$editAddress.attr('disabled', true);
														}
													}).done(function(response){
														if (response && response.status){
															if (newsMap.get('isListing') &&
																latlngDistance(newsMap.getCenter(), marker.getPosition(), 'M') > getRadius()){
																newsMap.setCenter(marker.getPosition());
																newsMapCircle.changeCenter(newsMap.getCenter(), 0.8);
																loadNews(0);
															} else {
																if (!newsMap.get('isListing')){
																	newsMap.setCenter(marker.getPosition());
																}

																newsMarkers[news_id].opts.data.latitude = marker.getPosition().lat();
																newsMarkers[news_id].opts.data.longitude = marker.getPosition().lng();
																newsMarkers[news_id].setPosition(marker.getPosition());

																if (newsMap.get('isListing')){
																	resetMarkersCluster();

																	if (isRootMarker(news_id)){
																		newsMarkers[news_id].setIcon({
																			url: baseUrl + 'www/images/icons/icon_1.png',
																			width: 25,
																			height: 36
																		});
																	} else {
																		newsMarkers[news_id].setIcon({
																			url: baseUrl + 'www/images/icons/icon_2.png',
																			width: 20,
																			height: 29
																		});
																	}

																	updateMarkersCluster();
																}

																var $markerElement = $('#' + newsMarkers[news_id].id);

																if ($markerElement.data('ui-tooltip')){
																	$markerElement.tooltip('destroy');
																}
															}

															$(event.target).dialog('close');
														} else if (response){
															alert(response.error.message);
															$map.unmask();
															$('[name=address],[type=submit]', event.target).attr('disabled', false);
														} else {
															alert(ERROR_MESSAGE);
															$map.unmask();
															$('[name=address],[type=submit]', event.target).attr('disabled', false);
														}
													}).fail(function(jqXHR, textStatus){
														alert(textStatus);
														$map.unmask();
														$('[name=address],[type=submit]', event.target).attr('disabled', false);
													});
												});

											function updateMarker(event){
												geocoder.geocode({
													latLng: event.latLng
												}, function(results, status){
													var address = '';

													if (status == google.maps.GeocoderStatus.OK){
														address = results[0].formatted_address;
													}

													infowindow.setContent(userAddressTooltip(address, imagePath));
													infowindow.open(map, marker);
													setGoogleMapsAutocompleteValue($editAddress, address);
													renderLocation = true;
												});
											}

											function renderLocationAddress(address, location){
												infowindow.setContent(userAddressTooltip(address, imagePath));
												map.setCenter(location);
												marker.setPosition(location);
												$editAddress.val(address);
												setGoogleMapsAutocompleteValue($editAddress, address);
												renderLocation = true;
											}
										}
									});
								}),
						$('<input/>', {type: 'button', 'class': 'delete-post'})
							.val('Delete')
							.click(function(e){
								if (!confirm('Are you sure you want to delete?')){
									return false;
								}

								var $editButtons = $('.location-post, .delete-post, .save-post', $target);

								$editButtons.attr('disabled', true);

								$.ajax({
									url: baseUrl + 'home/delete',
									type: 'POST',
									dataType: 'json',
									data: {id: news_id}
								}).done(function(response){
									if (response && response.status){
										if (newsMap.get('isListing')){
											$target.remove();
											newsMarkers[news_id].remove();
											delete newsMarkers[news_id];

											resetMarkersCluster();
											updateMarkersCluster();
										} else {
											window.location = baseUrl + 'home';
										}
									} else {
										alert('Sorry! we are unable to performe delete action');
										$editButtons.attr('disabled', false);
									}
								}).fail(function(jqXHR, textStatus){
									alert(textStatus);
									$editButtons.attr('disabled', false);
								});
							}),
						$('<input/>', {type: 'button', 'class': 'save-post'})
							.val('Done Editing')
							.click(function(e){
								var $editButtons = $('.location-post, .delete-post, .save-post', $target);

								if ($.trim($editNews.val()) === ''){
									$editNews.focus();
									return false;
								}

								$editButtons.attr('disabled', true);

								$.ajax({
									url: baseUrl + 'home/save-news',
									data: $('.edit-news-content', $target).find('[name=id],[name=news]').serialize(),
									type: 'POST',
									dataType: 'json',
									beforeSend: function(jqXHR, settings){
										$editNews.attr('disabled', true);
									}
								}).done(function(response){
									if (response && response.status){
										if (newsMap.get('isListing')){
											newsMarkers[news_id].opts.data.news = $('.edit-news-content [name=news]', $target).val();

											var $markerElement = $('#' + newsMarkers[news_id].id);

											if ($markerElement.data('ui-tooltip')){
												$markerElement.tooltip('destroy');
											}
										}

										$('.edit-news-content, .edit-news-panel', $target).remove();
										$('.post-content', $target).html(response.html).show();
										$('.news-footer', $target).show();
										$('.edit-post', $target).attr('disabled', false);
									} else if (response){
										alert(response.error.message);
										$editButtons.attr('disabled', false);
									} else {
										alert(ERROR_MESSAGE);
										$editButtons.attr('disabled', false);
									}
								}).fail(function(jqXHR, textStatus){
									alert(textStatus);
									$editButtons.attr('disabled', false);
								});
							})
					)
				);
			} else if (response){
				alert(response.error.message);
			} else {
				alert(ERROR_MESSAGE);
			}
		}).fail(function(jqXHR, textStatus){
			alert(textStatus);
		});
	});

	$('.view-comment', $news).click(function(e){
		var target = $(this),
			news_item = target.closest('.scrpBox'),
			comment_list = $('.cmntRow', news_item).closest('ul');

		target.hide();

		$.ajax({
			type: 'POST',
			url: baseUrl + 'home/get-total-comments',
			data: {
				news_id: getNewsID(news_item),
				limitstart: comment_list.find('.cmntList').size()
			},
			dataType : 'json'
		}).done(function(response){
			if (response && response.status){
				if (response.data){
					for (var i in response.data){
						renderComments($(response.data[i]).prependTo(comment_list));
					}

					if (response.label){
						comment_list.effect("highlight", {}, 500, function(){
							target.text(response.label).show();
						});
					}
				}
			} else if (response){
				alert(response.error.message);
			} else {
				alert(ERROR_MESSAGE);
			}
		}).fail(function(jqXHR, textStatus){
			alert(textStatus);
		});
	});

	renderComments($news.find('.cmntList'));
}

function showCommentHandle(){
	var news_item = $(this).closest('.scrpBox');

	$('.cmntList-last', news_item).show();
	$('.textAreaClass', news_item).focus();

	if ($("#newsData").height() > 714){
		setThisHeight(Number($("#newsData").height())+100);
	}

	$(this).closest('.post-comment').hide();
}

function renderComments($comments){
	$('.deteleIcon1 img', $comments).on('click', function (e){
		if (confirm('Are you sure you want to delete?')){
			var $target = $(this).closest('.cmntList'),
				comment_id = $target.attr('id').replace('comment_', '');

			$.ajax({
				url: baseUrl + 'home/delete-comment',
				type: 'POST',
				dataType: 'json',
				data: {id: comment_id}
			}).done(function(response){
				if (response && response.status){
					$target.remove();
				} else {
					alert('Sorry! we are unable to performe delete action');
				}
			}).fail(function(jqXHR, textStatus){
				alert(textStatus);
			});
		}
	});

	$comments.closest('.cmntList').hover(
		function(){
			$(this).find(".deteleIcon1").show();
		},
		function(){
			$(this).find(".deteleIcon1").hide();
		}
	);
}

function editNewsHandle(){
	var text = $(this).val();

	if (text.length > 500){
		$(this).val(text.substring(0, 499));
		alert("Sorry! You can not enter more then 500 charactes.");
	}
}

function getNewsID(target){
	return parseInt(target.attr('id').replace('scrpBox_', ''));
}

function createNewsMarker(options, contentCallback){
	var marker = new NewsMarker($.extend({
		id: 'newsMarker' + options.data.id
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
					if (newsMap.get('isListing')){
						var newsId = $(event.target).attr('id').replace('newsMarker', ''),
							position = getMarkerPosition(newsId);

						$('a.prev, a.next', ui.tooltip).remove();

						if (position[0] > 0){
							$('.tooltip-footer', ui.tooltip).append(
								$('<a/>', {'class': 'prev', href: '#'})
									.html('&lt;')
									.click(function(e){
										e.preventDefault();
										openMarkerTooltip(position[1][parseInt(position[0]) - 1][0], newsId);
									})
							);
						}

						if (position[1].length > 1 && position[0] < (position[1].length - 1)){
							$('.tooltip-footer', ui.tooltip).append(
								$('<a/>', {'class': 'next', href: '#'})
									.html('&gt;')
									.click(function(e){
										e.preventDefault();
										openMarkerTooltip(position[1][parseInt(position[0]) + 1][0], newsId);
									})
							);
						}
					}

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
					ui.tooltip.hover(
						function(){
							$(this).stop(true).show();
						},
						function(){
							$(this).remove();
						}
					);
				}
		})
		.tooltip('open');
	});

	newsMarkers[options.data['id']] = marker;

	return marker;
}

function openImage(image, width, height){
	var dialogWidth = parseFloat(width),
		dialogHeight = parseFloat(height),
		scrollTop = $(window).scrollTop(),
		offsetTop = $('#midColLayout').offset().top,
		windowHeight = $(window).height(),
		mainContainerMinHeight = $('.mainContainer').css('min-height'),
		minWidth = minHeight = 150,
		maxWidth = 980, maxHeight = Math.max(windowHeight - 150, 540);

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

	$('#midColLayout').css({
		top: -(scrollTop - offsetTop),
		position: 'fixed',
		maxHeight: Math.max(windowHeight - offsetTop, 540),
		width: $('#midColLayout').width()
	});

	$('.mainContainer').css('min-height', 'auto');

	$('body').css('overflowY', 'scroll');

	$('<div/>', {'class': 'news-image-dialog'})
		.append(
			$('<img/>', {
				src: baseUrl + 'newsimages/' + image,
				width: dialogWidth,
				height: dialogHeight
			})
		)
		.appendTo($('body'))
		.dialog({
			modal: true,
			resizable: false,
			drag: false,
			width: dialogWidth + 10,
			height: dialogHeight + 15,
			dialogClass: 'colorbox',
			open: function(event, ui){
				$(window).bind('resize.dialog', function(){
					$(event.target).dialog('close');
					openImage(image, width, height);
				});
			},
			beforeClose: function(event, ui){
				$('#midColLayout').css({
					top: 0,
					position: 'static',
					maxHeight: 'auto',
					width: 'auto'
				});

				$('.mainContainer').css('min-height', mainContainerMinHeight);
				$('body').css('overflowY', 'auto');
				$(window).scrollTo(scrollTop);
				$(event.target).dialog('destroy').remove();
				$(window).unbind('resize.dialog');
			}
		});
}

function fbshare(imageUrl,messageId,reurlPost) { 
	FB.ui({ 
		method: 'feed',
		name: 'SeeAround.me',
		link: reurlPost,
		description: '<b>' + messageId + '</b><br>',
		picture: imageUrl
	});
}

function voting(thisone, action, elid,userid) {
    if(action && elid && userid){
        $.ajax({
            url  : baseUrl+'home/store-voting',
            type : 'post',
            data : {
                action:action, 
                id:elid, 
                user_id:userid
            },
            success: function(data) {
                data = $.parseJSON(data);

                if (data && data.successalready){
                    $('#thumbs_up2_'+elid).show('slow');
                    $('#img1_'+elid).hide();
                    $('#img2_'+elid).show();
                }

                if (data && data.success && action == 'news') {
					$('#thumbs_up2_'+elid).hide();
					$('#img1_'+elid).hide();
					$('#img2_'+elid).show();
					var doFlag = true;
                }

                if(data && data.noofvotes_2){
                    $('#message_success_'+elid).html(data.noofvotes_2); 
                }
                else if(data && data.error) {
                    alert('Sorry! we are unable to perform voting action');
                } 
            }
        });
    } else {
        alert('Sorry! we are unable to performe delete action');
    }
}

function newsUserTooltipContent(image, name, body){
	return '<div class="tooltip-content">' +
		'<img src="' + image + '" />' +
		'<div>' +
			'<h4>' + name + '</h4>' +
			'<div>' + body.substring(0, 500) + '</div>' +
		'</div>' +
	'</div>';
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

function latlngDistance(firstPoint, secondPoint, unit){
	var lat1 = firstPoint.lat();
	var lon1 = firstPoint.lng();
	var lat2 = secondPoint.lat();
	var lon2 = secondPoint.lng();
	var theta = lon1 - lon2;
	var dist = Math.sin(lat1 * Math.PI / 180) * Math.sin(lat2 * Math.PI / 180) +  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.cos(theta * Math.PI / 180);
	var miles = (Math.acos(dist) * 180 / Math.PI) * 60 * 1.1515;

	if (unit == "K"){
		return (miles * 1.609344); 
	} else if (unit == "N"){
		return (miles * 0.8684);
	} else {
		return miles;
	}
}

function higlightNews(newsId){
	var $target = $('#scrpBox_' + newsId);
	$('.scrpBox').removeClass('higlight-news');
	$.scrollTo($target, 1000, {offset: {top: -100}});
	$target.addClass('higlight-news').find('.moreButton').click();
}

function loadNews(start, callback){
	if (start == 0){
		if (!$.isEmptyObject(newsMarkers)){
			for (var id in newsMarkers){
				newsMarkers[id].remove();
				delete newsMarkers[id];
			}
		}

		resetMarkersCluster();

		$("#newsData").html('');
	}

    $('#loading').width($("#newsData").width()).show();
	$('#newsData #pagingDiv').remove();

	$.ajax({
		url: baseUrl + 'home/get-nearby-points',
		data: {
			latitude: newsMap.getCenter().lat(),
			longitude: newsMap.getCenter().lng(),
			radius: getRadius(),
			search: $('#searchNews [name=sv]').val(),
			filter: $('#filter_type').val(),
			fromPage: start
		},
		type: 'POST',
		dataType: 'json',
		async: false
	}).done(function(response){
		if (response && response.status){
			$('#loading').hide();

			if (typeof response.result === 'object'){
				var scrollPosition = [
					self.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft,
					self.pageYOffset || document.documentElement.scrollTop  || document.body.scrollTop
				];

				window.scrollTo(scrollPosition[0], scrollPosition[1]);

				for (i in response.result){
					start++;
					updateNews($(response.result[i].html).appendTo($("#newsData")));
				}

				if (response.result.length == 15){
					$(window).bind('scroll', function(){
						if ($(window).scrollTop() + $(window).height() > $(document).height() - 400){
							$(window).unbind('scroll');
							loadNews(start);
						}
					});
				}

				window.scrollTo(scrollPosition[0], scrollPosition[1])

				for (var i in response.result){
					renderListingMarker(response.result[i]);
				}

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
			} else if (typeof response.result === 'string'){
				$("#newsData").append(response.result);
			}

			if ($("#newsData").height() > 714){
				setThisHeight(Number($("#newsData").height())+100);
			}

			if (typeof callback == 'function'){
				callback();
			}
		} else if (response){
			alert(response.error.message);
		} else {
			alert(ERROR_MESSAGE);
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
	});
}

function updateNews($news){
	var newsId = getNewsID($news);

	renderNews($news);

	$news.mouseover(function(){
		if (isRootMarker(newsId)){
			return true;
		}

		newsMarkers[newsId].setIcon({
			url: baseUrl + 'www/images/icons/icon_3.png',
			width: 20,
			height: 29
		});
	});

	$news.mouseout(function(){
		if (isRootMarker(newsId)){
			return true;
		}

		newsMarkers[newsId].setIcon({
			url: baseUrl + 'www/images/icons/icon_2.png',
			width: 20,
			height: 29
		});
	});

	$('.post-comment', $news).on('click', showCommentHandle);

	$('.email-share', $news).click(function(e){
		e.preventDefault();

		$('body').css({overflow: 'hidden'});

		$('<div/>', {'class': 'message-dialog'})
			.append(
				$('<img/>', {src: baseUrl + 'www/images/mail_send.gif'}),
				$('<span/>', {'class': 'locppmsgimg'}).text('Send Message'),
				$('<div/>', {'class': 'row-content'}).append(
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
							$('<input/>', {
								type: 'submit',
								value: 'Send',
								'class': 'btnBlueRpt'
							}),
							$('<input/>', {
								type: 'button',
								value: 'Cancel',
								'class': 'btnBlueRpt'
							}).click(function(){
								$('.message-dialog').dialog('close');
							})
						),
						$('<input/>', {type: 'hidden', name: 'news_id'}).val(newsId)
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
				dialogClass: 'colorbox',
				beforeClose: function(event, ui){
					$('body').css({overflow: 'visible'});
					$(event.target).dialog('destroy').remove();
				},
				open: function(event, ui){
					$('form', event.target).validate({
						rules: {
							to: {
								required: true,
								email: true
							},
							message: {
								required: true
							}
						},
						submitHandler: function(form){
							$(event.target).mask('Loading...');

							$.ajax({
								url: baseUrl + 'info/public-message-email',
								data: $(form).serialize(),
								type: 'POST',
								dataType: 'json'
							}).done(function(response){
								if (response && response.status){
									$(event.target)
										.empty()
										.append(
											$('<div/>', {'class': 'messageSuccess'}).append(
												$('<img/>', {src: baseUrl + 'www/images/correct.gif'}),
												$('<span/>').text('Message sent successful')
											)
										);
								} else {
									$(event.target).unmask();
									alert(response ? response.error.message : ERROR_MESSAGE);
								}
							}).fail(function(jqXHR, textStatus){
								$(event.target).unmask();
								alert(textStatus);
							});
						}
					});

				}
			});
	});

	$('.moreButton', $news).click(function(e){
		var $target = $(this),
			$post = $target.closest('.scrpBox');

		e.preventDefault();

		$.ajax({
			url: baseUrl + 'home/read-more-news',
			data: {id: getNewsID($post)},
			type: 'POST',
			dataType: 'json'
		}).done(function(response){
			if (response && response.status){
				$('.post-content', $post).html(response.html);
				$target.remove();
			} else if (response){
				alert(response.error.message);
			} else {
				alert(ERROR_MESSAGE);
			}
		}).fail(function(jqXHR, textStatus){
			alert(textStatus);
		});
	});
}

function getRadius(){
	var radius = Number($("#radious").html());

	if (radius > 0){
		return radius;
	}

	return 0.8;
}

function renderListingMarker(news){
	var position = new google.maps.LatLng(parseFloat(news.latitude), parseFloat(news.longitude));
	var marker = createNewsMarker({
		map: newsMap,
		position: position,
		data: news,
		icon: {
			url: baseUrl + 'www/images/icons/icon_2.png',
			width: 20,
			height: 29
		},
		hide: true,
		addClass: 'newsMarker'
	}, function(newsMarker){
			var tooltip = '<div class="tooltip-content">' +
			'<img src="' + newsMarker.opts.data.user.image + '" />' +
				'<div>' +
					'<h4>' + newsMarker.opts.data.user.name + '</h4>' +
					'<div>' + $.trim(newsMarker.opts.data.news.substring(0, 100));

			if (newsMarker.opts.data.news.length > 100){
				tooltip += '... <a href="#" class="more" onclick="higlightNews(' + newsMarker.opts.data.id + '); return false;">More</a>';
			}

			tooltip += '</div>' +
				'</div>' +
			'</div>' +
			'<div class="tooltip-footer">' +
				'<a href="' + baseUrl + 'info/news/nwid/' + newsMarker.opts.data.id + '">More details</a>' +
			'</div>';

			return tooltip;
		}
	);

	google.maps.event.addListener(marker, 'click', function(){
		newsMarkerClick = true;
		higlightNews(this.opts.data.id);
	});
}

function isRootMarker(id){
	for (var groupId in newsMarkersCluster){
		for (var key in newsMarkersCluster[groupId]){
			if (newsMarkersCluster[groupId][key][0] == id){
				if (newsMarkersCluster[groupId][0][0] == 0){
					return true;
				}

				break;
			}
		}
	}

	return false;
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

function openMarkerTooltip(id, currentId){
	$('#newsMarker' + currentId).tooltip('destroy');

	setTimeout(function(){
		newsMarkers[currentId].hide();
		newsMarkers[id].show();
		google.maps.event.trigger(newsMarkers[id], 'mouseover');
	}, .1);
}

function resetMarkersCluster(){
	newsMarkersCluster = [[[0, currentLocationMarker.getPosition()]]];

	if (!$.isEmptyObject(newsMarkers)){
		for (var id in newsMarkers){
			var group = false;

			for (var groupId in newsMarkersCluster){
				if (latlngDistance(newsMarkersCluster[groupId][0][1], newsMarkers[id].getPosition(), 'F') <= 0.018939){
					newsMarkersCluster[groupId].push([newsMarkers[id].opts.data.id, newsMarkers[id].getPosition()]);
					group = true;
					break;
				}
			}

			if (!group){
				newsMarkersCluster.push([[newsMarkers[id].opts.data.id, newsMarkers[id].getPosition()]]);
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
