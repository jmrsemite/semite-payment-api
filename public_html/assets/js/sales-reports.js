	var salesChart;
	var groupsChart;
	var customersTable;
	var report_from = $('input[name="report-from"]');
	var report_to = $('input[name="report-to"]');
	var report_customers = $('#customers-report');
	var report_customers_groups = $('#customers-group');
	var date_range = $('#date-range');
	var fnServerParams = {
	    "report_months": '[name="months-report"]',
	    "report_from": '[name="report-from"]',
	    "report_to": '[name="report-to"]',
	    "report_currency": '[name="currency"]',
	}
	$(document).ready(function() {

	    $('select[name="currency"]').on('change', function() {
	        gen_reports();
	    });

	    report_from.on('change', function() {
	        var val = $(this).val();
	        var report_to_val = report_to.val();
	        if (val != '') {
	            report_to.attr('disabled', false);
	            if (report_to_val != '') {
	                gen_reports();
	            }
	        } else {
	            report_to.attr('disabled', true);
	        }
	    });

	    report_to.on('change', function() {
	        var val = $(this).val();
	        if (val != '') {
	            gen_reports();
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
	        gen_reports();
	    });
	    // Main report group change and reinit data
	    $('select[name="report-group-change"]').on('change', function() {
	        var report_wrapper = $('#report');
	        if (report_wrapper.hasClass('hide')) {
	            report_wrapper.removeClass('hide');
	        }
	        $('#customers-group-gen').addClass('hide');
	        report_customers_groups.addClass('hide');
	        report_customers.addClass('hide');
	        $('#chart').addClass('hide');
	        // Clear custom date picker
	        report_to.val('');
	        report_from.val('');
	        $('#currency').removeClass('hide');
	        var report = $(this).val();
	        if (report == '') {
	        	report_wrapper.addClass('hide');
	            $('#report-time').addClass('hide');
	            return;
	        } else {
	            $('#report-time').removeClass('hide');
	        }
	        if (report == 'total-income') {
	            $('#chart').removeClass('hide');
	        } else if (report == 'customers-report') {

	            report_customers.removeClass('hide');
	        } else if(report == 'customers-group'){
	        	$('#customers-group-gen').removeClass('hide');
	        }
	        gen_reports();
	    });
	});

	// Generate total income bar
	function total_income_bar_report() {
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
	    $.post(admin_url + 'reports/total_income_report', data).success(function(response) {
	        response = $.parseJSON(response);
	        var ctx = $('#chart').get(0).getContext('2d');
	        salesChart = new Chart(ctx).Bar(response, {
	            responsive: true
	        });
	    });
	}

	// Generate customers report
	function customers_report() {
	    if ($.fn.DataTable.isDataTable('.table-customers-report')) {
	        $('.table-customers-report').DataTable().destroy();
	    }

	    initDataTable('.table-customers-report', admin_url + 'reports/customers_report', 'customers', false, false, fnServerParams);
	}
	function report_by_customer_groups(){
		if (typeof(groupsChart) !== 'undefined') {
			groupsChart.destroy();
		}
		var data = {};
		data.months_report = $('select[name="months-report"]').val();
		data.report_from = report_from.val();
		data.report_to = report_to.val();

		var currency = $('#currency');
		if (currency.length > 0) {
			data.report_currency = $('select[name="currency"]').val();
		}
		$.post(admin_url + 'reports/report_by_customer_groups', data).success(function(response) {
			response = $.parseJSON(response);
			var ctx = $('#customers-group-gen').get(0).getContext('2d');
			groupsChart = new Chart(ctx).Line(response, line_chart_options);
		});
	}
	// Main generate report function
	function gen_reports() {

	    if (!$('#chart').hasClass('hide')) {
	        total_income_bar_report();
	    } else if (!report_customers.hasClass('hide')) {
	        customers_report();
	    } else if(!$('#customers-group-gen').hasClass('hide')){
	    	report_by_customer_groups();
	    }
	}
