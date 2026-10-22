/* Minimal replacement for jstz: jstz.determine().name() returns the IANA time zone name, or '' if unavailable */
var jstz = {
	determine: function () {
		var tzname = '';
		try {
			tzname = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
		} catch (e) {}
		return {
			name: function () {
				return tzname;
			}
		};
	}
};
