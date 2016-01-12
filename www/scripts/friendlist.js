$(function(){
	var center = new google.maps.LatLng(userLatitude, userLongitude);

	renderNewsMap({
		center: center,
		isListing: true
	});

	google.maps.event.addListener(newsMap, 'click', function(event){
		if (!newsMarkerClick){
			$('.scrpBox').removeClass('higlight-news');
		}

		newsMarkerClick = false;
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
			max: 1.5,
			min: 0.5,
			step: 0.1,
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
		renderEditLocationDialog(function(){loadFriendNews(); });
	});

	loadFriendNews();
	
	setHeight('.eqlCH');

	$("#search")
		.val('')
		.autocomplete({
			minLength: 1,
			source: function (request, callback){
				$("#sacrchWait").show();
				
				$.ajax({
					url: baseUrl + 'contacts/search',
					data: {search: request.term},
					type: 'POST',
					dataType: 'json'
				}).done(function(response){
					if (response && response.status){
						callback(response.result);
					} else {
						alert(response ? response.error.message : ERROR_MESSAGE);
					}

					$("#sacrchWait").hide();
				}).fail(function(jqXHR, textStatus){
					alert(textStatus);
					$("#sacrchWait").hide();
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

	if ($('#friendList').size()){
		moreFriends();
	}
});

function loadFriendNews(callback){
	if (!$.isEmptyObject(newsMarkers)){
		for (var id in newsMarkers){
			newsMarkers[id].remove();
			delete newsMarkers[id];
		}
	}

	resetMarkersCluster();

	$.ajax({
		url: baseUrl + 'home/load-friend-news',
		data: {
			keywords: $('#searchNews [name=keywords]').val(),
			latitude: newsMap.getCenter().lat(),
			longitude: newsMap.getCenter().lng(),
			radius: getRadius()
		},
		type: 'POST',
		dataType: 'json',
		async: false
	}).done(function(response){
		if (response && response.status){
			if (typeof callback == 'function'){
				callback();
			}

			if (typeof response.result !== 'object'){
				return false;
			}

			for (var i in response.result){
				renderListingMarker(response.result[i], false);
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
		} else {
			alert(response ? response.error.message : ERROR_MESSAGE);
		}
	});
}

function moreFriends(){
	var offset = $('#friendList > .invtFrndList').size();

	$.ajax({
		url: baseUrl + 'contacts/friends-list-load',
		data: {offset: offset},
		type: 'POST',
		dataType: 'json'
	}).done(function(response){
		if (response && response.status){
			$('.postClass_after').remove();

			if (!response.friends){
				return false;
			}

			for (var x in response.friends){
				$("#friendList").append(
					$('<div/>', {'class': 'invtFrndList', 'id': 'user-' + response.friends[x].id}).append(
						$('<ul/>', {'class': 'invtFrndRow'}).append(
							$('<li/>', {'class': 'img'}).append(
								$('<a/>', {href: baseUrl + 'home/profile/user/' + response.friends[x].id}).append(
									$('<img/>', {src: response.friends[x].image})
								)
							),
							$('<li/>', {'class': 'name'}).append(
								response.friends[x].name,
								$('<span/>', {'class': 'loc'}).text(response.friends[x].address)
							),
							$('<li/>', {'class': 'message btnCol'}).append(
								$('<img/>', {src: baseUrl + 'www/images/envelope-icon.gif'}).click(function(){
									userMessageDialog($(this).closest('.invtFrndList').attr('id').replace('user-', ''));
								})
							),
							$('<li/>', {'class': 'delete btnCol'}).append(
								$('<img/>', {src: baseUrl + 'www/images/delete-icon.png'}).click(function(){
									if (!confirm("Are you sure to delete this friend?")){
										return false;
									}

									var target = $(this).closest('.invtFrndList').attr('disabled', true),
										id = target.attr('id').replace('user-', '');

									ajaxJson({
										url: baseUrl + 'contacts/friend',
										data: {
											user: id,
											action: 'reject'
										},
										done: function(){
											target.remove();
											friends_count--;
											loadFriendNews();
										}
									})
								})
							),
							$('<div/>', {'class': 'clr'})
						),
						$('<div/>', {'class': 'clr'})
					),
					$('<div/>', {'class': 'clr'})
				);

				if ($("#midColLayout").height() > 714){
					setThisHeight(Number($("#midColLayout").height()));
				}
				
				offset++;
			}

			if (offset < friends_count){
				$('.listHight').after(
					$('<div/>', {'class': 'postClass_after'})
						.click(function(){
							$(this).append($('<img/>', {src: baseUrl + 'www/images/wait.gif'}));
							moreFriends();
						})
						.append($('<lable/>').text('More'))
				);
			}
		} else {
			alert(response ? response.error.message : ERROR_MESSAGE);
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
	});
}
