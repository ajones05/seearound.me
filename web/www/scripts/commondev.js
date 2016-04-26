function editLocationDialog(options){
	var locationForm = $('<form/>').attr({autocomplete:false}).hide();
	if (options.cancelButton===true){
		var cancelButton=$('<input/>',{type:'button'})
			.addClass('cancel')
			.val('Cancel')
			.prependTo(locationForm);
	}
	$('body').css({overflow: 'hidden'});
	$('<div/>').addClass('location-dialog')
		.append(
			$('<div/>', {id: 'map-canvas'}),
			$('<div/>').addClass('panel').append(
				locationForm.append(
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
			dialogClass: 'dialog colorbox', // remove legacy class "colorbox"
			beforeClose: function(event, ui){
				if (typeof options.beforeClose === 'function'){
					options.beforeClose(event, ui);
				}
				$('.pac-container').remove();
				$('body').css({overflow: 'visible'});
				$(event.target).dialog('destroy').remove();
			},
			open: function(dialogEvent, ui){
				var renderPlace=false,
					submitInput=$('[type=submit]',locationForm),
					locationAddress=$('[name=address]',locationForm)
						.on('input', function(){submitInput.attr('disabled', false); }),
					autocomplete = new google.maps.places.Autocomplete(locationAddress.get(0)),
					geocoder = new google.maps.Geocoder();
				var renderLocation=function(renderOpt){
					if (typeof renderOpt.place==='undefined'){
						return (function(){
							geocoder.geocode({
								latLng:renderOpt.position
							}, function(results,status){
								renderOpt.place=status===google.maps.GeocoderStatus.OK?
									results[0]:false;
								return renderLocation(renderOpt);
							});
						})();
					}
					renderPlace=renderOpt.place;
					if (renderOpt.update){
						var position=renderOpt.position?renderOpt.position:
							renderPlace.geometry.location;
						if ($.inArray('map',renderOpt.update)>=0){
							map.setCenter(position);
						}
						if ($.inArray('marker',renderOpt.update)>=0){
							marker.setPosition(position);
						}
					}
					var address=renderPlace?renderPlace.formatted_address:'';
					infowindow.setContent(options.infoWindowContent(address));
					infowindow.open(map,marker);
					setGoogleMapsAutocompleteValue(locationAddress.val(address),address);
				};
				var map = new google.maps.Map(document.getElementById('map-canvas'),{
					zoom:options.mapZoom,
					center:options.center,
					draggable:false,
					zoomControl:false,
					scrollwheel:false,
					disableDoubleClickZoom:true,
					styles:[{
						featureType:'poi',
						stylers:[{visibility:'off'}]
					}]
				});
				var marker = new google.maps.Marker({
					position:options.center,
					icon:options.markerIcon,
					draggable:false
				});
				var infowindow = new google.maps.InfoWindow({maxWidth:220});
				google.maps.event.addListener(map,'idle',function(){
					marker.setMap(map);
				});
				google.maps.event.addListener(map,'dragstart',function(){
					infowindow.close();
				});
				google.maps.event.addListener(map,'zoom_changed',function(){
					infowindow.close();
				});
				google.maps.event.addListener(map,'click',function(event){
					infowindow.close();
					renderLocation({position:event.latLng,update:['marker']});
				});
				google.maps.event.addListener(marker,'dragstart',function(){
					infowindow.close();
				});
				google.maps.event.addListener(marker,'dragend',function(event){
					renderLocation({position:event.latLng});
				});

				geocoder.geocode({
					latLng: options.center
				}, function(results, status){
					if (status==google.maps.GeocoderStatus.OK){
						var address=results[0].formatted_address;
						locationAddress.val(address);
						infowindow.setContent(options.infoWindowContent(address));
						infowindow.open(map,marker);
						renderPlace=results[0];
					}
					locationForm.show();
					map.setOptions({
						draggable:true,
						zoomControl:true,
						scrollwheel:true,
						disableDoubleClickZoom:false
					});
					marker.setDraggable(true);
				});

				google.maps.event.addListener(autocomplete,'place_changed',function(){
					var place = autocomplete.getPlace();
					if (!place || typeof place.geometry==='undefined'){
						return false;
					}
					renderLocation({place:place,update:['map','marker']});
				});
				if (options.cancelButton==true){
					cancelButton.click(function(){
						$(dialogEvent.target).dialog('close');
					});
				}
				locationForm.submit(function(e){
					e.preventDefault();
					if (submitInput.is(':disabled')){
						return true;
					}
					var address=$.trim(locationAddress.val());
					if (address===''){
						locationAddress.focus();
						return false;
					}
					submitInput.add(locationAddress).attr('disabled',true);
					geocoder.geocode({
						address:address
					}, function(results, status){
						var elements=locationAddress;
						if (status===google.maps.GeocoderStatus.OK){
							renderLocation({place:results[0],update:['map','marker']});
							elements.add(submitInput);
						} else {
							alert('Sorry! We are unable to find this location.');
							renderPlace=false;
						}
						elements.attr('disabled',false);
					});
				});

				$('.save',locationForm).click(function(){
					$('input',locationForm).attr('disabled',true);
					options.submit(map,dialogEvent,marker.getPosition(),renderPlace);
				});
			}
		});
}

function setGoogleMapsAutocompleteValue(input, value){
	input.blur();
	$('.pac-container .pac-item').addClass('hidden');
	setTimeout(function(){
		input.val(value).change();
	}, .1);
}

function locationTimezone(position,callback){
	$.ajax('https://maps.googleapis.com/maps/api/timezone/json?location='+
		position.lat()+','+position.lng()+'&timestamp=' + getTimestamp())
	.done(function(response){
		if (response.status==='OK'){
			return callback(response.timeZoneId);
		}
		var timeZoneDialog = $('<div/>').appendTo('body'),
			timezoneSelect = $('<select/>',{id:'locationTimezone'})
			.on('change', function(){
				timeZoneDialog.dialog('close');
			})
			.append($('<option/>',{text:'Select One'}).val(''));

		for (var id in timizoneList){
			timezoneSelect.append($('<option/>',{text:timizoneList[id]}).val(id));
		}
		timeZoneDialog
			.append(
				$('<label/>',{'for':'locationTimezone'}).text('Time zone'),
				timezoneSelect
			)
			.dialog({
				title:'Cannot determine location time zone',
				width: 450,
				beforeClose: function(event, ui){
					return callback($('#locationTimezone').val());
					$(event.target).dialog('destroy').remove();
				}
			})
	});
}

function getTimestamp(){
	return (Math.round((new Date().getTime()) / 1000)).toString();
}
