$(function(){
	var requestCount = 0, apiForm = $('.container form').submit(function(e){
		var form = $(this),
			data = $('form :input').filter(function(index, element){
				return $.trim($(element).val()) !== '';
			});

		e.preventDefault();
		$.ajax({
			url: form.attr('action'),
			method: form.attr('method'),
			data: data.serialize()
		}).done(function(response){
			form.after($('<div/>').addClass('panel panel-default').append(
				$('<div/>').addClass('panel-heading').text('#' + (++requestCount) + ' - ' + form.attr('action')),
				$('<div/>').addClass('panel-body').append(
					$('<pre/>').text(JSON.stringify(data.serializeObject(), null, 4)),
					$('<pre/>').text(JSON.stringify(response, null, 4))
				)
			));
		}).fail(function(jqXHR, textStatus){
			form.after($('<div/>').addClass('panel panel-default').append(
				$('<div/>').addClass('panel-heading').text('#' + (++requestCount) + ' - ' + form.attr('action')),
				$('<div/>').addClass('panel-body').append(
					$('<pre/>').text(JSON.stringify(data.serializeObject(), null, 4)),
					$('<pre/>').text(textStatus)
				)
			));
		});
	});

	var url = decodeURIComponent(window.location);

	if ($.inArray('submit', url.split('/'))){
		apiForm.submit();
	}

	$.fn.serializeObject = function(){
		var o = {};
		var a = this.serializeArray();
		$.each(a, function(){
			if (o[this.name] !== undefined){
				if (!o[this.name].push) {
					o[this.name] = [o[this.name]];
				}
				o[this.name].push(this.value || '');
			} else {
				o[this.name] = this.value || '';
			}
		});
		return o;
	};
});
