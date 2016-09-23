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
// Generate random api access
function generateAccess(element) {

    var url = $(element).attr('data-link');

    setTimeout(function () {
        $.ajax({
            type: 'POST',
            url: url,
            dataType: 'json',
            success: function (json) {

                $('input[name=\'api_id\']').val(json.api_id);
                $('input[name=\'secret_key\']').val(json.secret_key);

            },
            error: function (xhr, ajaxOptions, thrownError) {
                if (xhr.status != 0) {
                    alert(xhr.status + "\r\n" +thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            }
        });
    }, 500);
}

$(document).ready(function(){
    $('.payment').on('click',function(e){
        e.preventDefault();

        $('form#virtual-terminal').submit();

    });

    $('#copy_billing_information').on('change',function(e){
        e.preventDefault();

        if ($(this).is(':checked')){

            $('input[name=\'shipping[customer_name]\']').val($('input[name=\'customer[customer_name]\']').val());
            $('select[name=\'shipping[country_code]\']').val($('select[name=\'customer[country_code]\']').val());
            $('input[name=\'shipping[address_line_1]\']').val($('input[name=\'customer[address_line_1]\']').val());
            $('input[name=\'shipping[address_line_2]\']').val($('input[name=\'customer[address_line_2]\']').val());
            $('input[name=\'shipping[city]\']').val($('input[name=\'customer[city]\']').val());
            $('input[name=\'shipping[state]\']').val($('input[name=\'customer[state]\']').val());
            $('input[name=\'shipping[zip]\']').val($('input[name=\'customer[zip]\']').val());
        } else {
            $('input[name=\'shipping[customer_name]\']').val('');
            $('select[name=\'shipping[country_code]\']').val('');
            $('input[name=\'shipping[address_line_1]\']').val('');
            $('input[name=\'shipping[address_line_2]\']').val('');
            $('input[name=\'shipping[city]\']').val('');
            $('input[name=\'shipping[state]\']').val('');
            $('input[name=\'shipping[zip]\']').val('');
        }
    });

    $('.money').mask("###0.00", {reverse: true});
    $('.cc-card').mask('0999-0999-0999-0999');
    $('.cc-expiry').mask('00/0000');
});