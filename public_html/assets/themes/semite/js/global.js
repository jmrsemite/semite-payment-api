$(document).ready(function(){
	init_progress_bars();
	init_datepicker();
	$('.article_useful_buttons button').on('click',function(e){
		e.preventDefault();
		var data = {};
		data.answer = $(this).data('answer');
		data.articleid = $('input[name="articleid"]').val();
		$.post(window.location.href,data).success(function(response){
			response = $.parseJSON(response);
			if(response.success == true){
				$(this).focusout();
			}
			$('.answer_response').html(response.message);
		});
	});
});
function init_progress_bars() {
	setTimeout(function() {
		$('.progress .progress-bar').each(function() {
			var bar = $(this);
			var perc = bar.attr("data-percent");
			var current_perc = 0;
			var progress = setInterval(function() {
				if (current_perc >= perc) {
					clearInterval(progress);
				} else {
					current_perc += 1;
					bar.css('width', (current_perc) + '%');
				}
				bar.text((current_perc) + '%');
			}, 10);
		});

	}, 300);
}
function init_datepicker() {
	$('.datepicker').datepicker({
		autoclose: true,
		format: date_format
	});
	$('.calendar-icon').on('click', function() {
		$(this).parents('.date').find('.datepicker').datepicker('show');
	});
}
// Datatables sprintf language help function
if (!String.prototype.format) {
  String.prototype.format = function() {
    var args = arguments;
    return this.replace(/{(\d+)}/g, function(match, number) {
      return typeof args[number] != 'undefined'
        ? args[number]
        : match
      ;
    });
  };
}
