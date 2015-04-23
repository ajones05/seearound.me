var newsLocationMap, newsLocationMarker, newsLocationInfoWindow;

function renderNews($news){
	$('.textAreaClass', $news)
		.textareaAutoSize()
		.bind('input paste keypress', function(e){
			if (!isLogin){
				$.colorbox({
					width: '26%',
					height: '20%',
					inline: true,
					href: '#login-id',
					open: true
				}, function(){
					$('html, body').animate({scrollTop: 0}, 0);
				});

				return false;
			}

			var $target = $(this),
				comment = $target.val(),
				news_id = getNewsID($target);

			if (comment.length == 0){
				return true;
			}

			if (comment.length > 250){
				$target.val(comment = comment.substring(0, 250));
				alert("The comment should be less the 250 Characters");
				return false;
			}

			if (comment.indexOf('<') > 0 || comment.indexOf('>') > 0){
				alert('You enter invalid text');
				$target.val(comment.replace('<', '').replace('>', ''));
				return false;
			}

			if (e.keyCode === 13){
				$target.attr('disabled', true);

				$('.commentLoading').show();

				$.ajax({
					url: baseUrl + 'home/add-new-comments',
					data: {
						comment: comment,
						news_id: news_id
					},
					type: 'POST',
					dataType: 'json',
					async: false
				}).done(function(response){
					if (response && response.status){
						e.preventDefault();
						$target.val('').height('auto').attr('disabled', false).blur();
						renderComments($(response.html).insertBefore($target.closest('.cmntList-last')));
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
			$editButtons = $('.location-post, .delete-post, .save-post', $target),
			news_id = getNewsID($target);

		$editButtons.attr('disabled', true);

		$.ajax({
			url: baseUrl + 'home/edit-news',
			data: {id: news_id},
			type: 'POST',
			dataType: 'json'
		}).done(function(response){
			if (response && response.status){
				$('.post-content, .news-footer', $target).hide();
				$('.edit-news-panel', $target).show();

				$('<textarea/>', {rows: 1, name: 'news'})
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

				$editButtons.attr('disabled', false);
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

	$('.location-post', $news).click(function(){
		var $target = $(this).closest('.scrpBox');

		$('body').css({overflow: 'hidden'});

		$('#post-location').dialog({
			modal: true,
			resizable: false,
			drag: false,
			width: 980,
			height: 500,
			dialogClass: 'colorbox',
			beforeClose: function(event, ui){
				$('body').css({overflow: 'visible'});
			},
			open: function(event, ui){
				var $editForm = $('.edit-news-content', $target),
					$addressField = $('[name=address]', $editForm),
					$latitudeField = $('[name=latitude]', $editForm),
					$longitudeField = $('[name=longitude]', $editForm),
					newsAddress = $addressField.val(),
					$locationDialog = $('#post-location');
					$editAddress = $('[name=address]', $locationDialog)
						.val(newsAddress)
						.attr('disabled', false),
					$submitField = $('#post-location [type=submit]')
						.attr('disabled', false),
					$map = $('#post-location #map-canvas'),
					autocomplete = new google.maps.places.Autocomplete($editAddress[0]),
					centerLocation = new google.maps.LatLng($latitudeField.val(), $longitudeField.val()),
					renderLocation = true;

				$map.unmask();

				if (!newsLocationMap){
					newsLocationMap = new google.maps.Map($map[0], {
						zoom: 14,
						center: centerLocation
					});

					newsLocationMarker = new google.maps.Marker({
						map: newsLocationMap,
						draggable: true,
						position: newsLocationMap.getCenter(),
						icon: new google.maps.MarkerImage(baseUrl + MainMarker1.image, null, null, null,
							new google.maps.Size(MainMarker1.height,MainMarker1.width))
					});

					newsLocationInfoWindow = new google.maps.InfoWindow({
						maxWidth: 220,
						content: commonMap.addressContent(newsAddress, imagePath)
					});

					google.maps.event.addListener(newsLocationMarker, 'dragend', function(event){
						var infoWindowContent = $('#post-location .profile-map-info .user-address');

						newsLocationMap.setCenter(event.latLng);
						infoWindowContent.html($('<div/>').css({textAlign: 'left'}).text('loading...'));

						geocoder.geocode({
							latLng: event.latLng
						}, function(results, status){
							if (status == google.maps.GeocoderStatus.OK){
								infoWindowContent.text(results[0].formatted_address);
								$editAddress.val(results[0].formatted_address);
							} else {
								infoWindowContent.text('');
								$editAddress.val('');
							}

							renderLocation = true;
						});
					});

					google.maps.event.addListener(autocomplete, 'place_changed', function(){
						$('#post-location .panel .search').click();
					});

					$editAddress.keydown(function(e){
						if (e.keyCode == 13){
							e.preventDefault();
						}
					});

					$('#post-location .panel .search').click(function(){
						var value = $.trim($editAddress.val());

						if (value === ''){
							$editAddress.focus();
							return;
						}

						$submitField.attr('disabled', true);

						geocoder.geocode({
							address: value
						}, function(results, status){
							if (status == google.maps.GeocoderStatus.OK){
								$('#post-location .profile-map-info .user-address').text(results[0].formatted_address);
								newsLocationMap.setCenter(results[0].geometry.location);
								newsLocationMarker.setPosition(results[0].geometry.location);
								$editAddress.val(results[0].formatted_address);
								renderLocation = true;
							} else {
								alert('Sorry! We are unable to find this location.');
								renderLocation = false;
							}

							$submitField.attr('disabled', false);
						});
					});

					$('#post-location .panel form').submit(function(e){
						e.preventDefault();

						if (!renderLocation){
							$editAddress.focus();
							return false;
						}

						$('#post-location #map-canvas').mask('Waiting...');
						$submitField.attr('disabled', true);

						$addressField.val($editAddress.val());
						$latitudeField.val(newsLocationMarker.getPosition().lat());
						$longitudeField.val(newsLocationMarker.getPosition().lng());

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
								// TODO: change marker position
								//	- if marker out of radius then remove

								$('#post-location').dialog('close');
							} else if (response){
								alert(response.error.message);
								$map.unmask();
								$('[name=address],[type=submit]', '#post-location').attr('disabled', false);
							} else {
								alert(ERROR_MESSAGE);
								$map.unmask();
								$('[name=address],[type=submit]', '#post-location').attr('disabled', false);
							}
						}).fail(function(jqXHR, textStatus){
							alert(textStatus);
							$map.unmask();
							$('[name=address],[type=submit]', '#post-location').attr('disabled', false);
						});
					});
				} else {
					google.maps.event.trigger(newsLocationMap, 'resize');

					newsLocationMap.setOptions({
						draggable: true,
						zoom: 14,
						center: centerLocation
					});

					newsLocationMarker.setOptions({
						draggable: true,
						position: newsLocationMap.getCenter()
					});
				}

				$('#post-location .profile-map-info .user-address').text(newsAddress);
				newsLocationInfoWindow.open(newsLocationMap, newsLocationMarker);
			}
		});
	});

	$('.delete-post', $news).click(function(e){
		if (!confirm('Are you sure you want to delete?')){
			return false;
		}

		var $target = $(this).closest('.scrpBox'),
			$editButtons = $('.location-post, .delete-post, .save-post', $target),
			news_id = getNewsID($target);

		$editButtons.attr('disabled', true);

		$.ajax({
			url: baseUrl + 'home/delete',
			type: 'POST',
			dataType: 'json',
			data: {id: news_id}
		}).done(function(response){
			if (response && response.status){
				$target.remove();
				removeNewsMarker(news_id);
			} else {
				alert('Sorry! we are unable to performe delete action');
				$editButtons.attr('disabled', false);
			}
		}).fail(function(jqXHR, textStatus){
			alert(textStatus);
			$editButtons.attr('disabled', false);
		});
	});

	$('.save-post', $news).click(function(e){
		var $target = $(this).closest('.scrpBox'),
			$editForm = $('.edit-news-content', $target),
			$editNews = $('[name=news]', $editForm),
			$editButtons = $('.location-post, .delete-post, .save-post', $target),
			value = $.trim($editNews.val()),
			news_id = getNewsID($target);

		if (value === ''){
			$editNews.focus();
			return false;
		}

		$editButtons.attr('disabled', true);

		$.ajax({
			url: baseUrl + 'home/save-news',
			data: $('[name=id],[name=news]', $editForm).serialize(),
			type: 'POST',
			dataType: 'json',
			beforeSend: function(jqXHR, settings){
				$editNews.attr('disabled', true);
			}
		}).done(function(response){
			if (response && response.status){
				$('.edit-news-panel', $target).hide();
				$editForm.remove();
				$('.post-content', $target).html(response.html).show();
				$('.news-footer', $target).show();
				$('.edit-post', $target).attr('disabled', false);

				// TODO: change marker tooltip text
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
	});

	renderComments($news.find('.cmntList'));
}

function showCommentHandle(){
	if (!isLogin){
		$.colorbox({
			width: '26%',
			height: '20%',
			inline: true,
			href: '#login-id',
			open: true
		}, function(){
			$('html, body').animate({scrollTop: 0}, 0);
		});

		return false;
	}

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

// TODO: test
function removeNewsMarker(news_id){
	var doFlag = true;

	for (x in commonMap.bubbleArray){
		for (y in commonMap.bubbleArray[x].newsId){
			if (commonMap.bubbleArray[x].newsId[y] == news_id){
				if (commonMap.bubbleArray[x].newsId.length == 1){
					if (x == 0){
						commonMap.bubbleArray[0].contentArgs[0][2] = 'This is me!';
						commonMap.bubbleArray[0].contentArgs[0][6] = 0;
						commonMap.bubbleArray[0].newsId = new Array(); 
						commonMap.bubbleArray[0].currentNewsId = null;
						commonMap.bubbleArray[x].total = 0;
						commonMap.bubbleArray[0].divContent = commonMap.createContent(
							commonMap.bubbleArray[0].contentArgs[0][0],
							commonMap.bubbleArray[0].contentArgs[0][1],
							commonMap.bubbleArray[0].contentArgs[0][2],
							commonMap.bubbleArray[0].contentArgs[0][3],
							commonMap.bubbleArray[0].contentArgs[0][4],
							commonMap.bubbleArray[0].contentArgs[0][5],
							commonMap.bubbleArray[0].contentArgs[0][6],
							true
						);

						$("#mainContent_0").each(function(){
							if ($(this).html()==''){
								$(this).remove();
							} else {
								$(this).attr('currentdiv', 1);
								$(this).attr('totalDiv', 1);
								$("#prevDiv_0").css('display', 'none');
								$("#nextDiv_0").css('display', 'none');
							}
						});
					} else {
						commonMap.bubbleArray[x].contentArgs = new Array();
						commonMap.bubbleArray[x].newsId = new Array();
						commonMap.marker[x].setMap(null);
						commonMap.marker = mergeArray(commonMap.marker,x);
						doFlag = false;
						break;
					}
				} else {
					commonMap.bubbleArray[x].contentArgs = mergeArray(commonMap.bubbleArray[x].contentArgs, y);
					commonMap.bubbleArray[x].newsId = mergeArray(commonMap.bubbleArray[x].newsId, y);
					commonMap.bubbleArray[x].user_id = mergeArray(commonMap.bubbleArray[x].user_id, y);
					commonMap.bubbleArray[x].currentNewsId = commonMap.bubbleArray[x].newsId[0];
					commonMap.bubbleArray[x].total = commonMap.bubbleArray[x].user_id.length;
					commonMap.bubbleArray[x].contentArgs[0][4] = 'first';
					commonMap.bubbleArray[x].contentArgs[0][5] = 1;

					var arrowflag = true;

					if (commonMap.bubbleArray[x].newsId.length > 1){
						arrowflag = false;
					}

					commonMap.bubbleArray[x].divContent = commonMap.createContent(
						commonMap.bubbleArray[x].contentArgs[0][0],
						commonMap.bubbleArray[x].contentArgs[0][1],
						commonMap.bubbleArray[x].contentArgs[0][2],
						1,
						commonMap.bubbleArray[x].contentArgs[0][4],
						commonMap.bubbleArray[x].contentArgs[0][5],
						commonMap.bubbleArray[x].contentArgs[0][6],
						arrowflag
					);

					$("#mainContent_"+x).each(function(){
						if ($(this).html()==''){
							$(this).remove();
						} else {
							$(this).attr('currentdiv',1);
							$(this).attr('totalDiv',commonMap.bubbleArray[x].newsId.length);
							$(this).html(commonMap.bubbleArray[x].divContent);
						}
					});
				}
			}
		}

		if (!doFlag){
			break;
		}
	}
}

function getNewsID(target){
	return parseInt(target.attr('id').replace('scrpBox_', ''));
}
