$.tablesorter.addParser({
	id: "bigcurrency",
	is: function (s) {
		return /^[£$€?.]/.test(s);
	}, format: function (s) {
		return 1000000 * $.tablesorter.formatFloat(s.replace(/[ £$€,\.]/g, ""));
	}, type: "numeric"
});
