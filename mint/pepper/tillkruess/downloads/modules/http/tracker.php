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

	error_reporting(E_ALL);

	define('MINT', TRUE);

	class Mint {

		function Mint($args) {

			$this->paranoid = isset($args['paranoid']) ? $args['paranoid'] : FALSE;
			$this->db_prefix = $args['tblPrefix'];
			$this->db_id = mysql_connect($args['server'], $args['username'], $args['password']);

			mysql_select_db($args['database'], $this->db_id);

			$result = mysql_query('SELECT cfg, data FROM '.$this->db_prefix.'_config LIMIT 0,1', $this->db_id);
			$row = mysql_fetch_assoc($result);

			$this->pepper_cfg = $this->safe_unserialize($row['cfg']);
			$this->pepper_id = $this->pepper_cfg['pepperLookUp']['TK_Downloads'];
			$this->pepper_prefs = $this->pepper_cfg['preferences']['pepper'][$this->pepper_id];

			$this->loggedin = (isset($_COOKIE['MintAuth']) && $_COOKIE['MintAuth'] == md5($this->pepper_cfg['password'])) ? TRUE : FALSE;
			$this->debug = isset($_GET['debug']) ? TRUE : FALSE;

		}

		function safe_unserialize($serialized) {

			$serialized = stripslashes($serialized);
			$unserialized = unserialize($serialized);

			if ($unserialized === FALSE) {
				$serialized	= preg_replace('/s:\d+:"([^"]*)";/e', "'s:'.strlen('\\1').':\"\\1\";'", $serialized);
				$unserialized = unserialize(stripslashes($serialized));
			}

			return $unserialized;

		}

		function prune_array($array, $length = -1) {

			if ($length != -1 && count($array) <= $length) {
				return $array;
			}

			if ($length == -1) {
				$length = count($array) - 1;
			}

			ksort($array);
			reset($array);

			$n = count($array) - $length;

			foreach($array as $key => $value) {
				if ($n > 0) {
					unset($array[$key]); 
					$n--;
				} else {
					break;
				}
			}

			return $array;

		}

		function should_ignore() {

			$ignore = FALSE;

			if (isset($_COOKIE['MintIgnore']) && $_COOKIE['MintIgnore'] == 'true') {
				return TRUE;
			}

			$iplong = $this->get_ip();

			foreach($this->pepper_cfg['preferences']['ignoredIPsLong'] as $ignored) {
				if ((is_array($ignored) && $iplong >= $ignored[0] && $iplong <= $ignored[1]) || $iplong == $ignored) {
					$ignore = TRUE;
					break;
				}
			}

			return $ignore;

		}

		function get_checksum($string) {

			$crc = crc32($string);

			if ($crc & 0x80000000) {
				$crc ^= 0xffffffff;
				$crc += 1;
				$crc = -$crc;
			}

    		return $crc;

		}

		function get_ip() {

			$ip = $_SERVER['REMOTE_ADDR'];

			if (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && !empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
				$ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
			}

			if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}

			return ip2long($ip);

		}

		function get_time($when = '') {

			$time = time();

			switch($when) {

				case 'today':
					$time = $this->make_gmt(0, 0, 0);
					break;

				case 'week':
					$time = $this->make_gmt(0, 0, 0, $this->get_date('n'), $this->get_date('j') - $this->get_date('w'), $this->get_date('Y'));
					break;

				case 'month':
					$time = $this->make_gmt(0, 0, 0, $this->get_date('n'), 1);
					break;
			}

			return $time;

		}

		function get_date($format) {

			$format	= preg_replace('/(?!:[^\\\]?)U/', time(), $format);
			$time = time() + $this->pepper_cfg['offset'] * 3600;

			return gmdate($format, $time);

		}

		function make_gmt($hours = FALSE, $minutes = FALSE, $seconds = FALSE, $month = FALSE, $day = FALSE, $year = FALSE) {

			list($now_day, $now_month, $now_year, $now_hours, $now_minutes, $now_seconds) = explode(' ', $this->get_date('d m Y H i s'));

			$hours = $hours !== FALSE ? $hours : $now_hours;
			$minutes = $minutes !== FALSE ? $minutes : $now_minutes;
			$seconds = $seconds !== FALSE ? $seconds : $now_seconds;
			$month = $month !== FALSE ? $month : $now_month;
			$day = $day	!== FALSE ? $day : $now_day;
			$year = $year !== FALSE ? $year : $now_year;

			return gmmktime($hours - $this->pepper_cfg['offset'], $minutes, $seconds, $month, $day, $year);

		}

		function track_request() {

			$track = FALSE;
			$record = FALSE;
			$redirect = FALSE;
			$url = preg_replace("/^http(s)?:\/\/www\.([^.]+\.)/i", "http$1://$2", mysql_real_escape_string(rawurldecode($_GET['url']), $this->db_id));
			$components = parse_url($url);
			$file = $_SERVER['DOCUMENT_ROOT'].$components['path'];

			$this->debug($this->pepper_prefs['extensions'], 'extensions');
			foreach (explode(', ', $this->pepper_prefs['extensions']) as $extension) {
				if (substr($file, -strlen($extension)) == $extension) {
					$track = TRUE;
					break;
				}
			}

			$this->debug($track, 'valid extension');
			$this->debug(isset($_GET['remote']), 'remote');
			$this->debug($this->pepper_prefs['remote'], 'remote enabled');

			if (isset($_GET['remote']) && $this->pepper_prefs['remote'] && $track) {

				$port = array_key_exists('port', $components) ? $components['port'] : 80;
				$socket = fsockopen($components['host'], $port, $errno, $errstr, 20);
				$this->debug($socket, 'socket');

				if ($socket) {

					fwrite($socket, 'HEAD '.$components['path']." HTTP/1.0\r\nHost: ".$components['host']."\r\n\r\n");
					$buffer = fread($socket, 1025);

					if (strpos($buffer, '200 OK') !== FALSE) {
						preg_match('/content-length:\\s?(\\d+)/i', $buffer, $matches);
						$record = isset($matches[1]) ? intval($matches[1]) : 0;
					}

				}

				$this->debug($record, 'filesize');
				$redirect = TRUE;

			} elseif ($track) {

				require dirname(__FILE__).'/config.php';

				if (file_exists($file) && is_file($file)) {

					$record = filesize($file);
					$redirect = isset($_GET['force']) || isset($_GET['inline']) ? FALSE : TRUE;

				} elseif (is_array($TK_HTTP_PATHS)) {
					foreach ($TK_HTTP_PATHS as $path) {
						$filepath = $path.'/'.basename($components['path']);
						if (file_exists($filepath) && is_file($filepath)) {
							$record = filesize($filepath);
							$redirect = isset($_GET['force']) || isset($_GET['inline']) ? FALSE : TRUE;
							break;
						}
					}

				}

				if ($record) {
					$this->debug($record, 'filesize');
					$this->debug(isset($_GET['inline']), 'inline');
					$this->debug(isset($_GET['force']), 'force');
				} else {
					$track = FALSE;
					$this->debug(TRUE, 'invalid local file');
				}

			}

			$this->debug($this->should_ignore(), 'mint ignore');
			if ($record && !$this->should_ignore()) {

				$checksize = $this->pepper_prefs['checksize'] ? ' AND size = '.$record : '';
				$result = mysql_query('SELECT id FROM '.$this->db_prefix."files WHERE file = '".$url."'".$checksize, $this->db_id);

				$referer = empty($_SERVER['HTTP_REFERER']) || substr($_SERVER['HTTP_REFERER'], 0, 4) != 'http' ? '' : preg_replace('/#.*$/', '', preg_replace("/^http(s)?:\/\/www\.([^.]+\.)/i", "http$1://$2", mysql_real_escape_string($_SERVER['HTTP_REFERER'], $this->db_id)));
				$checksum = $this->get_checksum(preg_replace('/(^([^:]+):\/\/(www\.)?|(:\d+)?\/.*$)/', '', $referer));
				$session = isset($_COOKIE['MintCrush']) ? mysql_real_escape_string($_COOKIE['MintCrush'], $this->db_id) : 0;

				if (mysql_num_rows($result)) {
					$row = mysql_fetch_assoc($result);
					$id = $row['id'];
					mysql_query('UPDATE '.$this->db_prefix.'files SET hits = hits + 1 WHERE id = '.$id, $this->db_id);
				} else {
					mysql_query('INSERT INTO '.$this->db_prefix."files (id, file, type, size, hits) VALUES ('', '".$url."', 'http', ".$record.", 1)", $this->db_id);
					$id = mysql_insert_id($this->db_id);
				}

				mysql_query('INSERT INTO '.$this->db_prefix."downloads (id, file, dt, ip, session, referer, checksum) VALUES ('', '".$id."', ".time().", ".$this->get_ip().", ".$session.", '".$referer."', '".$checksum."')", $this->db_id);

				$rawdata = mysql_fetch_assoc(mysql_query('SELECT data FROM '.$this->db_prefix.'_data WHERE id = '.$this->pepper_id, $this->db_id));
				$downloads = $this->safe_unserialize($rawdata['data']);

				$today = $this->get_time('today');
				$week = $this->get_time('week');
				$month = $this->get_time('month');

				$downloads[0][0][$today] = isset($downloads[0][0][$today]) ? $downloads[0][0][$today] + 1 : 1;
				$downloads[0][1][$week] = isset($downloads[0][1][$week]) ? $downloads[0][1][$week] + 1 : 1;
				$downloads[0][2][$month] = isset($downloads[0][2][$month]) ? $downloads[0][2][$month] + 1 : 1;
				$downloads[$id][0][$today] = isset($downloads[$id][0][$today]) ? $downloads[$id][0][$today] + 1 : 1;
				$downloads[$id][1][$week] = isset($downloads[$id][1][$week]) ? $downloads[$id][1][$week] + 1 : 1;
				$downloads[$id][2][$month] = isset($downloads[$id][2][$month]) ? $downloads[$id][2][$month] + 1 : 1;

				$downloads[0][0] = $this->prune_array($downloads[0][0], 7);
				$downloads[0][1] = $this->prune_array($downloads[0][1], 5);
				$downloads[0][2] = $this->prune_array($downloads[0][2], 12);
				$downloads[$id][0] = $this->prune_array($downloads[$id][0], 7);
				$downloads[$id][1] = $this->prune_array($downloads[$id][1], 5);
				$downloads[$id][2] = $this->prune_array($downloads[$id][2], 12);

				mysql_query('UPDATE '.$this->db_prefix."_data SET data = '".addslashes(serialize($downloads))."' WHERE id = ".$this->pepper_id, $this->db_id);

			}

			if ($redirect) {

				if (!$this->debug()) {
					header('Location: '.$url);
					exit();
				}

			} else {

				if (!$track) {
					exit('// You cannot download this file');
				}

				if (extension_loaded('fileinfo')) {
					$fileinfo = new finfo(FILEINFO_MIME);
					$mime = $fileinfo->file($file);
				} elseif (extension_loaded('mime') && file_exists(ini_get('mime_magic.magicfile'))) {
					$mime = mime_content_type($file);
				} elseif (file_exists('/usr/bin/file')) {
					$mime = @shell_exec('/usr/bin/file -bi '.escapeshellarg($file));
				} else {
					$mime = 'x-application/x-octet-stream';
				}
				$this->debug($mime, 'mimetype');

				$disposition = isset($_GET['inline']) ? 'inline' : 'attachment';

				if (!$this->debug()) {
					header('Content-Type: '.trim($mime));
					header('Content-Disposition: '.$disposition.'; filename="'.basename($file).'"');
					header('Content-Transfer-Encoding: binary');
					header('Content-Length: '.filesize($file));
					$fp = fopen($file, 'rb');
					fpassthru($fp);
				}

			}

		}

		function debug($output = NULL, $name = NULL) {

			if ($this->debug) {

				$echo = $this->paranoid ? FALSE : TRUE;
				$echo = $this->loggedin ? TRUE : $echo;

				if ($output === NULL) {
					return $echo;
				} else if ($echo) {
					print '['.$name.'] ';
					var_dump($output);
					print '<br />';
				}

			}

		}

	}

	require str_replace('pepper/tillkruess/downloads/modules/http/tracker.php', '', __FILE__).'config/db.php';

	$Mint->track_request();

?>