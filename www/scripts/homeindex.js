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
					within: '#map_canvas',
					my: "center bottom",
					at: "center top"
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

	loadNews(0);

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
			loadNews(0);
		})
		.bind("slide", function(event, ui){
			$("#radious").html(ui.value == 1 ? '1.0' : ui.value);
		});

	google.maps.event.addListener(newsMap, 'dragend', function(){
		google.maps.event.clearListeners(newsMap, 'idle');

		google.maps.event.addListener(newsMap, 'idle', function(){
			$('html, body').animate({scrollTop: '0px'}, 300);
			newsMapCircle.changeCenter(newsMap.getCenter(), getRadius());
			loadNews(0);
		});
	});

	$("#locationButton").click(function(){
		$('body').css({overflow: 'hidden'});

		$('<div/>', {'class': 'location-dialog'})
			.append(
				$('<div/>', {id: 'map-canvas'}),
				$('<div/>', {'class': 'panel'}).append(
					$('<form/>').append(
						$('<input/>', {type: 'text', name: 'address', placeholder: 'Enter address'}),
						$('<img/>', {'class': 'search', src: '/www/images/map_search.png'}),
						$('<input/>', {type: 'submit', value: 'Use This Address'})
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
					var $editAddress = $('[name=address]', event.target)
							.val(userAddress)
							.attr('disabled', false),
						$submitField = $('[type=submit]', event.target)
							.attr('disabled', false),
						$map = $('#map-canvas', event.target),
						autocomplete = googleMapsPlacesAutocompleteReset($editAddress[0]),
						centerLocation = newsMapCircle.center,
						geocoder = new google.maps.Geocoder(),
						renderLocation = true;

					var map = new google.maps.Map($map[0], {
						zoom: 14,
						center: centerLocation
					});

					var marker = new google.maps.Marker({
						map: map,
						draggable: true,
						position: map.getCenter(),
						icon: baseUrl + 'www/images/icons/icon_1.png'
					});

					function updateMarker(event){
						geocoder.geocode({
							latLng: event.latLng
						}, function(results, status){
							if (status == google.maps.GeocoderStatus.OK){
								infowindow.setContent(userAddressTooltip(results[0].formatted_address, imagePath));
								$editAddress.val(results[0].formatted_address);
							} else {
								infowindow.setContent(userAddressTooltip('', imagePath));
								$editAddress.val('');
							}

							autocomplete = googleMapsPlacesAutocompleteReset($editAddress[0]);
							renderLocation = true;
							infowindow.open(map, marker);
						});
					}

					var infowindow = new google.maps.InfoWindow({
						maxWidth: 220,
						content: userAddressTooltip(userAddress, imagePath)
					});

					infowindow.open(map, marker);

					google.maps.event.addListener(map, 'click', function(mapEvent){
						infowindow.close();
						marker.setPosition(mapEvent.latLng);
						updateMarker(mapEvent);
					});

					google.maps.event.addListener(marker, 'dragend', function(markerEvent){
						updateMarker(markerEvent);
					});

					google.maps.event.addListener(autocomplete, 'place_changed', function(){
						$('.search', event.target).click();
					});

					$editAddress.keydown(function(e){
						if (e.keyCode == 13){
							e.preventDefault();
						}
					});

					$('.search', event.target).click(function(){
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
								infowindow.setContent(userAddressTooltip(results[0].formatted_address, imagePath));
								map.setCenter(results[0].geometry.location);
								marker.setPosition(results[0].geometry.location);
								$editAddress.val(results[0].formatted_address);
								autocomplete = googleMapsPlacesAutocompleteReset($editAddress[0]);
								renderLocation = true;
							} else {
								alert('Sorry! We are unable to find this location.');
								renderLocation = false;
							}

							$submitField.attr('disabled', false);
						});
					});

					$('form', event.target).submit(function(e){
						e.preventDefault();

						if (!renderLocation){
							$editAddress.focus();
							return false;
						}

						$map.mask('Waiting...');
						$submitField.attr('disabled', true);

						$.ajax({
							url: baseUrl + 'home/change-address',
							data: {
								address: $editAddress.val(),
								latitude: marker.getPosition().lat(),
								longitude: marker.getPosition().lng()
							},
							type: 'POST',
							dataType: 'json',
							beforeSend: function(jqXHR, settings){
								$editAddress.attr('disabled', true);
							}
						}).done(function(response){
							if (response && response.status){
								userAddress = $editAddress.val();
								userLatitude = marker.getPosition().lat();
								userLongitude = marker.getPosition().lng();

								newsMap.setCenter(marker.getPosition());
								newsMapCircle.changeCenter(marker.getPosition(), 0.8);
								currentLocationMarker.setPosition(marker.getPosition());

								var $markerElement = $('#' + currentLocationMarker.id);

								if ($markerElement.data('ui-tooltip')){
									$markerElement.tooltip('destroy');
								}								

								loadNews(0);

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
				}
			});
	});

	$('#newsPost')
		.focus(function(){
			$('#postOptionId').show();
			$('#newsPost').attr('placeholder-data', $('#newsPost').attr('placeholder')).removeAttr('placeholder');
		})
		.blur(function(){
			$('#newsPost').attr('placeholder', $('#newsPost').attr('placeholder-data')).removeAttr('placeholder-data');
		})
		.bind('input paste keypress', editNewsHandle);

	$('#addNewsForm').submit(function(e){
		e.preventDefault();

		if ($.trim($('#newsPost').val()) === ''){
			$('#newsPost').focus();
			return false;
		}

		var userPosition = new google.maps.LatLng(parseFloat(userLatitude), parseFloat(userLongitude));

		if (userPosition.toString() != newsMapCircle.center.toString()){
			$("#locationButton").click();
			return false;
		}

		preparePost(userPosition, userAddress);
	});

	$('#postlocationButton').click(function(){
		if ($.trim($('#newsPost').val()) === ''){
			$('#newsPost').focus();
			return false;
		}

		$('body').css({overflow: 'hidden'});

		$('<div/>', {'class': 'location-dialog'})
			.append(
				$('<div/>', {id: 'map-canvas'}),
				$('<div/>', {'class': 'panel'}).append(
					$('<form/>').append(
						$('<input/>', {type: 'text', name: 'address', placeholder: 'Enter Address'}),
						$('<img/>', {'class': 'search', src: '/www/images/map_search.png'}),
						$('<input/>', {type: 'submit', value: 'Use This Address'})
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
					var $editForm = $('#reg_form'),
						$addressField = $('[name=Location]', $editForm),
						$latitudeField = $('[name=RLatitude]', $editForm),
						$longitudeField = $('[name=RLongitude]', $editForm),
						$editAddress = $('[name=address]', event.target)
							.val(userAddress)
							.attr('disabled', false),
						$submitField = $('[type=submit]', event.target)
							.attr('disabled', false),
						$map = $('#map-canvas', event.target),
						autocomplete = googleMapsPlacesAutocompleteReset($editAddress[0]),
						centerLocation = new google.maps.LatLng(userLatitude, userLongitude),
						geocoder = new google.maps.Geocoder(),
						renderLocation = true;

					var map = new google.maps.Map($map[0], {
						zoom: 14,
						center: centerLocation
					});

					var marker = new google.maps.Marker({
						map: map,
						draggable: true,
						position: map.getCenter(),
						icon: baseUrl + 'www/images/icons/icon_1.png'
					});

					function updateMarker(marker, event){
						geocoder.geocode({
							latLng: event.latLng
						}, function(results, status){
							if (status == google.maps.GeocoderStatus.OK){
								infowindow.setContent(userAddressTooltip(results[0].formatted_address, imagePath));
								$editAddress.val(results[0].formatted_address);
							} else {
								infowindow.setContent(userAddressTooltip('<i>undefined</i>', imagePath));
								$editAddress.val('');
							}

							autocomplete = googleMapsPlacesAutocompleteReset($editAddress[0]);
							renderLocation = true;
							infowindow.open(map, marker);
						});
					}

					var infowindow = new google.maps.InfoWindow({
						maxWidth: 220,
						content: userAddressTooltip(userAddress, imagePath)
					});

					infowindow.open(map, marker);

					google.maps.event.addListener(map, 'click', function(mapEvent){
						infowindow.close();
						marker.setPosition(mapEvent.latLng);
						updateMarker(marker, mapEvent);
					});

					google.maps.event.addListener(marker, 'dragstart', function(){
						infowindow.close();
					});

					google.maps.event.addListener(marker, 'dragend', function(markerEvent){
						updateMarker(this, markerEvent);
					});

					google.maps.event.addListener(autocomplete, 'place_changed', function(){
						$('.search', event.target).click();
					});

					$editAddress.keydown(function(e){
						if (e.keyCode == 13){
							e.preventDefault();
						}
					});

					$('.search', event.target).click(function(){
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
								$('.profile-map-info .user-address', event.target).text(results[0].formatted_address);
								map.setCenter(results[0].geometry.location);
								marker.setPosition(results[0].geometry.location);
								$editAddress.val(results[0].formatted_address);
								autocomplete = googleMapsPlacesAutocompleteReset($editAddress[0]);
								renderLocation = true;
							} else {
								alert('Sorry! We are unable to find this location.');
								renderLocation = false;
							}

							$submitField.attr('disabled', false);
						});
					});

					$('form', event.target).submit(function(e){
						e.preventDefault();

						if (!renderLocation){
							$editAddress.focus();
							return false;
						}

						preparePost(marker.getPosition(), $editAddress.val());

						if (latlngDistance(newsMap.getCenter(), marker.getPosition(), 'M') > getRadius()){
							newsMap.setCenter(marker.getPosition());
							newsMapCircle.changeCenter(newsMap.getCenter(), 0.8);

							loadNews(0);
						}

						$(event.target).dialog('close');
					});
				}
			});
	});

	$('#newsFile').change(function(){
		$('.fileNm').remove();

		$(this).after(
			$('<div/>', {'class': 'fileNm'}).html($(this).val()).append(
				$('<img/>', {src: baseUrl + 'www/images/delete-icon12x12.png'}).click(clearUpload)
			)
		);
	})

	$('.imgInput').click(function(){
		$('#newsFile').click();
	});

	$(".menu dd ul li a").click(function(e){
		e.preventDefault();
		$('#filter_type').val($(this).prop("hash").substring(1));
		loadNews(0);

		$(this).closest('dl').find('.result').html($(this).html());
		$(".menu dd ul").hide();
	});

	$('.result').html("View All");

	$(document).bind('click', function(e){
		var $clicked = $(e.target);

		if (!$clicked.parents().hasClass("menu")){
			$(".menu dd ul").hide();
			$(".menu dt a").removeClass("selected");
		}
	});

	$(".menu dt a").click(function(){
		var clickedId = "#" + this.id.replace(/^link/,"ul");

		$(".menu dd ul").not(clickedId).hide();
		$(clickedId).toggle();
		if ($(clickedId).css("display") == "none"){
			$(this).removeClass("selected");
		} else {
			$(this).addClass("selected");
		}
	});

	$(window).scroll(function(){
		if ($("#midColLayout").height() > 714){
			setThisHeight(Number($("#midColLayout").height()));
		}
	});
});

