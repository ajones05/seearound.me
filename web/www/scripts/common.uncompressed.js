var timezoneOffset = new Date().getTimezoneOffset(),
monthNames = ['January','February','March','April','May','June',
'July','August','September','October','November','December'],
ERROR_MESSAGE = 'Internal server error';

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
					$('<input/>', {type: 'button'}).val('').addClass('search'),
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
					autocompleteSel=false,
					submitInput=$('.search',locationForm).attr('clicked',false),
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
				},
				submitAutocompleteFormHandler=function(){
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
				};
				var map = new google.maps.Map(document.getElementById('map-canvas'),{
					zoom:options.mapZoom,
					center:options.center,
					draggable:false,
					zoomControl:false,
					scrollwheel:false,
					disableDoubleClickZoom:true,
					styles:[
						{
							"elementType": "labels.icon",
							"stylers": [
								{
									"visibility": "off"
								}
							]
						},
						{
							"featureType": "administrative.neighborhood",
							"elementType": "geometry.fill",
							"stylers": [
								{
									"lightness": 30
								}
							]
						},
						{
							"featureType": "administrative.neighborhood",
							"elementType": "geometry.stroke",
							"stylers": [
								{
									"lightness": 35
								}
							]
						},
						{
							"featureType": "administrative.neighborhood",
							"elementType": "labels.icon",
							"stylers": [
								{
									"visibility": "off"
								}
							]
						},
						{
							"featureType": "landscape",
							"elementType": "labels.icon",
							"stylers": [
								{
									"visibility": "off"
								}
							]
						},
						{
							"featureType": "landscape.man_made",
							"elementType": "geometry",
							"stylers": [
								{
									"lightness": 30
								}
							]
						},
						{
							"featureType": "landscape.man_made",
							"elementType": "geometry.fill",
							"stylers": [
								{
									"lightness": 25
								}
							]
						},
						{
							"featureType": "landscape.man_made",
							"elementType": "geometry.stroke",
							"stylers": [
								{
									"lightness": -5
								}
							]
						},
						{
							"featureType": "landscape.man_made",
							"elementType": "labels",
							"stylers": [
								{
									"weight": 0.5
								}
							]
						},
						{
							"featureType": "landscape.natural",
							"elementType": "labels",
							"stylers": [
								{
									"weight": 0.5
								}
							]
						},
						{
							"featureType": "poi",
							"elementType": "labels.icon",
							"stylers": [
								{
									"visibility": "off"
								}
							]
						},
						{
							"featureType": "poi",
							"elementType": "labels.text",
							"stylers": [
								{
									"visibility": "off"
								}
							]
						},
						{
							"featureType": "poi.business",
							"stylers": [
								{
									"visibility": "off"
								}
							]
						},
						{
							"featureType": "poi.park",
							"elementType": "geometry.fill",
							"stylers": [
								{
									"color": "#bbe88c"
								}
							]
						},
						{
							"featureType": "road",
							"elementType": "labels",
							"stylers": [
								{
									"weight": 0.5
								}
							]
						},
						{
							"featureType": "road",
							"elementType": "labels.icon",
							"stylers": [
								{
									"visibility": "off"
								}
							]
						},
						{
							"featureType": "road.highway",
							"elementType": "geometry.fill",
							"stylers": [
								{
									"lightness": 25
								}
							]
						},
						{
							"featureType": "road.highway",
							"elementType": "geometry.stroke",
							"stylers": [
								{
									"color": "#f1c358"
								}
							]
						},
						{
							"featureType": "road.local",
							"stylers": [
								{
									"weight": 1
								}
							]
						},
						{
							"featureType": "road.local",
							"elementType": "labels",
							"stylers": [
								{
									"weight": 0.5
								}
							]
						},
						{
							"featureType": "road.local",
							"elementType": "labels.icon",
							"stylers": [
								{
									"visibility": "off"
								}
							]
						},
						{
							"featureType": "transit",
							"stylers": [
								{
									"visibility": "off"
								}
							]
						},
						{
							"featureType": "transit",
							"elementType": "labels.icon",
							"stylers": [
								{
									"visibility": "off"
								}
							]
						},
						{
							"featureType": "water",
							"elementType": "geometry.fill",
							"stylers": [
								{
									"color": "#9cc9ff"
								},
								{
									"lightness": 10
								}
							]
						}
					]
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

				submitInput.on('click', function(){
					if ($(this).is(':disabled')){
						return;
					}
					$(this).attr('clicked', true);
					locationForm.submit();
				});

				locationForm.submit(function(e){
					e.preventDefault();

					if (submitInput.attr('clicked')==true){
						submitInput.attr('clicked',false);
						submitAutocompleteFormHandler();
					} else {
						locationAddress.attr('disabled',true);
						setTimeout(function(){
							locationAddress.attr('disabled',false);
							if (submitInput.is(':disabled')){
								return true;
							}
							submitAutocompleteFormHandler();
						},200)
					}
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
					$(event.target).dialog('destroy').remove();
					return callback($('#locationTimezone').val());
				}
			})
	});
}

