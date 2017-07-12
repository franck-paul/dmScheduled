dotclear.dmScheduledCheck = function() {
	var params = {
		f: 'dmScheduledCheck',
		xd_check: dotclear.nonce
	};
	$.get('services.php',params,function(data) {
		if ($('rsp[status=failed]',data).length > 0) {
			// For debugging purpose only:
			// console.log($('rsp',data).attr('message'));
			console.log('Dotclear REST server error');
		} else {
			// Refresh list of last scheduled posts
			var args = {
				f: 'dmLastScheduledRows',
				xd_check: dotclear.nonce
			};
			$.get('services.php',args,function(data) {
				if ($('rsp[status=failed]',data).length > 0) {
					// For debugging purpose only:
					// console.log($('rsp',data).attr('message'));
					console.log('Dotclear REST server error');
				} else {
					xml = $('rsp>rows',data).attr('list');
					// Replace current list with the new one
					if ($('#scheduled-posts span.badge').length) {
						$('#scheduled-posts span.badge').remove();
					}
					if ($('#scheduled-posts ul').length) {
						$('#scheduled-posts ul').remove();
					}
					if ($('#scheduled-posts p').length) {
						$('#scheduled-posts p').remove();
					}
					// Add current hour in badge on module
					var now = new Date();
					var time = now.toLocaleTimeString();
					xml = '<span class="badge badge-block badge-info">'+time+'</span>'+xml;
					// Display module content
					$('#scheduled-posts h3').after(xml);
				}
			});
		}
	});
}

$(function() {
	if (dotclear.dmScheduled_Monitor) {
		$('#scheduled-posts').addClass('badgeable');
		// Auto refresh requested : Set 5 minutes interval between two checks for publishing scheduled entries
		dotclear.dmScheduled_Timer = setInterval(dotclear.dmScheduledCheck,60*5*1000);
	}
});
