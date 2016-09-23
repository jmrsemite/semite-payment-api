// JS File used in events
$(document).ready(function() {
	var settings = {
		customButtons: {},
		header: {
			left: 'prev,next today',
			center: 'title',
			right: 'month,agendaWeek,agendaDay,viewFullCalendar'
		},
		loading: function( isLoading, view ) {
            if(!isLoading) {// isLoading gives boolean value
              $('.dt-loader').addClass('hide');
            } else {
            	$('.dt-loader').removeClass('hide');
            }
        },
		eventColor: '#28B8DA',
		editable: true,
		eventLimit: 3,
		eventSources: [
		{
		url: admin_url + 'utilities/get_calendar_data',
			type: 'GET',
			data: {test:'test'},
			error: function() {
				alert('There was an error while fetching calendar data!');
			}
		},
		],
		eventRender: function(event, element) {
			element.attr('title', event._tooltip);
			element.attr('data-toggle', 'tooltip');
			// Only add remove icon on events
			if(!event.url){
				// is not event creator or admin
				if(!event.is_not_creator){
					element.append('<span class="closeon"><i class="fa fa-remove"></i></span>');
					element.find(".closeon").click(function() {
						$.get(admin_url + 'utilities/delete_event/' + event.eventid, function(response) {
							if (response.success == true) {
								$('#calendar').fullCalendar('removeEvents', event._id);
								alert_float('success', response.message);
							}
						}, 'json');

					});
				}
			}
		},
		dayClick: function(date, jsEvent, view) {
			$('#newEventModal').modal('show');
			date = date.toDate();
			$("input[name='start'].datepicker").datepicker("update", date);
			return false;
		}
	}
	if($('body').hasClass('home')){
		settings.customButtons.viewFullCalendar = {
			text: 'expand',
			click: function() {
				window.location.href = admin_url + 'utilities/calendar';
			}
		}
	}

	if(google_api != ''){
		settings.googleCalendarApiKey = google_api;
	}

	if(calendarIDs != ''){
	calendarIDs = $.parseJSON(calendarIDs);
		if (calendarIDs.length != 0) {
			if(google_api != ''){
				for (var i=0;i < calendarIDs.length;i++){
					var _gcal = {};
					_gcal.googleCalendarId = calendarIDs[i];
					settings.eventSources.push(_gcal);
				}
			} else {
				alert('You have setup Google Calendar IDS but you dont have specified Google API key');
			}
		}
	}

	// Init calendar
	$('#calendar').fullCalendar(settings);
	// New event modal
	$('#newEventModal form').submit(function() {
		if($(this).find('input[name="start"]').val() == ''){
			alert('Start date cannot be empty');
			return false;
		}
			$.post(this.action, $(this).serialize(), function(response) {
				response = $.parseJSON(response);
				if (response.success == true) {
					alert_float('success', response.message);
					$('#newEventModal').modal('hide');
					$('#calendar').fullCalendar('refetchEvents');
					$('#newEventModal input').val('');
				}
			});
			return false;
		});
	});
