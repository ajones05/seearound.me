var map, infowindow, markers = {};

$(function(){
	map = new google.maps.Map($('#map_canvas')[0], {
		zoom: 14,
		minZoom: 13,
		maxZoom: 15,
		center: new google.maps.LatLng(userLatitude, userLongitude),
		disableDefaultUI: true,
		panControl: false,
		zoomControl: false,
		scaleControl: false,
		streetViewControl: false,
		overviewMapControl: false,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	});

	google.maps.event.addListenerOnce(map, 'idle', function(){
		var marker = new google.maps.Marker({
			position: map.getCenter(),
			map: map,
			icon: baseUrl + 'www/images/icons/icon_1.png'
		});

		infowindow = new google.maps.InfoWindow({
			content: userAddressTooltip(userAddress, imagePath)
		});

		infowindow.open(map, marker);
	});

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
					} else if (response){
						alert(response.error.message);
					} else {
						alert(ERROR_MESSAGE);
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
									var $target = $(this).closest('.invtFrndList'),
										id = $target.attr('id').replace('user-', '');

									deleteFriend(id, $target, function(){
										friends_count--;
										markers[id].setMap(null);
									});
								})
							),
							$('<div/>', {'class': 'clr'})
						),
						$('<div/>', {'class': 'clr'})
					),
					$('<div/>', {'class': 'clr'})
				);

				var marker = new google.maps.Marker({
					map: map,
					position: new google.maps.LatLng(response.friends[x].latitude, response.friends[x].longitude),
					icon: baseUrl + 'www/images/icons/icon_2.png',
					data: {
						address: response.friends[x].address,
						image: response.friends[x].image,
					}
				});

				google.maps.event.addListener(marker, 'click', function(){
					if (infowindow){
						infowindow.close();
					}

					infowindow.setContent(userAddressTooltip(this.data.address, this.data.image));
					infowindow.open(map, this);
				});
				
				markers[response.friends[x].id] = marker;

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
		} else if (response){
			alert(response.error.message);
		} else {
			alert(ERROR_MESSAGE);
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
	});
}
