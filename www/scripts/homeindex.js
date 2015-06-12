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
		renderEditLocationDialog(function(){loadNews(0); });
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

		editLocationDialog({
			mapZoom: 14,
			markerIcon: baseUrl + 'www/images/icons/icon_1.png',
			inputPlaceholder: 'Enter address',
			submitText: 'Use This Address',
			defaultAddress: userAddress,
			center: new google.maps.LatLng(userLatitude, userLongitude),
			infoWindowContent: function(address){
				return userAddressTooltip(address, imagePath);
			},
			submit: function(dialogEvent, position, address){
				preparePost(position, address);

				if (latlngDistance(newsMap.getCenter(), position, 'M') > getRadius()){
					newsMap.setCenter(position);
					newsMapCircle.changeCenter(position, 0.8);
					loadNews(0);
				}

				$(dialogEvent.target).dialog('close');
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

	$(".menu dd ul li").click(function(e){
		$('#filter_type').val($(this).attr("filter"));
		loadNews(0);
		$(this).closest('dt').html($(this).html());
		$(".menu dd ul").hide();
	});

	$(document).bind('click', function(e){
		var $clicked = $(e.target);

		if (!$clicked.parents().hasClass("menu")){
			$(".menu dd ul").hide();
			$(".menu dt").removeClass("selected");
		}
	});

	$(".menu dt").click(function(){
		$(this).toggleClass('selected').closest('dl').find('dd > ul').toggle();
	});

	$(window).scroll(function(){
		if ($("#midColLayout").height() > 714){
			setThisHeight(Number($("#midColLayout").height()));
		}
	});

	$('#searchNews').submit(function(e){
		var $form = $(this),
			$searchInput = $('[name=sv]', $form),
			$searchIcon = $('.search', $form);

		e.preventDefault();

		if ($.trim($searchInput.val()) === ''){
			$searchInput.focus();
			return false;
		}

		$('.clear', $form).remove();
		$searchIcon.hide();
		$searchInput.attr('disabled', true);

		loadNews(0, function(){
			$searchInput.attr('disabled', false);

			$('.sIcn', $form).append(
				$('<img/>', {'class': 'clear', src: baseUrl + 'www/images/close_12x12.png'}).click(function(){
					$(this).remove();
					$searchInput.val('');

					loadNews(0, function(){
						$searchIcon.show();
					});
				})
			);
		});
	});

	if ($.trim($('#searchNews [name=sv]').val()) !== ''){
		$('#searchNews').submit();
	} else {
		loadNews(0);
	}
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
		$images = $('[name=image]', $form),
		data = new FormData();

	data.append('news', $('[name=news]', $form).val());
	data.append('latitude', location.lat());
	data.append('longitude', location.lng());

	if (address){
		data.append('address', address);
	}

	if ($.trim($images.val()) !== ''){
		data.append('image', $images[0].files[0]);
	}

	$('.bgTxtArea input, .bgTxtArea textarea').attr('disabled', true);

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

			renderListingMarker(response.news, true);

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
