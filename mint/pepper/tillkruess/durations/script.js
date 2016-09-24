
if (!Mint.TK) {
	Mint.TK = new Object();
}
Mint.TK.Durations = {
	now : new Date(),
	recalc : function() {
		var then = new Date();
		var img = new Image();
		var path = '<?php print $this->Mint->cfg['installFull']; ?>/pepper/tillkruess/durations/recalc.php?token=<?php print $token; ?>&time=';
		path += Math.round((then.getTime() - Mint.TK.Durations.now.getTime()) / 1000);
		img.src = path.replace(/^https?:/, window.location.protocol);
	},
	onsave : function() {
		return '&token=<?php print $token; ?>';
	}
};<?php if ($this->prefs['interval']) { ?>
window.setInterval('Mint.TK.Durations.recalc()', <?php print $this->prefs['interval']; ?> * 1000);
if (window.addEventListener) {
	window.addEventListener('unload', Mint.TK.Durations.recalc, false);
} else if (window.attachEvent) {
	window['eunload' + Mint.TK.Durations.recalc] = Mint.TK.Durations.recalc;
	window['unload' + Mint.TK.Durations.recalc] = function() {
		window['eunload' + Mint.TK.Durations.recalc](window.event);
	}
	window.attachEvent('onunload', window['unload' + Mint.TK.Durations.recalc]);
}
<?php } ?>