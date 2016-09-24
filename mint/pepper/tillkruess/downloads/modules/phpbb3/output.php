<?php

/******************************************************************************
 Pepper

 Developer: Till Krüss
 Plug-in Name: Downloads

 More info at: http://pepper.pralinenschachtel.de/

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

	class TK_Downloads_Output_phpbb3 {

		function TK_Downloads_Output_phpbb3() {

			require dirname(__FILE__).'/config.php';

			if ($dbms == 'mysql') {
				$this->db_prefix = $table_prefix;
				$this->db_id = mysql_connect($dbhost, $dbuser, $dbpasswd);
				mysql_select_db($dbname, $this->db_id);
			}

		}

		function get_path($path, $domains) {
			return '(phpBB3 file)';
		}

		function get_filename($id, $abbr = NULL) {

			$result = mysql_query('SELECT real_filename FROM '.$this->db_prefix.'attachments WHERE attach_id = '.$id, $this->db_id);
			$row = mysql_fetch_assoc($result);

			if (is_null($abbr) && strlen($row['real_filename']) > 28) {
				return '<abbr title="'.$this->cut_abbr($row['real_filename']).'">'.substr($row['real_filename'], 0, 28).'&#8230;</abbr>';
			} else {
				return $row['real_filename'];
			}

		}

		function get_fullname($id) {

			$filename = $this->get_filename($id, TRUE);

			if (strlen($filename) > 30) {
				$filename = '&#8230;'.substr($filename, -30);
			}

			return '(phpBB3 file) '.$filename;

		}

		function get_link($filename, $id) {

			return $filename;

		}

		function cut_abbr($string) {

			if (strlen($string) > 75) {
				$string = '&#8230;'.substr($string, -75);
			}

			return $string;

		}

	}
