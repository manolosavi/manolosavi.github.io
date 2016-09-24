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

	if (!defined('MINT')) {
		header('Location: /');
		exit();
	};

	$installPepper = 'TK_Durations';

	class TK_Durations extends Pepper {

		var $version = 113;

		var $info = array(
			'pepperName' => 'Durations',
			'pepperUrl' => 'http://pepper.pralinenschachtel.de/',
			'pepperDesc' => 'View the duration of time your visitors stay on your site.',
			'developerName' => 'Till Kr&uuml;ss',
			'developerUrl' => 'http://pralinenschachtel.de/'
		);

		var $panes = array(
			'Durations' => array(
				'Frames',
				'Pages',
				'Timeline'
			)
		);

		var $data = array(
			'averages' => array()
		);

		var $prefs = array(
			'timeout' => 30,
			'interval' => 15,
			'threshold' => 5,
			'timeframes' => '30, 60, 120, 300, 600, 900, 1800, 2700, 3600, 7400, 18000'
		);

		var $manifest = array(
			'visit' => array(
				'duration_token' => 'INT(9) NOT NULL',
				'duration_time' => 'INT(10) NOT NULL'
			)
		);

		function update() {

			if ($this->Mint->version < 215) {

				$this->Mint->logError('This version of Durations requires Mint v2.15.', 2);

			} elseif ($this->getInstalledVersion() < 100) {

				$this->Mint->logError('You cannot update Durations from a version prior to v1.00.', 2);

			} else {
				
				if ($this->getInstalledVersion() < 110) {

					if ($this->getInstalledVersion() == 107) {
						$this->data['averages'] = array();
					}

					if ($this->getInstalledVersion() < 108) {
						$this->onInstall();
						$this->query('UPDATE '.$this->Mint->db['tblPrefix'].'visit SET duration_time = 0 WHERE duration_time > 27000 OR duration_time < 0');
					}

					unset($this->data['averages'][2], $this->data['averages'][3]);

				}

				if ($this->getInstalledVersion() < 112) {
					unset($this->prefs['_verified']);
				}

			}

		}

		function isCompatible() {

			if ($this->Mint->version < 215) {
				return array('isCompatible' => FALSE, 'explanation' => '<p>This Pepper requires Mint v2.15.</p>');
			} else {
				return array('isCompatible' => TRUE);
			}

		}

		function onInstall() {

			if (isset($this->Mint->cfg['pepperLookUp']['SI_SecretCrush'])) {
				$SecretCrush =& $this->Mint->getPepperByClassName('SI_SecretCrush');
				$SecretCrush->register($this->pepperId);
			}

		}

		function onUninstall() {

			if (isset($this->Mint->cfg['pepperLookUp']['SI_SecretCrush'])) {
				$SecretCrush =& $this->Mint->getPepperByClassName('SI_SecretCrush');
				$SecretCrush->unregister($this->pepperId);
			}

		}

		function onRecord() {

			$lasthour = $this->Mint->getOffsetTime('hour') - 3600;
			$yesterday = $this->Mint->getOffsetTime('today') - 86400;
			$lastweek = $this->Mint->getOffsetTime('week') - 604800;
			$lastmonthdays = date('t', $this->Mint->getOffsetTime('month') - 604800);
			$lastmonth = $this->Mint->getOffsetTime('month') - 86400 * $lastmonthdays;
			$hours = TRUE; $days = TRUE; $month = TRUE;

			for ($i = 0; $i < 24; $i++) {

				$now = isset($now) ? $now - 3600 : $this->Mint->getOffsetTime('hour');
				if (!isset($this->data['averages'][0][$now-3600])) {
					if ($result = $this->query('SELECT SUM(duration_time) FROM '.$this->Mint->db['tblPrefix'].'visit WHERE duration_time != 0 AND dt < '.$now.' AND dt > '.($now-7200).' GROUP BY duration_token')) {
						if (mysql_num_rows($result)) {
							while ($row = mysql_fetch_row($result)) {
								$total[$i][] = $row[0];
							}
							$this->data['averages'][0][$now-3600] = round(array_sum($total[$i]) / count($total[$i]) / 60);
						} else {
							$this->data['averages'][0][$now-3600] = 0;
						}
					}
				}

			}

			$this->data['averages'][0] = $this->prune_array($this->data['averages'][0], 72);

			if (!isset($this->data['averages'][1][$yesterday])) {

				for ($i = 0; $i < 24; $i++) {

					$hour = $yesterday - ($i * 3600); 

					if (isset($this->data['averages'][0][$hour]) && $hours) {
						$hours += $this->data['averages'][0][$hour];
					} else {
						$hours = FALSE;
						break;
					}

				}

				if ($hours) {
					$this->data['averages'][1][$yesterday] = $hours ? round($hours / 24) : 0;
					$this->data['averages'][1] = $this->prune_array($this->data['averages'][1], 35);
				}

			}

			if (!isset($this->data['averages'][2][$lastweek])) {

				for ($i = 0; $i < 7; $i++) {

					$day = $lastweek - ($i * 86400);

					if (isset($this->data['averages'][1][$day]) && $days) {
						$days += $this->data['averages'][1][$day];
					} else {
						$days = FALSE;
						break;
					}

				}

				if ($days) {
					$this->data['averages'][2][$lastweek] = $days ? round($days / 7) : 0;
					$this->data['averages'][2] = $this->prune_array($this->data['averages'][2], 6);
				}

			}

			if (!isset($this->data['averages'][3][$lastmonth])) {

				for ($i = 0; $i < $lastmonthdays; $i++) {

					$day = $lastmonth + ($i * 86400);

					if (isset($this->data['averages'][1][$day]) && $month) {
						$month += $this->data['averages'][1][$day];
					} else {
						$month = FALSE;
						break;
					}

				}

				if ($month) {
					$this->data['averages'][3][$lastmonth] = $month ? round($month / $lastmonthdays) : 0;
					$this->data['averages'][3] = $this->prune_array($this->data['averages'][3], 12);
				}

			}

			if ($this->Mint->acceptsCookies && isset($_GET['token']) && is_numeric($_GET['token'])) {
				$token = $this->escapeSQL($_GET['token']);
				$this->Mint->bakeCookie('MintDurationsToken', $token, time() + ($this->prefs['timeout'] * 60));
				if ($result = $this->query('SELECT dt FROM '.$this->Mint->db['tblPrefix'].'visit WHERE duration_token = '.$token.' AND dt > '.(time() - ($this->prefs['timeout'] * 60)).' ORDER BY dt DESC LIMIT 0, 1')) {
					if (mysql_num_rows($result)) {
						$row = mysql_fetch_row($result);
						$time = time() - $row[0];
					}
				}
			}

			return array('duration_token' => isset($token) ? $token : 0, 'duration_time' => isset($time) ? $time : 0);

		}

		function onJavaScript() {

			if (isset($_COOKIE['MintDurationsToken']) && is_numeric($_COOKIE['MintDurationsToken'])) {
				$token = $_COOKIE['MintDurationsToken'];
			} else {
				$token = substr(preg_replace('/[^0-9]/', '', sha1(uniqid(rand(), TRUE))), 0, 9);
			}

			include(dirname(__FILE__).'/script.js');

		}

		function onCustom() {

			if (isset($_POST['action'])	&& $_POST['action'] == 'gettimeframes' && isset($_POST['checksum'])) {
				print $this->build_pages_timeframes($this->escapeSQL($_POST['checksum']));
			}

		}

		function onSecretCrushMeta($session) {

			if ($result = $this->query('SELECT SUM(duration_time) as total FROM '.$this->Mint->db['tblPrefix'].'visit WHERE session_checksum = '.$session)) {

				$row = mysql_fetch_assoc($result);

				return array('Duration' => $this->format_time($row['total']));

			}

		}

		function onDisplay($pane, $tab, $column = '', $sort = '') {

			switch ($pane) {

				case 'Durations':
					switch ($tab) {
						case 'Frames':
							return $this->build_frames();
							break;
						case 'Pages':
							return $this->build_pages();
							break;
						case 'Timeline':
							return $this->build_timeline();
							break;
					}
					break;

			}

		}

		function onDisplayPreferences() {

			$timeframes = get_class_vars('TK_Durations');

			$preferences['Sessions'] = <<<HTML
<table class="snug">
	<tr>
		<td>Session ends after</td>
		<td><span class="inline"><input type="text" name="durations_timeout" value="{$this->prefs['timeout']}" class="cinch" /></span></td>
		<td>minutes of inactivity</td>
	</tr>
</table>
<table class="snug">
	<tr>
		<td>Update duration every</td>
		<td><span class="inline"><input type="text" name="durations_interval" value="{$this->prefs['interval']}" class="cinch" /></span></td>
		<td>seconds</td>
	</tr>
</table>
HTML;
			$preferences['Display'] = <<<HTML
<table class="snug">
	<tr>
		<td>Fade timeframes smaller than</td>
		<td><span class="inline"><input type="text" name="durations_threshold" value="{$this->prefs['threshold']}" class="cinch" /></span></td>
		<td>percent</td>
	</tr>
</table>
HTML;

			$preferences['Timeframes'] = <<<HTML
<table class="snug">
	<tr>
		<th>Split off time frames at (in seconds):</th>
	</tr>
	<tr>
		<td><span><textarea name="durations_timeframes" id="durations_timeframes" rows="2" cols="30">{$this->prefs['timeframes']}</textarea></span></td>
	</tr>
	<tr>
		<td><a href="#default" onclick="document.getElementById('durations_timeframes').value = '{$timeframes['prefs']['timeframes']}'; return false;" style="float: left; margin: 0 11px 11px 0;"><img src="pepper/tillkruess/durations/images/btn-default-mini.png" width="51" height="17" alt="Default" /></a> Common timeframes between 30 seconds and 5 hours.</td>
	</tr>
</table>
HTML;

			return $preferences;

		}

		function onSavePreferences() {

			if (isset($_POST['offset']) && $_POST['offset'] != $this->Mint->cfg['offset']) {

				$diff = ($_POST['offset'] - $this->Mint->cfg['offset']) * 60 * 60;

				for ($i = 0; $i < 3; $i++) {

					if (isset($this->data['averages'][$i])) {

						foreach ($this->data['averages'][$i] as $date => $duration) {
							$this->data['averages'][$i][$date - $diff] = $duration;
							unset($this->data['averages'][$i][$date]);
						}

					}

				}

			}

			if (isset($_POST['durations_timeout']) && is_numeric($_POST['durations_timeout'])) {
				$this->prefs['timeout'] = $_POST['durations_timeout'] < 86400 ? round($_POST['durations_timeout'], 0) : 86400;
			}

			if (isset($_POST['durations_interval']) && is_numeric($_POST['durations_interval'])) {
				$this->prefs['interval'] = $_POST['durations_interval'] > 0 ? $_POST['durations_interval'] : 0;
			}

			if (isset($_POST['durations_threshold']) && is_numeric($_POST['durations_threshold'])) {
				$this->prefs['threshold'] = $_POST['durations_threshold'] < 100 ? $_POST['durations_threshold'] : 100;
			}

			if (isset($_POST['durations_timeframes'])) {
				$timeframes = preg_split('/[\s,]+/', $_POST['durations_timeframes']);
				foreach ($timeframes as $key => $frame) {
					if ($frame < 1 || $frame >= 86400) {
						unset($timeframes[$key]);
					}
				}
				$this->prefs['timeframes'] = implode(', ', $timeframes);
			}

		}

		function build_frames() {

			$filters = array('Show all' => 0, 'Past hour' => 1, '2h' => 2, '4h' => 4, '8h' => 8, '24h' => 24, '48h' => 48, '72h' => 72);
			$filter_data = $this->generateFilterList('Frames', $filters, array('Pages'));

			$table_data['thead'] = array(array('value' => '% of Total', 'class' => 'sort'), array('value' => 'Duration', 'class' => 'focus'), array('value' => 'Visitors', 'class' => 'sort'));

			$timeframes = $this->calc_timeframes($this->filter ? " AND dt > ".(time() - ($this->filter * 60 * 60)) : '');

			foreach ($timeframes as $frame => $data) {

				if (($data[0] && $frame) != 0) {

					$percent = $this->Mint->formatPercents($data[0] / $timeframes[0][0] * 100);
					$row = array($percent, $data[1], $data[0]);

					if (round($percent, 5) < $this->prefs['threshold']) {
						$row['class'] = 'insig';
					}

					$table_data['tbody'][] = $row;
				}

			}

			return $filter_data.$this->Mint->generateTable($table_data);

		}

		function build_pages() {

			$filters = array('Show all' => 0, 'Past hour' => 1, '2h' => 2, '4h' => 4, '8h' => 8, '24h' => 24, '48h' => 48, '72h' => 72);
			$filter_data = $this->generateFilterList('Pages', $filters, array('Frames'));

			$table_data['hasFolders'] = TRUE;
			$table_data['thead'] = array(array('value' => 'Total', 'class' => 'sort'), array('value' => 'Avg ', 'class' => 'sort'), array('value' => 'Page/Duration', 'class' => 'focus'));

			$timespan = $this->filter ? " AND dt > ".(time() - ($this->filter * 60 * 60)) : '';

			if ($result = $this->query('SELECT resource, resource_checksum, resource_title, SUM(duration_time) as total, AVG(duration_time) as average FROM '.$this->Mint->db['tblPrefix'].'visit WHERE duration_time != 0'.$timespan.' GROUP BY resource_checksum ORDER BY total DESC LIMIT 0, '.$this->Mint->cfg['preferences']['rows'])) {
				while ($row = mysql_fetch_assoc($result)) {
					$title = empty($row['resource_title']) ? $row['resource'] : $row['resource_title'];
					$table_data['tbody'][] = array($this->format_time($row['total'], TRUE), $this->format_time($row['average'], TRUE), '<a href="'.$row['resource'].'">'.$this->Mint->abbr($title, 38).'</a>', 'folderargs' => array('action' => 'gettimeframes', 'checksum' => $row['resource_checksum']));
				}
			}

			return $filter_data.$this->Mint->generateTable($table_data);

		}

		function build_pages_timeframes($checksum) {

			$table_data['classes'] = array('sort', 'sort', 'focus');

			$timeframes = $this->calc_timeframes(" AND resource_checksum = '".$checksum."'", TRUE);

			foreach ($timeframes as $frame => $data) {

				if (($data[0] && $frame) != 0) {

					$percent = $this->Mint->formatPercents($data[0] / $timeframes[0][0] * 100);
					$row = array($data[0], $data[1], $data[2]);

					if (round($percent, 5) < $this->prefs['threshold']) {
						$row['class'] = 'insig';
					}

					$table_data['tbody'][] = $row;

				}

			}

			return $this->Mint->generateTableRows($table_data);

		}

		function build_timeline() {

			$filters = array('Past Day' => 1, 'Past Week' => 2, 'Past Month' => 3, 'Past Year' => 4);
			$filter_data = $this->generateFilterList('Timeline', $filters);
			$graph_data = array('titles' => array('background' => 'average minutes on site', 'foreground' => ''), 'key' => array('background' => 'Minutes', 'foreground' => ''));
			$high = 0;

			if ($this->filter == 1) {

				for ($i = 0; $i < 24; $i++) {

					$hour = isset($hour) ? $hour - 3600 : $this->Mint->getOffsetTime('hour') - 3600;
					$this->data['averages'][0][$hour] = isset($this->data['averages'][0][$hour]) ? $this->data['averages'][0][$hour] : 0;
					$high = $this->data['averages'][0][$hour] > $high ? $this->data['averages'][0][$hour] : $high;
					$twelve = $this->Mint->offsetDate('G', $hour) == 12;
					$twentyfour = $this->Mint->offsetDate('G', $hour) == 0;
					$label = $this->Mint->offsetDate('g', $hour);

					$graph_data['bars'][] = array(isset($this->data['averages'][0][$hour]) ? round($this->data['averages'][0][$hour]) : 0, 0, $twelve ? 'Noon' : ($twentyfour ? 'Midnight' : (($label == 3 || $label == 6 || $label == 9) ? $label : '')), $this->Mint->formatDateRelative($hour, 'hour'), $twelve || $twentyfour ? 1 : 0);

				}	

			} elseif ($this->filter == 2) {

				for ($i = 0; $i < 7; $i++) {

					$day = isset($day) ? $day - 86400 : $this->Mint->getOffsetTime('today') - 86400;
					$this->data['averages'][1][$day] = isset($this->data['averages'][1][$day]) ? $this->data['averages'][1][$day] : 0;
					$high = $this->data['averages'][1][$day] > $high ? $this->data['averages'][1][$day] : $high;
					$dayofweek = $this->Mint->offsetDate('w', $day);
					$label = substr($this->Mint->offsetDate('D', $day), 0, 2);

					$graph_data['bars'][] = array(isset($this->data['averages'][1][$day]) ? $this->data['averages'][1][$day] : 0, 0, $dayofweek == 0 ? '' : ($dayofweek == 6 ? 'Weekend' : $label), $this->Mint->formatDateRelative($day, 'day'), $dayofweek == 0 || $dayofweek == 6 ? 1 : 0);

				}

			} elseif ($this->filter == 3) {

				for ($i = 0; $i < 5; $i++) {

					$week = isset($week) ? $week - 604800 : $this->Mint->getOffsetTime('week') - 604800;
					$this->data['averages'][2][$week] = isset($this->data['averages'][2][$week]) ? $this->data['averages'][2][$week] : 0;
					$high = $this->data['averages'][2][$week] > $high ? $this->data['averages'][2][$week] : $high;

					$graph_data['bars'][] = array($this->data['averages'][2][$week], 0, $this->Mint->formatDateRelative($week, "week", $i + 1), $this->Mint->offsetDate('D, M j', $week), 0);

				}

			} elseif ($this->filter == 4) {

				$month = $this->Mint->getOffsetTime('month');

				for ($i = 1; $i < 13; $i++) {

					$month = $month - 86400 * date('t', $month - 604800);
					$this->data['averages'][3][$month] = isset($this->data['averages'][3][$month]) ? $this->data['averages'][3][$month] : 0;
					$high = $this->data['averages'][3][$month] > $high ? $this->data['averages'][3][$month] : $high;

					$graph_data['bars'][] = array($this->data['averages'][3][$month], 0, $this->Mint->offsetDate('M', $month), $this->Mint->offsetDate('F', $month), 0);

				}

			}

			$graph_data['bars'] = array_reverse($graph_data['bars']);

			return $filter_data.$this->getHTML_Graph($high, $graph_data);

		}

		function calc_timeframes($clause = '', $average = FALSE) {

			$frames = preg_split('/[\s,]+/', $this->prefs['timeframes']);
			$frames[] = 172800;
			sort($frames);

			$timeframes[0] = 0;
			foreach ($frames as $frame) {
				$timeframes[$frame] = 0;
			}

			if ($result = $this->query('SELECT SUM(duration_time) as duration FROM '.$this->Mint->db['tblPrefix'].'visit WHERE duration_time != 0'.$clause.' GROUP BY duration_token')) {
				while ($row = mysql_fetch_assoc($result)) {
					foreach ($frames as $frame) {
						if ($row['duration'] < $frame) {
							$timeframes[0]++;
							$timeframes[$frame]++;
							break 1;
						}
					}
				}
			}

			$data = $timeframes;
			arsort($data);
			unset($timeframes[0]);

			foreach ($data as $frame => $visitors) {
				$data[$frame] = array($visitors);
			}

			foreach ($timeframes as $frame => $visitors) {

				$lower = NULL;
				$higher = NULL;

				foreach ($timeframes as $_frame => $visitors) {
					if ($frame > $_frame) {
						$lower = $_frame;
					}
				}

				foreach ($timeframes as $_frame => $visitors) {
					if ($frame < $_frame) {
						$higher = $_frame;
						break 1;
					}
				}

				if ($average) {

					$_clause = $clause;

					if (!is_null($lower)) {
						$_clause .= ' AND duration_time > '.$lower;
					}

					if (!is_null($higher)) {
						$_clause .= ' AND duration_time < '.$higher;
					}

					if ($result = $this->query('SELECT AVG(duration_time) as average FROM '.$this->Mint->db['tblPrefix'].'visit WHERE duration_time != 0'.$_clause)) {
						$row = mysql_fetch_assoc($result);
						$data[$frame][] = $this->format_time($row['average'], TRUE);
					} else {
						$data[$frame][] = '-';
					}

					unset($_clause);

				}

				if (is_null($lower)) {
					$data[$frame][] = '< '.$this->format_time($frame);
				} elseif ($frame != 172800) {
					$data[$frame][] = $this->format_time($lower).' - '.$this->format_time($frame);
				} else {
					$data[$frame][] = '> '.$this->format_time($lower);
				}

			}

			return $data;

		}

		function format_time($seconds, $short = FALSE) {
		
			if ($seconds < 60) {
				return round($seconds).($short ? 's' : ' sec');
			}

			$minutes = round($seconds / 60);

			if ($minutes < 60) {
				return $minutes.($short ? 'm' : ' min');
			}

			$hours = round($minutes / 60);

			if ($hours < 48) {
				return $hours.($short ? 'h' : ' hour'.($hours > 1 ? 's' : ''));
			}

			$days = round($hours / 24);

			if ($days < 31) {
				return $days.($short ? 'd' : ' day'.($days > 1 ? 's' : ''));
			}

			$weeks = round($days / 7);

			return $weeks.($short ? 'w' : ' week'.($weeks > 1 ? 's' : ''));

		}

		function prune_array($array, $length) {

			if ($length != -1 && count($array) <= $length) {
				return $array;
			}

			ksort($array);
			reset($array);

			$n = count($array) - $length;

			foreach($array as $key => $val) {

				if ($n > 0) { 
					unset($array[$key]); 
					$n--;
				} else {
					break;
				}

			}

			return $array;

		}

	}
