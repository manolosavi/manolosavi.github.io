
var Mint_TK_script = '<?php print $this->Mint->cfg['installFull']; ?>/pepper/tillkruess/downloads/tracker.php?url=';
var Mint_TK_domains = '<?php print trim($this->Mint->cfg['siteDomains']); ?>';
var Mint_TK_extensions = '<?php print trim($this->prefs['extensions']); ?>';

function Mint_TK_redirect_links() {
	extensions = Mint_TK_extensions.split(', ');
	domains = Mint_TK_domains.split(', ');
	var e = document.getElementsByTagName('a');
	for (var i = 0; i < e.length; i++) {
		if (e[i].href.indexOf(Mint_TK_script) == -1) {
			for (var j = 0; j < extensions.length; j++) {
				if (extensions[j] == e[i].href.substring(e[i].href.length - extensions[j].length, e[i].href.length)) {
					for (var h = 0; h < domains.length; h++) {
						if ((e[i].href.substr(e[i].href.indexOf('//') + 2)).substr(0, (e[i].href.substr(e[i].href.indexOf('//') + 2)).indexOf('/')).indexOf(domains[h]) != -1) {
							e[i].href = Mint_TK_script + escape(e[i].href);
							break;
						}
					}
				}
			}
		}
	}
}

if (window.addEventListener) {
	window.addEventListener('load', Mint_TK_redirect_links, false);
} else if (window.attachEvent) {
	window['eload' + Mint_TK_redirect_links] = Mint_TK_redirect_links;
	window['load' + Mint_TK_redirect_links] = function() {
		window['eload' + Mint_TK_redirect_links](window.event);
	}
	window.attachEvent('onload', window['load' + Mint_TK_redirect_links]);
}