function getTimestamp(){
	return (Math.round((new Date().getTime()) / 1000)).toString();
}

function formatOutputDate(date){
	return monthNames[date.getMonth()]+' '+date.getDate()+' at '+formatTime(date);
}

function formatTime(date){
	var hours = date.getHours();
	var minutes = date.getMinutes();
	var ampm = hours >= 12 ? 'pm' : 'am';
	hours = hours % 12;
	hours = hours ? hours : 12;
	minutes = minutes < 10 ? '0'+minutes : minutes;
	var strTime = hours + ':' + minutes + ampm;
	return strTime;
}

function resetTimeAgo(){
	$.timeago.settings.strings.seconds='Just now';
	$.timeago.settings.strings.minute='1 minute';
	$.timeago.settings.strings.hour='1 hour';
	$.timeago.settings.strings.hours='%d hours';
	$.timeago.settings.strings.day='Yesterday';
	$.timeago.settings.strings.month='1 month';
	$.timeago.settings.strings.year='1 year';
}

function userMessageDialog(userId){
	$('body').css({overflow: 'hidden'});

	$('<div/>').addClass('message-dialog location-dialog')
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
					).addClass('panel')
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
			dialogClass: 'dialog colorbox', // remove legacy class "colorbox"
			beforeClose: function(event, ui){
				$('body').css({overflow: 'visible'});
				$(event.target).dialog('destroy').remove();
			},
			open: function(event, ui){
				var dialog=event.target;
				$('form', dialog).on('submit', function(e){
					e.preventDefault();
					var hasError=false,
						form=$(this),
						subjectFl=form.find('[name=subject]'),
						subject=subjectFl.val(),
						bodyFl=form.find('[name=message]');
						body=bodyFl.val();

					if ($.trim(subject)===''){
						subjectFl.addClass('error');
						hasError=true;
					}

					if ($.trim(body)===''){
						bodyFl.addClass('error');
						hasError=true;
					}

					if (hasError){
						return false;
					}

					var formInputs=form.find('input,textarea').attr('disabled',true);

					ajaxJson({
						url:baseUrl+'message/send',
						data:{
							subject:subject,
							message:body,
							user_id:userId
						},
						done: function(){
							$(dialog).addClass('success').empty().append(
								$('<img/>', {src: baseUrl+'www/images/correct.gif'}),
								$('<span/>').text('Message sent')
							);
						}
					});
				});
			}
		});
}

function ajaxJson(url, settings){
	if (typeof url === 'string'){
		settings.url = url;
	} else if (typeof url === 'object'){
		settings = url;
	}

	settings.dataType = 'json';

	var jqxhr = $.ajax($.extend(settings, {type: 'POST'}))
		.done(function(data, textStatus, jqXHR){
			var isObject = typeof data === 'object';
			if (!isObject || data.status == 0){
				if (typeof settings.fail === 'function'){
					settings.fail(data, textStatus, jqXHR);
				}
				if (settings.failMessage !== false){
					alert(isObject ? data.message : ERROR_MESSAGE);
				}
				return false;
			}

			if (typeof settings.done === 'function'){
				return settings.done(data, textStatus, jqXHR);
			}
		}).fail(function(jqXHR, textStatus){
			if (jqXHR.readyState == 0 || jqXHR.status == 0){
				return true;
			}
			if (typeof settings.fail === 'function'){
				settings.fail({}, textStatus, jqXHR);
			}
			if (settings.failMessage !== false){
				alert(ERROR_MESSAGE);
			}
		});

	return jqxhr;
}

