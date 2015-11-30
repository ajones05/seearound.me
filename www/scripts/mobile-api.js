$(function(){
	var requestCount = 0;
	$('form').submit(function(e){
		var form = $(this);
		e.preventDefault();
		$.ajax({
			url: form.attr('action'),
			method: form.attr('method'),
			data: form.serialize(),
			beforeSend: function(){
			}
		}).done(function(response){
			form.after($('<div/>').addClass('panel panel-default').append(
				$('<div/>').addClass('panel-heading').text('#' + (++requestCount) + ' - ' + form.attr('action')),
				$('<div/>').addClass('panel-body').append(
					$('<pre/>').text(JSON.stringify(form.serializeObject())),
					$('<pre/>').text(JSON.stringify(response))
				)
			));
		}).fail(function(jqXHR, textStatus){
			form.after($('<div/>').addClass('panel panel-default').append(
				$('<div/>').addClass('panel-heading').text('#' + (++requestCount) + ' - ' + form.attr('action')),
				$('<div/>').addClass('panel-body').append(
					$('<pre/>').text(JSON.stringify(form.serializeObject())),
					$('<pre/>').text(textStatus)
				)
			));
		});
	});
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
