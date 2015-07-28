var ERROR_MESSAGE = 'Internal server error';
var currentRequests = 0;

$(function(){
	window.fbAsyncInit = function(){
		FB.init({
			appId: facebook_appId,
			xfbml: true,
			cookie: true,
			version: 'v2.1'
		});
	};

	(function(d, s, id){
		var js, fjs = d.getElementsByTagName(s)[0];
		if (d.getElementById(id)) {return;}
		js = d.createElement(s); js.id = id;
		js.src = "//connect.facebook.net/en_US/sdk.js";
		fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));

	$.ajaxSetup({
		error: function(jqXHR, exception){
			if (jqXHR.status === 0) {
				// alert('Not connect.n Verify Network.');
			} else if (jqXHR.status == 404) {
				alert('Requested page not found. [404]');
			} else if (jqXHR.status == 500) {
				alert('Internal Server Error [500].');
			} else if (exception === 'parsererror') {
				alert('Requested JSON parse failed.');
			} else if (exception === 'timeout') {
				alert('Time out error.');
			} else if (exception === 'abort') {
				alert('Ajax request aborted.');
			} else {
				alert('Uncaught Error.n' + jqXHR.responseText);
			}
		}
	});

	if (isLogin){
        notification();
        setInterval('notification()', 120000);

		$('#logoutLi a').click(function(e){
			var url = $(this).attr('href');

			e.preventDefault();

			FB.getLoginStatus(function(response){
				if (response.authResponse){
					FB.logout(function(){
						window.location.href = url;
					});
				} else {
					window.location.href = url;
				}
			}); 
		});

		$('#myProfLink').click(function(e){
			e.preventDefault();
			e.stopPropagation();

			$('#ddMyProf').show();

			$('body').on('click.menu', function(){
				$('#ddMyProf').hide();
				$(this).off('click.menu');
			});
		});

		$("#ddMyConn").click(function (e){
			e.preventDefault();
			e.stopPropagation();

			$('#ddMyConnList').show();

			$('body').on('click.menu', function(){
				$('#ddMyConnList').hide();
				$(this).off('click.menu');
			});
		});

		$('#searchNews .search').click(function(){
			$('#searchNews').submit();
		});
	} else {
		$('#facebookLogin').click(function(e){
			e.preventDefault();

			var url = $(this).attr('href');
			
			FB.login(function(response){
				if (response.authResponse){
					FB.api('/me', function(response){
						if (response.email){
							window.location.href = url;
						} else {
							alert('Email not activated');
						}
					});
				}
			},{scope: 'email'});
		});

		$('#loginForm').validate({
			errorPlacement: function(error, element){
			},
			rules: {
				email: {
					required: true,
					email: true
				},
				password: {
					required: true
				}
			}
		});
	}
});