function alertDialog(text){
  $('<div/>').text(text).appendTo('body')
    .dialog({
      resizable: false,
      height: "auto",
      width: 300,
      modal: true,
      dialogClass: 'alert',
      buttons:{
        Okay:function(){
          $(this).dialog("close");
        }
      },
      beforeClose: function(event, ui){
        $(event.target).dialog('destroy').remove();
      }
    });
}

function setCookie(name, value, days){
	var d = new Date();
	d.setTime(d.getTime() + (days * 86400000));
	document.cookie = name+'='+value+'; expires='+d.toUTCString();
}

function getCookie(name){
	var name = name+'=', ca = document.cookie.split(';');
	for(var i=0; i<ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1);
		if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
	}
	return '';
}

function userAddressTooltip(address, image){
    return '<div class="location-dialog__user">' +
		'<img src="' + image + '" />' +
		'<div class="address">' + address + '</div>' +
	'</div>';
}

function parsePlaceAddress(place){
	var placeData = {}, addressFields = {
		street_number: 'street_number',
		route: 'street_name',
		locality: 'city',
		administrative_area_level_1: 'state',
		country: 'country',
		postal_code: 'zip'
	};

	for (var i in place.address_components){
		var component = place.address_components[i],
			type = component.types[0];
		if (addressFields[type]){
			placeData[addressFields[type]] = type=='route'?
				component.long_name:component.short_name;
		}
	}

	return placeData;
}

function keyCode(event){
	return event.keyCode || event.which;
}

function getDistance(p1, p2){
	p1[0] = parseFloat(p1[0]);
	p1[1] = parseFloat(p1[1]);
	p2[0] = parseFloat(p2[0]);
	p2[1] = parseFloat(p2[1]);

	if (p1[0] == p2[0] && p1[1] == p2[1]){
		return 0;
	}

	var theta = p1[1] - p2[1];
	var dist = Math.sin(p1[0] * Math.PI / 180) * Math.sin(p2[0] * Math.PI / 180) +
		Math.cos(p1[0] * Math.PI / 180) * Math.cos(p2[0] * Math.PI / 180) *
		Math.cos(theta * Math.PI / 180);
	var miles = (Math.acos(dist) * 180 / Math.PI) * 60 * 1.1515;
	return miles;
}

Object.size = function(obj){
	var size = 0, key;
	for (key in obj){
		if (obj.hasOwnProperty(key)) size++;
	}
	return size;
};

/**
 * Timeago is a jQuery plugin that makes it easy to support automatically
 * updating fuzzy timestamps (e.g. "4 minutes ago" or "about 1 day ago").
 *
 * @name timeago
 * @version 1.3.0
 * @requires jQuery v1.2.3+
 * @author Ryan McGeary
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 *
 * For usage and examples, visit:
 * http://timeago.yarp.com/
 *
 * Copyright (c) 2008-2013, Ryan McGeary (ryan -[at]- mcgeary [*dot*] org)
 */

