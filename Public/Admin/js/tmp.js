$(document).ready(function() {
	$("#external-events div.external-event").each(function() {
		var d = {
			title: $.trim($(this).text())
		};
		$(this).data("eventObject", d);
		$(this).draggable({
			zIndex: 999,
			revert: true,
			revertDuration: 0
		})
	});
	var b = new Date();
	var c = b.getDate();
	var a = b.getMonth();
	var e = b.getFullYear();
	$("#calendar").fullCalendar({
		header: {
			left: "prev,next",
			center: "title",
			right: "month,agendaWeek,agendaDay"
		},
		editable: true,
		droppable: true,
		drop: function(g, h) {
			var f = $(this).data("eventObject");
			var d = $.extend({}, f);
			d.start = g;
			d.allDay = h;
			$("#calendar").fullCalendar("renderEvent", d, true);
			if ($("#drop-remove").is(":checked")) {
				$(this).remove()
			}
		},
		dayClick: function(date, jsEvent, view) {
	        location.href="/Admin/Demo/meetingAdd";
	    },
		events: [{
			title: "组织部",
			start: new Date(e, a, 1)
		}, {
			title: "组织部",
			start: new Date(e, a, c - 5),
			end: new Date(e, a, c - 4),
		}, {
			id: 999,
			title: "组织部",
			start: new Date(e, a, c - 3, 16, 0),
			allDay: true,
		}, {
			id: 999,
			title: "组织部",
			start: new Date(e, a, c + 4, 16, 0),
			allDay: false
		}, {
			title: "人事部",
			start: new Date(e, a, c, 10, 30),
			allDay: false
		}, {
			title: "人事部",
			start: new Date(e, a, c, 12, 30),
			end: new Date(e, a, c, 14, 0),
			allDay: false
		}, {
			title: "人事部",
			start: new Date(e, a, c + 1, 19, 00),
			end: new Date(e, a, c + 1, 22, 30),
			allDay: false
		}, {
			title: "人事部",
			start: new Date(e, a, 28),
			end: new Date(e, a, 29),
			//url: "http://baidu.com/"
		}],
		eventRender: function(event, element) {
	        
	    }
	})
});