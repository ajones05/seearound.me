var newsMap, newsMapRady = false, newsMarkers = {}, newsMarkersCluster = [], newsMarkerClick = false,
	newsMapCircle, currentLocationMarker, modalDialog = false;

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

			if (comment.indexOf('<') > 0 || comment.indexOf('>') > 0){
				alert('You enter invalid text');
				$commentField.val(comment.replace('<', '').replace('>', ''));
				return false;
			}

			if (keyCode(e) === 13 && !e.shiftKey){
				$commentField.attr('disabled', true);

				$('.commentLoading', $news).show();

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
						$commentField.val('').height(38).attr('disabled', false).focus();
						renderComments($(response.html).insertBefore($commentField.closest('.cmntList-last')));
						$('.commentLoading', $news).hide();
					} else if (response){
						alert(response.error.message);
					} else {
						alert(ERROR_MESSAGE);
					}
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
								var $address = $('.edit-news-content [name=address]', $target),
									$latitude = $('.edit-news-content [name=latitude]', $target),
									$longitude = $('.edit-news-content [name=longitude]', $target);

								editLocationDialog({
									mapZoom: 14,
									markerIcon: baseUrl + 'www/images/icons/icon_1.png',
									inputPlaceholder: 'Enter address',
									submitText: 'Save Location',
									defaultAddress: $address.val(),
									center: new google.maps.LatLng($latitude.val(), $longitude.val()),
									infoWindowContent: function(address){
										return userAddressTooltip(address, imagePath);
									},
									submit: function(dialogEvent, position, address){
										$('#map-canvas', dialogEvent.target).mask('Waiting...');

										$address.val(address);
										$latitude.val(position.lat());
										$longitude.val(position.lng());

										$.ajax({
											url: baseUrl + 'home/save-news-location',
											data: $(':not([name=news])', $('.edit-news-content', $target)).serialize(),
											type: 'POST',
											dataType: 'json',
											beforeSend: function(jqXHR, settings){
												$('[name=address]', dialogEvent.target);
											}
										}).done(function(response){
											if (response && response.status){
												if (newsMap.get('isListing') &&
													latlngDistance(newsMap.getCenter(), position, 'M') > getRadius()){
														newsMap.setCenter(position);
														newsMapCircle.changeCenter(position, 0.8);
														loadNews(0);
												} else {
													if (!newsMap.get('isListing')){
														newsMap.setCenter(position);
													}

													newsMarkers[news_id].opts.data.latitude = position.lat();
													newsMarkers[news_id].opts.data.longitude = position.lng();
													newsMarkers[news_id].setPosition(position);

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
												}

												$(dialogEvent.target).dialog('close');
											} else {
												alert(response ? response.message : ERROR_MESSAGE);
												$('#map-canvas', dialogEvent.target).unmask();
												$('[name=address],[type=submit]', dialogEvent.target).attr('disabled', false);
											}
										}).fail(function(jqXHR, textStatus){
											alert(textStatus);
											$('#map-canvas', dialogEvent.target).unmask();
											$('[name=address],[type=submit]', dialogEvent.target).attr('disabled', false);
										});
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

											$('#addNewsForm [value=' + news_id + ']').remove();
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
										}

										$('.edit-news-content, .edit-news-panel', $target).remove();
										$('.post-content', $target).html(response.html).show();
										$('.news-footer', $target).show();
										$('.edit-post', $target).attr('disabled', false);
									} else if (response){
										alert(response.message);
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
				alert(response.message);
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

	$('.like,.dislike', $news).click(function(){
		if ($(this).attr('disabled')){
			return false;
		}

		var $self = $(this),
			$target = $(this).closest('.scrpBox');

		$('.vote .icon > div', $target).attr('disabled', true);

		$.ajax({
			url: baseUrl + 'home/vote',
			data: {
				news_id: getNewsID($target),
				vote: ($(this).hasClass('like') ? 1 : -1)
			},
			type: 'POST',
			dataType: 'json'
		}).done(function(response){
			if (!response || !response.status){
				alert(response ? response.message : ERROR_MESSAGE);
				return false;
			}
			$('.vote .icon > div', $target)
				.removeClass('active')
				.attr('disabled', false);
			$('.vote ._3_copy', $target).html(response.vote);
			switch (response.active){
				case '-1':
					$('.dislike', $target).addClass('active');
					break;
				case '1':
					$('.like', $target).addClass('active');
					break;
			}
		});
	});
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

	$('.moreButton', $comments).click(function(e){
		e.preventDefault();

		if ($(this).attr('disabled')){
			return false;
		}

		$(this).attr('disabled', true);

		var $target = $(this).closest('.cmntList'),
			comment_id = $target.attr('id').replace('comment_', '');

		$.ajax({
			url: baseUrl + 'home/read-more-comment',
			data: {id: comment_id},
			type: 'POST',
			dataType: 'json'
		}).done(function(response){
			if (response && response.status){
				$('.cmnt', $target).html(response.html);
			} else {
				alert(response ? response.message : ERROR_MESSAGE);
			}
		});
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
				src: image,
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
				modalDialog = true;
				$(window).bind('resize.dialog', function(){
					$(event.target).dialog('close');
					openImage(image, width, height);
				});
			},
			beforeClose: function(event, ui){
				modalDialog = false;
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

function latlngDistance(firstPoint, secondPoint, unit){
	var lat1 = firstPoint.lat();
	var lon1 = firstPoint.lng();
	var lat2 = secondPoint.lat();
	var lon2 = secondPoint.lng();

	if (lat1 === lat2 && lon1 === lon2){
		return 0;
	}

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
	var $target = $('#scrpBox_' + newsId),
		locationMarkers = getMarkerPosition(newsId);
	$('.scrpBox').removeClass('higlight-news');

	for (var i in locationMarkers[1]){
		$('#scrpBox_' + locationMarkers[1][i][0]).addClass('higlight-news');
	}

	$.scrollTo($target, 1000, {offset: {top: -100}});
	$target.find('.moreButton').click();
}

function loadNews(start, callback){
	if (start == 0){
		resetNews();
	}

    $('#loading').width($("#newsData").width()).show();
	$('#newsData #pagingDiv').remove();

	var data = {
		radius: getRadius(),
		keywords: $('#searchNews [name=sv]').val(),
		filter: $('#filter_type').val(),
		start: start,
		'new': $('#addNewsForm [name=new\\[\\]]').map(function(){
			return $(this).val();
		}).get()
	};

	if (typeof point !== 'undefined'){
		data.latitude = point[0];
		data.longitude = point[1];
		data.point = 1;
	} else {
		data.latitude = newsMap.getCenter().lat();
		data.longitude = newsMap.getCenter().lng();
	}
	
	$.ajax({
		url: baseUrl + 'home/load-news',
		data: data,
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

				renderNewsResponse(response.result, start);
				window.scrollTo(scrollPosition[0], scrollPosition[1]);
			} else if (typeof response.result === 'string'){
				$("#newsData").append(response.result);
			}

			if ($("#newsData").height() > 714){
				setThisHeight(Number($("#newsData").height())+100);
			}

			if (typeof callback == 'function'){
				callback();
			}
		} else {
			alert(response ? response.error.message : ERROR_MESSAGE);
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

		var locationMarkers = getMarkerPosition(newsId);

		for (var i in locationMarkers[1]){
			newsMarkers[locationMarkers[1][i][0]].hide();
		}

		newsMarkers[newsId]
			.setIcon({
				url: baseUrl + 'www/images/icons/icon_3.png',
				width: 20,
				height: 29
			})
			.show();
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
												$('<span/>').text('Message sent')
											)
										);
								} else {
									$(event.target).unmask();
									alert(response ? response.message : ERROR_MESSAGE);
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
		e.preventDefault();

		if ($(this).attr('disabled')){
			return false;
		}

		$(this).attr('disabled', true);

		var $target = $(this),
			$post = $target.closest('.scrpBox');

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
				alert(response.message);
			} else {
				alert(ERROR_MESSAGE);
			}
		}).fail(function(jqXHR, textStatus){
			alert(textStatus);
		});
	});

	$('.share', $news).click(function(e){
		e.preventDefault();

		FB.ui({
			method: 'share',
			href: $(this).attr('href')
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

function renderListingMarker(news, readmore){
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
				tooltip += '...';

				if (readmore){
					tooltip += ' <a href="#" class="more" onclick="higlightNews(' + newsMarker.opts.data.id + '); return false;">More</a>';
				}
			}

			tooltip += '</div>' +
				'</div>' +
			'</div>' +
			'<div class="tooltip-footer">';

			if (getMarkerPosition(newsMarker.opts.data.id)[1].length == 1){
				tooltip += '<a href="' + baseUrl + 'info/news/nwid/' + newsMarker.opts.data.id + '">More details</a>';
			} else {
				tooltip += '<a href="' + baseUrl + 'home/index/point/' + newsMarker.opts.data.latitude + ',' + newsMarker.opts.data.longitude + '">More details</a>';
			}

			tooltip += '</div>';

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

function renderEditLocationDialog(callback){
		editLocationDialog({
			mapZoom: 14,
			markerIcon: baseUrl + 'www/images/icons/icon_1.png',
			inputPlaceholder: 'Enter address',
			submitText: 'Use This Address',
			defaultAddress: userAddress,
			center: newsMapCircle.center,
			infoWindowContent: function(address){
				return userAddressTooltip(address, imagePath);
			},
			submit: function(dialogEvent, position, address){
				$(window).scrollTo(0);

				$('#map-canvas', dialogEvent.target).mask('Waiting...');

				$.ajax({
					url: baseUrl + 'home/change-address',
					data: {
						address: address,
						latitude: position.lat(),
						longitude: position.lng()
					},
					type: 'POST',
					dataType: 'json',
					beforeSend: function(jqXHR, settings){
						$('[name=address]', dialogEvent.target);
					}
				}).done(function(response){
					if (response && response.status){
						userAddress = address;
						userLatitude = position.lat();
						userLongitude = position.lng();

						newsMap.setCenter(position);
						newsMapCircle.changeCenter(position, 0.8);
						currentLocationMarker.setPosition(position);

						callback();

						$(dialogEvent.target).dialog('close');
					} else {
						alert(response ? response.message : ERROR_MESSAGE);
						$('#map-canvas', dialogEvent.target).unmask();
						$('[name=address],[type=submit]', dialogEvent.target).attr('disabled', false);
					}
				}).fail(function(jqXHR, textStatus){
					alert(textStatus);
					$('#map-canvas', dialogEvent.target).unmask();
					$('[name=address],[type=submit]', dialogEvent.target).attr('disabled', false);
				});
			}
		});
}

function resetNews(){
	if (!$.isEmptyObject(newsMarkers)){
		for (var id in newsMarkers){
			newsMarkers[id].remove();
			delete newsMarkers[id];
		}
	}
	resetMarkersCluster();
	$("#newsData").html('');
}

function renderNewsResponse(news, start, isNew){
	for (i in news){
		updateNews(isNew === true ? $(news[i].html).prependTo($("#newsData")) :
			$(news[i].html).appendTo($("#newsData")));

		if (!isNew){
			start++;
		}
	}

	if (news.length == 15){
		$(window).bind('scroll', function(){
			if (modalDialog){
				return false;
			}

			if ($(window).scrollTop() + $(window).height() > $(document).height() - 400){
				$(window).unbind('scroll');
				loadNews(start);
			}
		});
	}

	for (var i in news){
		renderListingMarker(news[i], true);
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
}

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