(function (factory) {
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as an anonymous module.
    define(['jquery'], factory);
  } else {
    // Browser globals
    factory(jQuery);
  }
}(function ($) {
  $.timeago = function(timestamp) {
    if (timestamp instanceof Date) {
      return inWords(timestamp);
    } else if (typeof timestamp === "string") {
      return inWords($.timeago.parse(timestamp));
    } else if (typeof timestamp === "number") {
      return inWords(new Date(timestamp));
    } else {
      return inWords($.timeago.datetime(timestamp));
    }
  };
  var $t = $.timeago;

  $.extend($.timeago, {
    settings: {
      refreshMillis: 60000,
      allowFuture: false,
      localeTitle: false,
      cutoff: 0,
      strings: {
        prefixAgo: null,
        prefixFromNow: null,
        suffixAgo: "ago",
        suffixFromNow: "from now",
        seconds: "less than a minute",
        minute: "about a minute",
        minutes: "%d minutes",
        hour: "about an hour",
        hours: "about %d hours",
        day: "1 day",
        days: "%d days",
        month: "about a month",
        months: "%d months",
        year: "about a year",
        years: "%d years",
        wordSeparator: " ",
        numbers: []
      }
    },
    inWords: function(distanceMillis) {
      var $l = this.settings.strings;
      var prefix = $l.prefixAgo;
      var suffix = $l.suffixAgo;
      if (this.settings.allowFuture) {
        if (distanceMillis < 0) {
          prefix = $l.prefixFromNow;
          suffix = $l.suffixFromNow;
        }
      }

      var seconds = Math.abs(distanceMillis) / 1000;
      var minutes = seconds / 60;
      var hours = minutes / 60;
      var days = hours / 24;
      var years = days / 365;

      function substitute(stringOrFunction, number) {
        var string = $.isFunction(stringOrFunction) ? stringOrFunction(number, distanceMillis) : stringOrFunction;
        var value = ($l.numbers && $l.numbers[number]) || number;
        return string.replace(/%d/i, value);
      }

      var words = seconds < 45 && substitute($l.seconds, Math.round(seconds)) ||
        seconds < 90 && substitute($l.minute, 1) ||
        minutes < 45 && substitute($l.minutes, Math.round(minutes)) ||
        minutes < 90 && substitute($l.hour, 1) ||
        hours < 24 && substitute($l.hours, Math.round(hours)) ||
        hours < 42 && substitute($l.day, 1) ||
        days < 30 && substitute($l.days, Math.round(days)) ||
        days < 45 && substitute($l.month, 1) ||
        days < 365 && substitute($l.months, Math.round(days / 30)) ||
        years < 1.5 && substitute($l.year, 1) ||
        substitute($l.years, Math.round(years));

      var separator = $l.wordSeparator || "";
      if ($l.wordSeparator === undefined) { separator = " "; }
      return $.trim([prefix, words, suffix].join(separator));
    },
    parse: function(iso8601) {
      var s = $.trim(iso8601);
      s = s.replace(/\.\d+/,""); // remove milliseconds
      s = s.replace(/-/,"/").replace(/-/,"/");
      s = s.replace(/T/," ").replace(/Z/," UTC");
      s = s.replace(/([\+\-]\d\d)\:?(\d\d)/," $1$2"); // -04:00 -> -0400
      return new Date(s);
    },
    datetime: function(elem) {
      var iso8601 = $t.isTime(elem) ? $(elem).attr("datetime") : $(elem).attr("title");
      return $t.parse(iso8601);
    },
    isTime: function(elem) {
      // jQuery's `is()` doesn't play well with HTML5 in IE
      return $(elem).get(0).tagName.toLowerCase() === "time"; // $(elem).is("time");
    }
  });

  // functions that can be called via $(el).timeago('action')
  // init is default when no action is given
  // functions are called with context of a single element
  var functions = {
    init: function(){
      var refresh_el = $.proxy(refresh, this);
      refresh_el();
      var $s = $t.settings;
      if ($s.refreshMillis > 0) {
        this._timeagoInterval = setInterval(refresh_el, $s.refreshMillis);
      }
    },
    update: function(time){
      $(this).data('timeago', { datetime: $t.parse(time) });
      refresh.apply(this);
    },
    updateFromDOM: function(){
      $(this).data('timeago', { datetime: $t.parse( $t.isTime(this) ? $(this).attr("datetime") : $(this).attr("title") ) });
      refresh.apply(this);
    },
    dispose: function () {
      if (this._timeagoInterval) {
        window.clearInterval(this._timeagoInterval);
        this._timeagoInterval = null;
      }
    }
  };

  $.fn.timeago = function(action, options) {
    var fn = action ? functions[action] : functions.init;
    if(!fn){
      throw new Error("Unknown function name '"+ action +"' for timeago");
    }
    // each over objects here and call the requested function
    this.each(function(){
      fn.call(this, options);
    });
    return this;
  };

  function refresh() {
    var data = prepareData(this);
    var $s = $t.settings;

    if (!isNaN(data.datetime)) {
      if ( $s.cutoff == 0 || distance(data.datetime) < $s.cutoff) {
        $(this).text(inWords(data.datetime));
      }
    }
    return this;
  }

  function prepareData(element) {
    element = $(element);
    if (!element.data("timeago")) {
      element.data("timeago", { datetime: $t.datetime(element) });
      var text = $.trim(element.text());
      if ($t.settings.localeTitle) {
        element.attr("title", element.data('timeago').datetime.toLocaleString());
      } else if (text.length > 0 && !($t.isTime(element) && element.attr("title"))) {
        element.attr("title", text);
      }
    }
    return element.data("timeago");
  }

  function inWords(date) {
    return $t.inWords(distance(date));
  }

  function distance(date) {
    return (new Date().getTime() - date.getTime());
  }

  // fix for IE6 suckage
  document.createElement("abbr");
  document.createElement("time");
}));
