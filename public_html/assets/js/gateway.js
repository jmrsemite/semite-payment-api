/**
 * Created by sentir on 8/4/16.
 */
var salesChart;
var report_from = $('input[name="report-from"]');
var report_to = $('input[name="report-to"]');
var date_range = $('#date-range');

$(document).ready(function() {

    transaction_home_chart();

    $('select[name="currency"]').on('change', function() {
        transaction_home_chart();
    });

    report_from.on('change', function() {
        var val = $(this).val();
        var report_to_val = report_to.val();
        if (val != '') {
            report_to.attr('disabled', false);
            if (report_to_val != '') {
                transaction_home_chart();
            }
        } else {
            report_to.attr('disabled', true);
        }
    });

    report_to.on('change', function() {
        var val = $(this).val();
        if (val != '') {
            transaction_home_chart();
        }
    });

    $('select[name="months-report"]').on('change', function() {
        var val = $(this).val();
        report_to.attr('disabled', true);
        report_to.val('');
        report_from.val('');
        if (val == 'custom') {
            date_range.addClass('fadeIn').removeClass('hide');
            return;
        } else {
            if (!date_range.hasClass('hide')) {
                date_range.removeClass('fadeIn').addClass('hide');
            }
        }
        transaction_home_chart();
    });
});


function transaction_home_chart() {

    // Check if chart canvas exists.
    var chart = $('#transaction-home-chart');
    if(chart.length == 0){
        return;
    }

    if (typeof(salesChart) !== 'undefined') {
        salesChart.destroy();
    }

    var data = {};
    data.months_report = $('select[name="months-report"]').val();
    data.report_from = report_from.val();
    data.report_to = report_to.val();

    var currency = $('#currency');
    if (currency.length > 0) {
        data.report_currency = $('select[name="currency"]').val();
    }

    $.post(admin_url + 'gateway/transaction_home_chart', data).success(function(response) {
        response = $.parseJSON(response);
        var ctx = chart.get(0).getContext('2d');
        salesChart = new Chart(ctx).Line(response, {
            responsive: true,
            multiTooltipTemplate: "<%= datasetLabel %> - <%= value %>"
        });
    });
}
// Generate random api access
function generateAccess(element){

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