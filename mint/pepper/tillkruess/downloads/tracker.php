<?php

/******************************************************************************
 Pepper

 Developer: Till Krüss
 Plug-in Name: Downloads

 More info at: http://tillkruess.com/projects/pepper/downloads/

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.

 ******************************************************************************/

	if (isset($_GET['type']) && !empty($_GET['type'])) {

		$module = preg_replace('/[^a-z0-9_-]/i', '', $_GET['type']);
		$tracker = dirname(__FILE__).'/modules/'.$module.'/tracker.php';

		if (file_exists($tracker)) {
			require $tracker;
		} else {
			exit('// Nonexistent download module: "'.$module.'"');
		}

	} elseif (isset($_GET['url']) && !empty($_GET['url'])) {

		require dirname(__FILE__).'/modules/http/tracker.php';

	} else {
		exit('// Invalid tracking request');
	}

?>