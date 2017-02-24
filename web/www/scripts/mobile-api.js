$(function(){
	var requestCount = 0, apiForm = $('.container form').submit(function(e){
		var form = $(this),
			outputData = {},
			data = new FormData();
		e.preventDefault();

		$('form :input').each(function(){
			var isValid = true,
				value = $(this).val(),
				name = $(this).attr('name'),
				type = $(this).attr('type');
			switch (type){
				case 'checkbox':
				case 'radio':
					if (!$(this).is(':checked')){
						isValid=false;
					}
			}
			if (isValid && $.trim(value) !== ''){
				if ($.isArray(value)){
					for (var i in value){
						data.append(name+'[]', $(this).attr('type') == 'file' ?
							$(this)[i].files[0] : value[i]);
					}
				} else {
					data.append(name, $(this).attr('type') == 'file' ?
						$(this)[0].files[0] : value);
				}
				outputData[name] = value;
			}
		});

		$.ajax({
			url: form.attr('action'),
			method: form.attr('method'),
			data: data,
			contentType: false,
			processData: false
		}).done(function(response){
			form.after($('<div/>').addClass('panel panel-default').append(
				$('<div/>').addClass('panel-heading').text('#' + (++requestCount) + ' - ' + form.attr('action')),
				$('<div/>').addClass('panel-body').append(
					$('<pre/>').text(JSON.stringify(outputData, null, 4)),
					$('<pre/>').text(JSON.stringify(response, null, 4))
				)
			));
		}).fail(function(jqXHR, textStatus){
			form.after($('<div/>').addClass('panel panel-default').append(
				$('<div/>').addClass('panel-heading').text('#' + (++requestCount) + ' - ' + form.attr('action')),
				$('<div/>').addClass('panel-body').append(
					$('<pre/>').text(JSON.stringify(outputData, null, 4)),
					$('<pre/>').text(textStatus)
				)
			));
		});
	});

	var url = decodeURIComponent(window.location);

	if ($.inArray('submit', url.split('/')) > 0){
		apiForm.submit();
	}
});
