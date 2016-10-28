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
		}
	});
}

$(function() {
	if (dotclear.dmScheduled_Monitor) {
		// Auto refresh requested : Set 5 minutes interval between two checks for publishing scheduled entries
		dotclear.dmScheduled_Timer = setInterval(dotclear.dmScheduledCheck,60*5*1000);
	}
});