function showFriendRequest(thisone){
	$("#ddMyConnList")
		.html('')
		.append(
			$('<p/>').addClass('bgTop'),
			$('<img/>', {src: baseUrl + 'www/images/wait.gif'}).addClass('loading'),
			$('<p/>').addClass('pendReq2')
		);

	$.ajax({
		url: baseUrl + 'contacts/requests',
		type: 'POST',
		dataType: 'json'
	}).done(function(response){
		if (response && response.status){
			$("#ddMyConnList").html('');

			if (response.data){
				currentRequests = 0;
				$("#ddMyConnList").append($('<p/>').addClass('bgTop'));

				for (var x in response.data){ 
					$("#ddMyConnList").append(
						$('<ul/>').addClass('connList').append(
							$('<li/>').addClass('thumb').append(
								$('<img/>', {src: response.data[x].image, width: 40, height: 40})
							),
							$('<li/>').addClass('name').html(response.data[x].name + ' is<br/>following you')
						),
						$('<div/>').addClass('clear')
					);

					currentRequests++;
				}

				if (response.total >= 5){
					$("#ddMyConnList").append(
						$('<div/>').addClass('pendReq').append(
							$('<a/>', {href: baseUrl + 'contacts/all-requests'}).text('View all pending requests')
						)
					);
				} else {
					$("#ddMyConnList").append($('<p/>').addClass('pendReq2'));
				}

				$("#noteTotal").html(response.total);
			} else {
				$("#ddMyConnList").append($('<p/>').addClass('pendReq').text('No new friend request found'));
			}

			$("#ddMyConnList").append('<a class="friend" href="'+baseUrl+'contacts/friends-list">Go to my friends </a>');
		} else {
			alert(response ? response.error.message : ERROR_MESSAGE);
			$("#ddMyConnList").hide();
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
		$("#ddMyConnList").hide();
	});
}

function friendRequest(thisone, id, action){
	$(thisone).siblings('img').toggle();

	$.ajax({
		url: baseUrl + 'contacts/friend',
		type: 'POST',
		data: {
			user: id,
			action: action,
			total: true
		},
		dataType: 'json'
	}).done(function(response){
		if (response && response.status){
			$('#noteTotal').html(response.total);
			$(thisone).parent().parent().remove();

			currentRequests--;

			if (response.total == 0){
				$('#noteTotal').hide();
			}

			if (currentRequests <= 0){
				showFriendRequest();
			}
		} else {
			alert(response ? response.error.message : ERROR_MESSAGE);
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
	});
}

// TODO: merge with friendRequest

function deleteFriend(userId, target, callback){
	if (!confirm("Are you sure to delete this friend?")){
		return false;
	}

	$.ajax({
		url: baseUrl + 'contacts/friend',
		type: 'POST',
		data: {
			user: userId,
			action: 'reject'
		},
		dataType: 'json'
	}).done(function(response){
		if (response && response.status){
			$(target).remove();

			if (typeof callback === 'function'){
				callback.call();
			}
		} else {
			alert(response ? response.error.message : ERROR_MESSAGE);
		}
	}).fail(function(jqXHR, textStatus){
		alert(textStatus);
	});
}

function setGoogleMapsAutocompleteValue(input, value){
	input.blur();
	$('.pac-container .pac-item').addClass('hidden');
	setTimeout(function(){
		input.val(value).change();
	}, .1);
}

function userMessageDialog(userId){
	$('body').css({overflow: 'hidden'});

	$('<div/>', {'class': 'message-dialog'})
		.append(
			$('<img/>', {src: baseUrl + 'www/images/mail_send.gif'}),
			$('<span/>', {'class': 'locppmsgimg'}).text('Send Message'),
			$('<div/>', {'class': 'row-content'}).append(
				$('<form/>').append(
					$('<div/>').append(
						$('<input/>', {
							type: 'text',
							name: 'subject',
							placeholder: 'Please enter subject...'
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
					$('<input/>', {type: 'hidden', name: 'user_id'}).val(userId)
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
						subject: {
							required: true
						},
						message: {
							required: true
						}
					},
					submitHandler: function(form){
						$(event.target).mask('Loading...');

						$.ajax({
							url: baseUrl + 'message/send',
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
}

function userAddressTooltip(address, image){
    return '<div class="profile-map-info">' +
		'<div class="user-img">' +
			'<img src="' + image + '" />' +
		'</div>' +
		'<div class="user-address">' + address + '</div>' +
	'</div>';
}

function notification(){
	$.ajax({
		url: baseUrl + 'contacts/friends-notification',
		type: 'POST',
		dataType: 'json'
	}).done(function(response){
		if (response && response.status){
			if (response.friends > 0){
				$("#noteTotal").html(response.friends).show();
			} else {
				$("#noteTotal").hide();
			}

			if (response.messages > 0){
				$("#msgTotal").html(response.messages).show();
			} else {
				$("#msgTotal").hide();
			}
		} else {
			if (response && response.error.code == 401){
				window.location.href = baseUrl;
				return false;
			}

			alert(response ? response.error.message : ERROR_MESSAGE);
		}
	});
}

// TODO: ???
function setThisHeight(height){
	var commentClass = ['leftCol eqlCH', 'rightCol textAlignCenter eqlCH'];

	$(".mainContainer div").each(function(){
		for (var xxx in commentClass){
			if (commentClass[xxx].indexOf($(this).attr('class')) >= 0){
				$(this).css('min-height','auto');
				$(this).height("100%");
					if(!$(this).hasClass('mapDiv')) {
					$(this).css("position","absolute");
				}
			}
		}
	});

	$('#loading').hide();
	$("#newsData").css("position","relative");
	$("#newsData").css("height","100%");
}

// TODO: ???
function setHeight(col){
	var maxHeight = 0;

    $(col).each(function(){
		if ($(this).height() > maxHeight){
            maxHeight = $(this).height();
        }
    });

	$(col).css('min-height', maxHeight);
}

function keyCode(event){
	return event.keyCode || event.which;
}

function editLocationDialog(options){
	$('body').css({overflow: 'hidden'});

	$form = $('<form/>');

	// TODO: make buttons optional
	if (typeof options.cancelButton !== 'undefined' && options.cancelButton === true){
		$form.append($('<input/>', {type: 'button'}).val('Cancel').addClass('cancel'));
	}

	$('<div/>', {'class': 'location-dialog'})
		.append(
			$('<div/>', {id: 'map-canvas'}),
			$('<div/>', {'class': 'panel'}).append(
				$form.append(
					$('<input/>', {type: 'text', name: 'address', placeholder: options.inputPlaceholder}),
					$('<input/>', {type: 'submit'}).val('').addClass('search'),
					$('<input/>', {type: 'button'}).val(options.submitText).addClass('save')
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
			open: function (dialogEvent, ui){
				var $editAddress = $('[name=address]', dialogEvent.target)
						.val(options.defaultAddress)
						.attr('disabled', false),
					$submitField = $('[type=submit]', dialogEvent.target)
						.attr('disabled', false),
					$map = $('#map-canvas', dialogEvent.target),
					autocomplete = new google.maps.places.Autocomplete($editAddress[0]),
					geocoder = new google.maps.Geocoder(),
					renderLocation = true;

				var map = new google.maps.Map($map[0], {
					zoom: options.mapZoom,
					center: options.center
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

					var value = $.trim($editAddress.val());

					if (value === ''){
						$editAddress.focus();
						return false;
					}

					if (renderLocation){
						return true;
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

					$(dialogEvent.target).closest('.ui-dialog').mask('Loading...');
					$('input', dialogEvent.target).attr('disabled', true);
					options.submit(dialogEvent, marker.getPosition(), $editAddress.val());
				});

				$('.cancel', dialogEvent.target).click(function(){
					$(dialogEvent.target).dialog('close');
				});

				function updateMarker(event){
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

				function renderLocationAddress(address, location){
					infowindow.setContent(options.infoWindowContent(address));
					map.setCenter(location);
					marker.setPosition(location);
					$editAddress.val(address);
					setGoogleMapsAutocompleteValue($editAddress, address);
					renderLocation = true;
				}
			}
		});
}
