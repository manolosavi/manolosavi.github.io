<?php

/******************************************************************************
 Pepper

 Developer: Till Krüss
 Plug-in Name: Durations

 More info at: http://pepper.pralinenschachtel.de/

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.

 ******************************************************************************/

	define('MINT', TRUE);

	class Mint {

		function Mint($args) {
			$this->prefix = $args['tblPrefix'];
			$this->id = mysql_connect($args['server'], $args['username'], $args['password']);
			mysql_select_db($args['database'], $this->id);
		}

		function recalc() {
			if (isset($_GET['token'], $_GET['time']) && is_numeric($_GET['token']) && is_numeric($_GET['time']) && $_GET['time'] < 27000 && $_GET['time'] > 0) {
				mysql_query('UPDATE '.$this->prefix.'visit SET duration_time = \''.$_GET['time'].'\' WHERE duration_token = \''.$_GET['token'].'\' ORDER BY dt DESC LIMIT 1', $this->id);
			}
			mysql_close($this->id);
		}

	}

	$paths = array('pepper/tillkruess/durations/recalc.php', 'pepper\tillkruess\durations\recalc.php', 'pepper\\\tillkruess\\\durations\\\recalc.php');
	$file = str_replace($paths, '', __FILE__);

	require $file.'config/db.php';

	$Mint->recalc();

?>