function clearUpload(){
	$('.fileNm').remove();
	$('#newsFile').val('');
}

function preparePost(location, address){
	var news = $('#newsPost').val();

   	if (news.indexOf('<') > 0 || news.indexOf('>') > 0){
		alert('You enter invalid text');
		$('.bgTxtArea input, .bgTxtArea textarea').attr('disabled', false);
		return false;
	}

	$('#newsWaitOnAdd').show();

	if ($.trim(address) !== ''){
		return addPost(location, address);
	}

	(new google.maps.Geocoder()).geocode({
		'latLng': location
	}, function(results, status){
		if (status == google.maps.GeocoderStatus.OK){
			addPost(location, results[0].formatted_address);
		} else {
			addPost(location);
		}
	});
}

function addPost(location, address){
	var $form = $('#addNewsForm'),
		data = new FormData($form[0]);

	$('.bgTxtArea input, .bgTxtArea textarea').attr('disabled', true);

	if (address){
		data.append('address', address);
	}

	data.append('latitude', location.lat());
	data.append('longitude', location.lng());

	$.ajax({
		url: $form.attr('action'),
		data: data,
		cache: false,
		contentType: false,
		processData: false,
		dataType: 'json',
		type: 'POST',
	}).done(function(response){
		if (response && response.status){
			updateNews($(response.news.html).prependTo($('#newsData')));
			$('#noNews').html('');
			$('#newsWaitOnAdd').hide();

			$('html, body').animate({scrollTop: 0}, 0);

			renderListingMarker(response.news);

			resetMarkersCluster();

			setTimeout(function(){
				updateMarkersCluster();
			}, .1);

			clearUpload();

			$('#postOptionId').hide();
			$('#newsPost').val('').css('height', 36);
			$('#loading').hide();
		} else if (response){
			alert(response.error.message);
		} else {
			alert(ERROR_MESSAGE);
		}

		$('.bgTxtArea input, .bgTxtArea textarea').attr('disabled', false);
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
		$('.bgTxtArea input, .bgTxtArea textarea').attr('disabled', false);
	});

	$("#mainForm").parent().remove();
}
