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

 Download Trends is based on Brett DeWoody's and Ronald Heft's Trends Pepper
 More info at: http://cavemonkey50.com/code/trends

 ******************************************************************************/

	if (!defined('MINT')) {
		header('Location: /');
		exit();
	};

	$installPepper = 'TK_Downloads';

	class TK_Downloads extends Pepper {

		var $version = 225;

		var $info = array(
			'pepperName'	=> 'Downloads',
			'pepperUrl'		=> 'http://tillkruess.com/projects/pepper/downloads/',
			'pepperDesc'	=> 'The new crispy Pepparmint in town to track file requests.',
			'developerName'	=> 'Till Kr&uuml;ss',
			'developerUrl'	=> 'http://tillkruess.com/'
		);

		var $panes = array(
			'Files' => array(
				'Most Popular',
				'Most Recent',
				'Referrers',
				'Watched'
			),
			'Downloads' => array(
				'Overview',
				'Past Week',
				'Past Month',
				'Past Year'
			),
			'Download Trends' => array(
				'Popular',
				'Best',
				'Worst',
				'Watched'
			)
		);

		var $oddPanes = array(
			'Downloads'
		);

		var $prefs = array(
			'redirect' => 0,
			'checksize' => 0,
			'remote' => 0,
			'updated' => 0,
			'days' => 7,
			'against' => 7,
			'timeframe' => 1,
			'extensions' => 'zip, rar, tar, gz, gzip, bz2'
		);

		var $data = array(
			'watched' => array()
		);

		var $manifest = array(
			'files' => array(
				'id' => 'INT(10) unsigned NOT NULL auto_increment',
				'file' => 'VARCHAR(255) NOT NULL',
				'type' => 'VARCHAR(12) NOT NULL',
				'size' => 'INT(11) unsigned NOT NULL',
				'hits' => 'INT(10) unsigned NOT NULL'
			),
			'downloads' => array(
				'id' => 'INT(10) unsigned NOT NULL auto_increment',
				'file' => 'INT(10) unsigned NOT NULL',
				'dt' => "INT(10) unsigned NOT NULL default '0'",
				'ip' => 'INT(10) NOT NULL',
				'session' => 'INT(10) NOT NULL',
				'referer' => 'VARCHAR(255) NOT NULL',
				'checksum' => 'INT(10) NOT NULL'
			)
		);

		var $moderate = array('downloads');

		function update() {

			if ($this->Mint->version < 215) {

				$this->Mint->logError('This version of Downloads requires Mint v2.15.', 2);

			} else {

				if ($this->getInstalledVersion() < 208) {

					$this->Mint->logError('To ensure a successful update to Downloads v2.2 or greater, please update to v2.08 first.', 2);

				}

				if ($this->getInstalledVersion() < 210) {

					if (isset($this->Mint->cfg['pepperLookUp']['SI_SecretCrush'])) {
						$SecretCrush =& $this->Mint->getPepperByClassName('SI_SecretCrush');
						$SecretCrush->register($this->pepperId);
					}

					$this->prefs['updated'] = time() - 86400;

				}

				if ($this->getInstalledVersion() <= 211) {

					$this->query('CREATE TABLE IF NOT EXISTS '.$this->Mint->db['tblPrefix'].'_data (id TINYINT unsigned NOT NULL auto_increment, data MEDIUMTEXT NOT NULL, PRIMARY KEY(id)) TYPE=MyISAM');

					if ($this->query('INSERT INTO '.$this->Mint->db['tblPrefix'].'_data VALUES ('.$this->pepperId.", '".addslashes(serialize($this->data['downloads']))."')")) {
						unset($this->data['downloads']);
					}

				}

				if ($this->getInstalledVersion() < 220) {

					$this->prefs['days'] = 7;
					$this->prefs['against'] = 7;
					$this->prefs['timeframe'] = 1;

					if (isset($this->Mint->cfg['pepperLookUp']['SI_SecretCrush'])) {
						$SecretCrush =& $this->Mint->getPepperByClassName('SI_SecretCrush');
						$SecretCrush->register($this->pepperId);
					}

					$this->query('ALTER TABLE '.$this->Mint->db['tblPrefix'].'files CHANGE uri file VARCHAR(255) NOT NULL');
					$this->query('ALTER TABLE '.$this->Mint->db['tblPrefix'].'files ADD type VARCHAR(12) NOT NULL AFTER file');
					$this->query('UPDATE '.$this->Mint->db['tblPrefix']."files SET type = 'http'");	

					if ($result = $this->query('SELECT id, referer FROM '.$this->Mint->db['tblPrefix']."downloads WHERE referer != ''")) {

						while ($row = mysql_fetch_assoc($result)) {
							if (substr($row['referer'], 0, 4) != 'http') {
								$delete[] = $row['id'];
							} elseif (strpos($row['referer'], 'www.') !== FALSE || strpos($row['referer'], 'https://') !== FALSE) {
								$referer = preg_replace('/#.*$/', '', preg_replace("/^http(s)?:\/\/www\.([^.]+\.)/i", "http$1://$2", $row['referer']));
								$checksum = crc32(preg_replace('/(^([^:]+):\/\/(www\.)?|(:\d+)?\/.*$)/', '', $referer));
								$update[$row['id']] = array($referer, $checksum);
								unset($referer, $checksum);
							}
						}

						if (isset($delete)) {
							$this->query('DELETE FROM '.$this->Mint->db['tblPrefix']."downloads WHERE (id = '".implode("' OR id = '", $delete)."')");
						}

						if (isset($update)) {
							foreach ($update as $id => $data) {
								$this->query('UPDATE '.$this->Mint->db['tblPrefix']."downloads SET referer = '".$data[0]."', checksum = '".$data[1]."' WHERE id = ".$id);
							}
						}

					}

				}

				if ($this->getInstalledVersion() < 221) {

					if (crc32('1') > 0) {
						if ($result = $this->query('SELECT id, checksum FROM '.$this->Mint->db['tblPrefix']."downloads WHERE referer != ''")) {
							while ($row = mysql_fetch_assoc($result)) {
								$crc = $row['checksum'];
								$crc ^= 0xffffffff;
								$crc += 1;
								$crc = -$crc;
								$this->query('UPDATE '.$this->Mint->db['tblPrefix']."downloads SET checksum = '".$crc."' WHERE id = ".$row['id']);
							}							
						}
					}

				}

			}

		}

		function load_data() {

			if ($result = $this->query('SELECT data FROM '.$this->Mint->db['tblPrefix'].'_data WHERE id = '.$this->pepperId)) {

				$row = mysql_fetch_assoc($result);

				return $this->Mint->safeUnserialize($row['data']);

			} else {
				return FALSE;
			}

		}

		function save_data($data) {

			if (!empty($data) && $this->query('UPDATE '.$this->Mint->db['tblPrefix']."_data SET data = '".addslashes(serialize($data))."' WHERE id = ".$this->pepperId)) {
				return TRUE;
			} else {
				return FALSE;
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

			$this->query('CREATE TABLE IF NOT EXISTS '.$this->Mint->db['tblPrefix'].'_data (id TINYINT unsigned NOT NULL auto_increment, data MEDIUMTEXT NOT NULL, PRIMARY KEY(id)) TYPE=MyISAM');
			$this->query('INSERT INTO '.$this->Mint->db['tblPrefix'].'_data VALUES ('.$this->pepperId.", '".addslashes(serialize(array()))."')");

			if (isset($this->Mint->cfg['pepperLookUp']['SI_SecretCrush'])) {
				$SecretCrush =& $this->Mint->getPepperByClassName('SI_SecretCrush');
				$SecretCrush->register($this->pepperId);
			}

		}

		function onUninstall() {

			if ($result = $this->query('SELECT id FROM '.$this->Mint->db['tblPrefix'].'_data')) {
				if (mysql_num_rows($result) > 1) {
					$this->query('DELETE FROM '.$this->Mint->db['tblPrefix'].'_data WHERE id = '.$this->pepperId);
				} else {
					$this->query('DROP TABLE '.$this->Mint->db['tblPrefix'].'_data');
				}
			}

			if (isset($this->Mint->cfg['pepperLookUp']['SI_SecretCrush'])) {
				$SecretCrush =& $this->Mint->getPepperByClassName('SI_SecretCrush');
				$SecretCrush->unregister($this->pepperId);
			}

		}

		function onJavaScript() {

			if ($this->prefs['redirect']) {

				include(dirname(__FILE__).'/script.js');

			}

		}

		function onCustom() {

			if (isset($_POST['action'])) {

				if ($_POST['action'] == 'getsubreferrers' && isset($_POST['checksum']) && isset($_POST['filter'])) {

					print $this->build_subreferrers($_POST['checksum'], $_POST['filter']);

				} elseif ($_POST['action'] == 'getfilehistory' && isset($_POST['file']) && is_numeric($_POST['file']) && isset($_POST['isnew']) && is_numeric($_POST['isnew'])) {

					print $this->build_history($_POST['file'], $_POST['isnew']);

				} elseif ($_POST['action'] == 'deletefile' && isset($_POST['file']) && is_numeric($_POST['file'])) {

					$this->query('DELETE FROM '.$this->db['tblPrefix'].'downloads WHERE file = '.$_POST['file']);
					$this->query('DELETE FROM '.$this->db['tblPrefix'].'files WHERE id = '.$_POST['file']);

					$downloads = $this->load_data();

					foreach ($downloads[$_POST['file']] as $key => $data) {
						foreach ($data as $timestamp => $hits) {
							if (isset($downloads[0][$key][$timestamp])) {
								$downloads[0][$key][$timestamp] = $downloads[0][$key][$timestamp] - $hits;
							}
						}
					}

					if (isset($downloads[$_POST['file']])) {
						unset($downloads[$_POST['file']]);
					}

					$this->save_data($downloads);

					if (($key = array_search($_POST['file'], $this->data['watched'])) !== FALSE) {
						unset($this->data['watched'][$key]);
						$this->data['watched'] = $this->Mint->array_reindex($this->data['watched']);
					}

					print '<div id="request-feedback" class="request-feedback">All data associated with this file has been deleted.</div>'.$this->gen_filelist();

				} elseif ($_POST['action'] == ('watchfile' || 'unwatchfile') && isset($_POST['file']) && is_numeric($_POST['file'])) {

					if ($_POST['action'] == 'watchfile') {

						if (!in_array($_POST['file'], $this->data['watched'])) {
							$this->data['watched'][] = $_POST['file'];
						}

					} elseif ($_POST['action'] == 'unwatchfile') {

						if (($key = array_search($_POST['file'], $this->data['watched'])) !== FALSE) {
							unset($this->data['watched'][$key]);
							$this->data['watched'] = $this->Mint->array_reindex($this->data['watched']);
						}

					}

				}

			}

		}

		function onSecretCrushActivity($session) {

			$activity = array();
			$result = $this->query('SELECT id, file, type FROM '.$this->Mint->db['tblPrefix'].'files');

			$output = new TK_Downloads_Output;

			while ($row = mysql_fetch_assoc($result)) {
				$files[$row['id']] = array($row['file'], $row['type']);
				$output->load_module($row['type']);
			}

			if ($result = $this->query('SELECT file, dt FROM '.$this->Mint->db['tblPrefix'].'downloads WHERE session = '.$session.' ORDER BY dt DESC')) {

				while ($row = mysql_fetch_assoc($result)) {
					$file = $output->$files[$row['file']][1]->get_filename($files[$row['file']][0]);
					$activity[$row['dt']] = 'Downloaded '.$output->$files[$row['file']][1]->get_link($file, $files[$row['file']][0]);
				}

			}

			return $activity;

		}

		function onDisplay($pane, $tab, $column = '', $sort = '') {

			switch ($pane) {

				case 'Files':
					switch ($tab) {
						case 'Most Popular':
							return $this->build_mostpopular();
							break;
						case 'Most Recent':
							return $this->build_mostrecent();
							break;
						case 'Referrers':
							return $this->build_referrers();
							break;
						case 'Watched':
							return $this->build_watched();
							break;
					}
					break;

				case 'Downloads':
					switch ($tab) {
						case 'Overview':
							return $this->build_overview();
							break;
						case 'Past Week':
							return $this->build_pastweek();
							break;
						case 'Past Month':
							return $this->build_pastmonth();
							break;
						case 'Past Year':
							return $this->build_pastyear();
							break;
					}
					break;

				case 'Download Trends':
					switch ($tab) {
						case 'Popular':
							return $this->build_trends('Popular');
							break;
						case 'Best':
							return $this->build_trends('Best');
							break;
						case 'Worst':
							return $this->build_trends('Worst');
							break;
						case 'Watched':
							return $this->build_trends('Watched');
							break;
					}
					break;

			}

		}

		function onDisplaySupplemental($pane) {

			if ($pane == 'Files') {

				return <<<HTML
<style type="text/css" title="text/css" media="screen">
/* <![CDATA[ */
td.watched a.watchfile { color: #666; font-size: 1.1em; }
td.watched a.unwatchfile { color: #AB6666; }
/* ]]> */
</style>
<script type="text/javascript" language="javascript">
// <![CDATA[
function TK_manage_watched(e, id, remove) {
	if (remove) {
		var tbody = e.parentNode.parentNode.parentNode;
		var tr = e.parentNode.parentNode;
		tbody.removeChild(tr);
		var action = 'unwatchfile';
		if (window.event && window.event.stopPropagation) {
			window.event.stopPropagation();
		}
	} else {
		var action = e.href.replace(/^[^#]*#(.*)$/, '$1');
		if (action == 'watchfile')  {
			e.href = '#unwatchfile';
			e.innerHTML = '&times;';
			e.title = 'Unwatch this file';
			e.className = 'unwatchfile';
		} else {
			e.href = '#watchfile';
			e.innerHTML = '+';
			e.title = 'Watch this file';
			e.className = 'watchfile';
		};
	};
	SI.Request.post('{$this->Mint->cfg['installDir']}/?MintPath=Custom&action='+action+'&file='+id);
};
// ]]>
</script>
HTML;

			} elseif ($pane == 'Downloads') {

				return <<<HTML
<style type="text/css" title="text/css" media="screen">
/* <![CDATA[ */
.selectfilter { font-size: 1em; }
.loadingfilter { padding: 2px 0 3px 18px; background-image: url(styles/{$this->Mint->cfg['preferences']['style']}/images/spinner-w.gif); background-position: 2px 2px; background-repeat: no-repeat; }
/* ]]> */
</style>
<script type="text/javascript" language="javascript">
// <![CDATA[
function TK_load_filter(tab_name, filter) {
	var pane = filter.parentNode.parentNode.parentNode;
	if (pane.className.indexOf('content-container') != -1) {
		pane = pane.parentNode;
	}
	filter.parentNode.innerHTML = '<div class="loadingfilter">Loading...</div>';
	SI.Request.get('?pane_id='+pane.id.replace(/[^0-9]+/g, '')+'&tab='+tab_name, pane);
}
// ]]>
</script>
HTML;

			}

		}

		function onDisplayPreferences() {

			if (!isset($_GET['advanced'])) {

				$redrirect = $this->prefs['redirect'] ? ' checked="checked"' : '';
				$checksize = $this->prefs['checksize'] ? ' checked="checked"' : '';
				$remote = $this->prefs['remote'] ? ' checked="checked"' : '';

				$preferences['Tracking'] = <<<HTML
<script type="text/javascript" language="JavaScript">
	// <![CDATA[
	function TK_delete_file(id, file) {
		if (!window.confirm('Delete ' + file + '? (This will delete all data associated with this file. Once deleted this data cannot be recovered.)')) { return; }
		file = (window.encodeURIComponent) ? window.encodeURIComponent(file): window.escape(file);
		var content = document.getElementById('file-list');
		var url = '{$this->Mint->cfg['installDir']}/?MintPath=Custom&action=deletefile&file='+id;
		SI.Request.post(url, content, SI.Fade.delayedDown, 'request-feedback');
	}
	// ]]>
</script>
<table>
	<tr>
		<td>
			<label>
				<input type="checkbox" name="downloads_redirect" value="1"{$redrirect} />
				Automatically redirect links (using JavaScript) which end with one of the file extensions below.
			</label>
		</td>
	</tr>
	<tr>
		<td>
			<label>
				<input type="checkbox" name="downloads_checksize" value="1"{$checksize} />
				Compare the size of the file on every record.
			</label>
		</td>
	</tr>
	<tr>
		<td>
			<label>
				<input type="checkbox" name="downloads_remote" value="1"{$remote} />
				Allow remote file tracking (using the <code>&amp;remote</code> query command).
			</label>
		</td>
	</tr>
</table>
HTML;

				$preferences['File Extensions'] = <<<HTML
<table>
	<tr>
		<td>Track requests and automatically redirect links ending with one of these extensions:</td>
	</tr>
	<tr>
		<td><span><textarea name="downloads_extensions" rows="4" cols="30">{$this->prefs['extensions']}</textarea></span></td>
	</tr>
</table>			
HTML;

				$preferences['Download Trends'] = <<<HTML
<table class="snug">
	<tr>
		<td>Compare the last</td>
		<td><span class="inline"><input type="text" name="downloads_days" value="{$this->prefs['days']}" class="cinch" /></span></td>
		<td>days</td>
	</tr>
</table>
<table class="snug">
	<tr>
		<td>against the previous</td>
		<td><span class="inline"><input type="text" name="downloads_against" value="{$this->prefs['against']}" class="cinch" /></span></td>
		<td>days.</td>
	</tr>
</table>
HTML;

				$preferences['Delete Files'] = '<div id="file-list">'.$this->gen_filelist().'</div>';

				return $preferences;
			
			}

		}

		function onSavePreferences() {

			$this->prefs['redirect'] = isset($_POST['downloads_redirect']) ? 1 : 0;
			$this->prefs['checksize'] = isset($_POST['downloads_checksize']) ? 1 : 0;
			$this->prefs['remote'] = isset($_POST['downloads_remote']) ? 1 : 0;

			if (isset($_POST['downloads_extensions'])) {
				$this->prefs['extensions'] = implode(', ', preg_split('/[\s,]+/', $_POST['downloads_extensions']));
			}

			if (isset($_POST['downloads_days']) && is_numeric($_POST['downloads_days'])) {
				$this->prefs['days'] = $_POST['downloads_days'];
			}

			if (isset($_POST['downloads_against']) && is_numeric($_POST['downloads_against'])) {
				$this->prefs['against'] = $_POST['downloads_against'] >= $this->prefs['days'] ? $_POST['downloads_against'] : $this->prefs['days'];
			}

			$this->prefs['timeframe'] = $this->prefs['against'] / $this->prefs['days'];

			if (isset($_POST['offset']) && $_POST['offset'] != $this->Mint->cfg['offset']) {

				$offset = ($_POST['offset'] - $this->Mint->cfg['offset']) * 3600;

				$downloads = $this->load_data();
				
				foreach ($downloads as $file => $data) {
					foreach ($data as $period => $timestamps) {
						foreach ($timestamps as $timestamp => $hits) {
							$downloads[$file][$period][$timestamp - $offset] = $hits;
							unset($downloads[$file][$period][$timestamp]);
						}
					}
				}

				$this->save_data($downloads);

			}

		}

		function build_mostpopular() {

			$table_data['thead'] = array(array('value' => 'File', 'class' => 'focus'), array('value' => 'Requests <abbr title="External requests">(ext.)</abbr>', 'class' => 'sort'), array('value' => 'Traffic', 'class' => 'sort'), array('value' => '&nbsp;', 'class' => 'watched sort'));

			$output = new TK_Downloads_Output;

			if ($result = $this->query('SELECT * FROM '.$this->Mint->db['tblPrefix'].'files ORDER BY hits DESC LIMIT 0, '.$this->Mint->cfg['preferences']['rows'])) {

				$where = "referer != '' AND referer NOT LIKE '%".implode("%' OR referer NOT LIKE '%", explode(', ', $this->Mint->cfg['siteDomains']))."%'";

				while ($row = mysql_fetch_assoc($result)) {

					$_watched = $this->is_watched($row['id']);
					$_result = $this->query('SELECT COUNT(referer) as total FROM '.$this->Mint->db['tblPrefix'].'downloads WHERE file = '.$row['id'].' AND ('.$where.')');
					$_row = mysql_fetch_assoc($_result);

					$output->load_module($row['type']);

					$file = $output->$row['type']->get_filename($row['file']);
					$path = $output->$row['type']->get_path($row['file'], $this->Mint->cfg['siteDomains']);
					$size = $row['size'] == 0 ? NULL : $this->get_filesize($row['size']);
					$traffic = $row['size'] == 0 ? '&nbsp;' : $this->get_filesize($row['size'] * $row['hits']);
					$secondary = $this->Mint->cfg['preferences']['secondary'] ? '<br /><span>'.$size.' '.$path.'</span>' : '';
					$watched = $this->Mint->isLoggedIn() ? '<a href="#'.$_watched['action'].'" class="'.$_watched['action'].'" title="'.$_watched['title'].'" onclick="TK_manage_watched(this, \''.$row['id'].'\', 0); return false;">'.$_watched['icon'].'</a>' : '';

					$table_data['tbody'][] = array($output->$row['type']->get_link($file, $row['file']).$secondary, $row['hits'].' ('.$_row['total'].')', $traffic, $watched);

				}

			}

			return $this->Mint->generateTable($table_data);

		}

		function build_mostrecent() {

			$table_data['thead'] = array(array('value' => 'File', 'class' => 'focus'), array('value' => '&nbsp;', 'class' => 'watched sort'), array('value' => 'When', 'class' => 'sort'));

			$output = new TK_Downloads_Output;

			$secret_crush = isset($this->Mint->cfg['pepperLookUp']['SI_SecretCrush']);

			if ($secret_crush) {
				array_unshift($table_data['thead'], array('value' => '&nbsp;', 'class' => 'search'));
				$SecretCrush =& $this->Mint->getPepperByClassName('SI_SecretCrush');
			}

			if ($result1 = $this->query('SELECT file, dt, ip, referer FROM '.$this->Mint->db['tblPrefix'].'downloads ORDER BY dt DESC LIMIT 0, '.$this->Mint->cfg['preferences']['rows'])) {

				if ($result2 = $this->query('SELECT id, file, type, size FROM '.$this->Mint->db['tblPrefix'].'files')) {

					while ($row = mysql_fetch_assoc($result2)) {
						$files[$row['id']] = array($row['file'], $row['type'], $row['size']);
						$output->load_module($row['type']);
					}

					while ($row = mysql_fetch_assoc($result1)) {

						$_watched = $this->is_watched($row['file']);

						$file = $output->$files[$row['file']][1]->get_filename($files[$row['file']][0]);
						$path = $output->$files[$row['file']][1]->get_path($files[$row['file']][0], $this->Mint->cfg['siteDomains']);
						$size = $files[$row['file']][2] == 0 ? NULL : $this->get_filesize($files[$row['file']][2]);
						$secondary = $this->Mint->cfg['preferences']['secondary'] ? '<br /><span>'.$size.' '.$path.'</span>' : '';
						$watched = $this->Mint->isLoggedIn() ? '<a href="#'.$_watched['action'].'" class="'.$_watched['action'].'" title="'.$_watched['title'].'" onclick="TK_manage_watched(this, \''.$row['file'].'\', 0); return false;">'.$_watched['icon'].'</a>' : '';

						if ($secret_crush) {
							$table_data['tbody'][] = array($SecretCrush->generateSearchIcon(long2ip($row['ip']), TRUE), $output->$files[$row['file']][1]->get_link($file, $files[$row['file']][0]).$secondary, $watched, $this->Mint->formatDateTimeRelative($row['dt']));
						} else {
							$table_data['tbody'][] = array($output->$files[$row['file']][1]->get_link($file, $row['file']).$secondary, $watched, $this->Mint->formatDateTimeRelative($row['dt']));
						}

					}

				}

			}

			return $this->Mint->generateTable($table_data);

		}

		function build_referrers() {

			$table_data['hasFolders'] = TRUE;
			$table_data['table'] = array('id' => '', 'class' => 'folder');
			$table_data['thead'] = array(array('value' => 'Sources', 'class' => 'sort'), array('value' => 'Domain/Referrers', 'class' => 'focus'), array('value' => 'Requests', 'class' => 'sort'));

			$filters = array('Show all' => 0, 'Past hour' => 1, '2h' => 2, '4h' => 4, '8h' => 8, '24h' => 24, '48h' => 48, '72h' => 72);
			$filter = $this->generateFilterList('Referrers', $filters);
			$timespan = $this->filter ? ' AND dt > '.(time() - ($this->filter * 60 * 60)) : '';

			if ($result = $this->query('SELECT referer, checksum, COUNT(DISTINCT referer) as sources, COUNT(referer) as hits, dt FROM '.$this->Mint->db['tblPrefix']."downloads WHERE checksum != ''".$timespan." GROUP BY checksum ORDER BY hits DESC, sources DESC LIMIT 0,".$this->Mint->cfg['preferences']['rows'])) {
		
				while ($row = mysql_fetch_assoc($result)) {
					$components = parse_url($row['referer']);
					$table_data['tbody'][] = array($row['sources'], $this->cut_string($components['host']), $row['hits'], 'folderargs' => array('action' => 'getsubreferrers', 'checksum' => $row['checksum'], 'filter' => $this->filter));
				}

			}
			return $filter.$this->Mint->generateTable($table_data);

		}

		function build_subreferrers($checksum, $filter) {

			$table_data['classes'] = array('sort', 'focus', 'sort');
			$timespan = $filter ? ' AND dt > '.(time() - ($filter * 60 * 60)) : '';

			$output = new TK_Downloads_Output;

			if ($result1 = $this->query('SELECT referer, COUNT(referer) as total, dt FROM '.$this->Mint->db['tblPrefix']."downloads WHERE checksum = '".$this->escapeSQL($checksum)."'".$timespan." GROUP BY referer ORDER BY total DESC, dt DESC")) {

				if ($result2 = $this->query('SELECT id, file, type FROM '.$this->Mint->db['tblPrefix'].'files')) {

					while ($row2 = mysql_fetch_assoc($result2)) {
						$files[$row2['id']] = array($row2['file'], $row2['type']);
						$output->load_module($row2['type']);
					}

					while ($row1 = mysql_fetch_assoc($result1)) {

						$components = parse_url($row1['referer']);

						if (isset($components['path'])) {
							$table_data['tbody'][] = array('&nbsp;', '<a href="'.$row1['referer'].'" rel="nofollow">'.$this->cut_string(str_replace('', '', $components['path'])).'</a>', $row1['total']);
						}

						if ($result3 = $this->query('SELECT file, COUNT(file) as total FROM '.$this->Mint->db['tblPrefix']."downloads WHERE referer = '".$row1['referer']."'".$timespan." GROUP BY file ORDER BY total DESC, dt DESC")) {
							while ($row3 = mysql_fetch_assoc($result3)) {
								$table_data['tbody'][] = array('&nbsp;', '&nbsp;&nbsp;'.$output->$files[$row3['file']][1]->get_filename($files[$row3['file']][0]), $row3['total']);
							}
						}

					}

				}

			}

			return $this->Mint->generateTableRows($table_data);

		}

		function build_watched() {

			$table_data['thead'] = array(array('value' => 'File', 'class' => 'focus'), array('value' => 'Requests <abbr title="External requests">(ext.)</abbr>', 'class' => 'sort'), array('value' => 'Traffic', 'class' => 'sort'), array('value' => '&nbsp;', 'class' => 'watched sort'));

			$output = new TK_Downloads_Output;

			if (empty($this->data['watched'])) {
				$where = 'WHERE id = -1';
				$table_data['tbody'][] = array('You have no Watched Files', '', '', '');
			} else {
				$where = "WHERE (id = '".implode("' OR id = '", $this->data['watched'])."')";
			}

			if ($result = $this->query('SELECT id, file, type, size, hits FROM '.$this->Mint->db['tblPrefix'].'files '.$where.' ORDER BY hits DESC LIMIT 0, '.$this->Mint->cfg['preferences']['rows'])) {

				$where = "referer != '' AND referer NOT LIKE '%".implode("%' OR referer NOT LIKE '%", explode(', ', $this->Mint->cfg['siteDomains']))."%'";

				while ($row = mysql_fetch_assoc($result)) {

					$_watched = $this->is_watched($row['id']);
					$_result = $this->query('SELECT COUNT(referer) as total FROM '.$this->Mint->db['tblPrefix'].'downloads WHERE file = '.$row['id'].' AND ('.$where.')');
					$_row = mysql_fetch_assoc($_result);

					$output->load_module($row['type']);

					$file = $output->$row['type']->get_filename($row['file']);
					$path = $output->$row['type']->get_path($row['file'], $this->Mint->cfg['siteDomains']);
					$size = $row['size'] == 0 ? NULL : $this->get_filesize($row['size']);
					$traffic = $row['size'] == 0 ? '&nbsp;' : $this->get_filesize($row['size'] * $row['hits']);
					$secondary = $this->Mint->cfg['preferences']['secondary'] ? '<br /><span>'.$size.' '.$path.'</span>' : '';
					$watched = $this->Mint->isLoggedIn() ? '<a href="#'.$_watched['action'].'" class="'.$_watched['action'].'" title="'.$_watched['title'].'" onclick="TK_manage_watched(this, \''.$row['id'].'\', 1); return false;">'.$_watched['icon'].'</a>' : '';

					$table_data['tbody'][] = array($output->$row['type']->get_link($file, $row['file']).$secondary, $row['hits'].' ('.$_row['total'].')', $traffic, $watched);

				}

			}

			return $this->Mint->generateTable($table_data);

		}

		function build_overview() {

			$downloads = $this->load_data();

			$filter = $this->gen_filter(array('Overview', 'Past Week', 'Past Month', 'Past Year'));

			$table_data['table'] = array('id' => '', 'class' => 'inline-foot striped');
			$table_data['thead'] = array(array('value' => 'Past Week', 'class' => 'focus'), array('value' => '<abbr title="Total Downloads">Total</abbr>', 'class' => ''),  array('value' => '<abbr title="Average Hourly Downloads">Avg</abbr>', 'class' => ''));

			$day = $this->Mint->getOffsetTime('today');

			for ($i = 0; $i < 7; $i++) {

				$j = $day - ($i * 86400);

				if ($this->prefs['updated'] > $j) {
					$total = '-';
					$avg = isset($downloads[$this->filter][0][$j]) ? $downloads[$this->filter][0][$j] : '-';
				} else {
					$total = isset($downloads[$this->filter][0][$j]) ? $downloads[$this->filter][0][$j] : '-';
					$avg = floor($total / 24) > 1 ? floor($total / 24) : '-';
				}
	
				$table_data['tbody'][] = array($this->Mint->formatDateRelative($j, 'day'), $total, $avg);

			}

			$week = $this->Mint->generateTable($table_data);
			unset($table_data);

			$table_data['table'] = array('id' => '', 'class' => 'inline inline-foot striped');
			$table_data['thead'] = array(array('value' => 'Past Month', 'class' => 'focus'), array('value' => '<abbr title="Total Downloads">Total</abbr>', 'class' => ''), array('value' => '<abbr title="Average Daily Downloads">Avg</abbr>', 'class' => ''));
			$_week = $this->Mint->getOffsetTime('week');

			for ($i = 0; $i < 5; $i++) {

				$j = $_week - ($i * 604800);

				if ($this->prefs['updated'] > $j) {
					$total = '-';
					$avg = isset($downloads[$this->filter][1][$j]) ? $downloads[$this->filter][1][$j] : '-';
				} else {
					$total = isset($downloads[$this->filter][1][$j]) ? $downloads[$this->filter][1][$j] : '-';
					$avg = floor($total / 7) > 0 ? floor($total / 7) : '-';
				}
			
				$table_data['tbody'][] = array($this->Mint->formatDateRelative($j, 'week', $i), $total, $avg);

			}

			$month = $this->Mint->generateTable($table_data);
			unset($table_data);

			$table_data['table'] = array('id' => '', 'class' => 'inline year striped');
			$table_data['thead'] = array(array('value' => 'Past Year', 'class' => 'focus'), array('value' => '<abbr title="Total Downloads">Total</abbr>', 'class' => ''), array('value' => '<abbr title="Average Daily Downloads">Avg</abbr>', 'class' => ''));
			$_month = $this->Mint->getOffsetTime('month');

			for ($i = 0; $i < 12; $i++) {

				if ($i == 0) {
					$j = $_month;
				} else {
					$days = $this->Mint->offsetDate('t', $this->Mint->offsetMakeGMT(0, 0, 0, $this->Mint->offsetDate('n', $_month)-1, 1, $this->Mint->offsetDate('Y', $_month)));
					$j = $_month - ($days * 86400);
				}

				$_month = $j;

				if ($this->prefs['updated'] > $j) {
					$total = '-';
					$avg = isset($downloads[$this->filter][2][$j]) ? $downloads[$this->filter][2][$j] : '-';
				} else {
					$total = isset($downloads[$this->filter][2][$j]) ? $downloads[$this->filter][2][$j] : '-';
					$avg = floor($total / date('t', $j)) > 0 ? floor($total / date('t', $j)) : '-';
				}

				$table_data['tbody'][] = array($this->Mint->formatDateRelative($j, 'month', $i), $total, $avg);

			}

			return $filter."<table cellspacing=\"0\" class=\"visits\">\r\t<tr>\r\t\t<td class=\"left\">\r".$week.$month."\t\t</td>\t\t<td class=\"right\">\r".$this->Mint->generateTable($table_data)."\t\t</td>\r\t</tr>\r</table>\r";

		}

		function build_pastweek() {

			$downloads = $this->load_data();

			$filter = $this->gen_filter(array('Past Week', 'Overview', 'Past Month', 'Past Year'));
			$graph_data = array('titles' => array('background' => 'Total Downloads', 'foreground' => ''), 'key' => array('background' => 'Total', 'foreground' => ''));
			$high = 0;
			$day = $this->Mint->getOffsetTime('today');
			$days = isset($downloads[$this->filter][0]) ? $downloads[$this->filter][0] : array();

			for ($i = 0; $i < 7; $i++) {

				$timestamp = $day - ($i * 86400);
				$total = isset($days[$timestamp]) ? $days[$timestamp] : 0;
				$high = $total > $high ? $total : $high;
				$dayOfWeek = $this->Mint->offsetDate('w', $timestamp);
				$dayLabel = substr($this->Mint->offsetDate('D', $timestamp), 0, 2);

				$graph_data['bars'][] = array($total, $avg, $dayOfWeek == 0 ? '' : ($dayOfWeek == 6 ? 'Weekend' : $dayLabel), $this->Mint->formatDateRelative($timestamp, 'day'), ($dayOfWeek == 0 || $dayOfWeek == 6) ? 1 : 0);

			}

			$graph_data['bars'] = array_reverse($graph_data['bars']);

			return $filter.$this->getHTML_Graph($high, $graph_data);

		}

		function build_pastmonth() {

			$downloads = $this->load_data();

			$filter = $this->gen_filter(array('Past Month', 'Past Week', 'Overview', 'Past Year'));
			$graph_data = array('titles' => array('background' => 'Total Downloads', 'foreground' => ''), 'key' => array('background' => 'Total', 'foreground' => ''));
			$high = 0;
			$week = $this->Mint->getOffsetTime('week');
			$weeks = isset($downloads[$this->filter][1]) ? $downloads[$this->filter][1] : array();

			for ($i = 0; $i < 5; $i++) {

				$timestamp = $week - ($i * 604800);
				$total = isset($weeks[$timestamp]) ? $weeks[$timestamp] : 0;
				$high = $total > $high ? $total : $high;

				$graph_data['bars'][] = array($total, 0, $this->Mint->formatDateRelative($timestamp, 'week', $i), $this->Mint->offsetDate('D, M j', $timestamp), $i == 0 ? 1 : 0);

			}

			$graph_data['bars'] = array_reverse($graph_data['bars']);

			return $filter.$this->getHTML_Graph($high, $graph_data);

		}

		function build_pastyear() {

			$downloads = $this->load_data();

			$filter = $this->gen_filter(array('Past Year', 'Past Week', 'Overview', 'Past Month'));
			$graph_data = array('titles' => array('background' => 'Total Downloads', 'foreground' => ''), 'key' => array('background' => 'Total', 'foreground' => ''));
			$high = 0;
			$month = $this->Mint->getOffsetTime('month');
			$months = isset($downloads[$this->filter][2]) ? $downloads[$this->filter][2] : array();

			for ($i = 0; $i < 12; $i++) {

				if ($i == 0) {
					$timestamp = $month;
				} else {
					$days = $this->Mint->offsetDate('t', $this->Mint->offsetMakeGMT(0, 0, 0, $this->Mint->offsetDate('n', $month)-1, 1, $this->Mint->offsetDate('Y', $month)));
					$timestamp = $month - ($days * 86400);
				}

				$month = $timestamp;

				$total = isset($months[$timestamp]) ? $months[$timestamp] : 0;
				$high = $total > $high ? $total : $high;

				$graph_data['bars'][] = array($total, 0, $i == 0 ? 'This Month' : $this->Mint->offsetDate('M', $timestamp), $this->Mint->offsetDate('F', $timestamp), $i == 0 ? 1 : 0);

			}

			$graph_data['bars'] = array_reverse($graph_data['bars']);

			return $filter.$this->getHTML_Graph($high, $graph_data);

		}

		function build_trends($tab) {

			$filters = array('Default' => 0, 'Past hour' => 1, '2h' => 2, '4h' => 4, '8h' => 8, '24h' => 24, '48h' => 48, '72h' => 72);
			$filter_data = $this->generateFilterList($tab, $filters, $this->panes['Download Trends']);

			$data = $this->compare_files($tab);

			$output = new TK_Downloads_Output;

			if ($result = $this->query('SELECT id, file, type FROM '.$this->Mint->db['tblPrefix'].'files')) {
				while ($row = mysql_fetch_assoc($result)) {
					$files[$row['id']] = array($row['file'], $row['type']);
					$output->load_module($row['type']);
				}
			}

			if ($this->filter == 0) {
				$day1 = $this->prefs['days'];
			} else {
				$day1 = $this->filter / 24;
			}

			if ($day1 == 1) {
				$day1 = '24 hours';
			} elseif ($day1 < 1 && $day1 >= .042) {
				$day1 = round($day1 * 24, 1).' hours';
			} elseif ($day1 < .042) {
				$day1 = ceil($day1 * 1440).' ninutes';
			} else {
				$day1 = $day1.' days';
			}

			$day2 = $this->prefs['against'];

			if ($day2 == 1) {
				$day2 = '24 hours';
			} elseif ($day2 < 1 && $day2 >= .042) {
				$day2 = round($day2 * 24,1).' hours';
			} elseif ($day2 < .042) {
				$day2 = ceil($day2 * 1440).' minutes';
			} else {
				$day2 = $day2.' days';
			}

			$table_data['table'] = array('id' => '', 'class' => 'folder');
			$table_data['thead'] = array(array('value' => 'Hits', 'class' => 'sort'), array('value' => 'Comparing the last '.$day1.' to the previous '.$day2, 'class' => 'focus'), array('value' => '+/- %', 'class' => 'sort'));
			$table_data['hasFolders'] = TRUE;

			if (count($data) == 0) {
				$table_data['tbody'][] = array('', 'There are no files that meet this requirement.', '');
				$table_data['hasFolders'] = FALSE;
			}

			foreach($data as $performance) { 

				if (!$performance[3]) {
					$table_data['tbody'][] = array($performance[2], $output->$files[$performance[0]][1]->get_filename($files[$performance[0]][0]), '<div style="text-align: left;">'.($performance[1] == 'NEW' ? 'New!' : 'No requests').'</div>', 'folderargs' => array('action' => 'getfilehistory', 'file' => $performance[0], 'isnew' => $performance[1] == 'NEW' ? 1 : 0));
				} elseif ($performance[1] < 0) {
					$table_data['tbody'][] = array($performance[2], $output->$files[$performance[0]][1]->get_filename($files[$performance[0]][0]), '<div style="text-align: left;"><img src="pepper/tillkruess/downloads/images/icn-down.gif" /> '.abs(round($performance[1], 0)).'%</div>', 'folderargs' => array('action' => 'getfilehistory', 'file' => $performance[0], 'isnew' => $performance[1] == 'NEW' ? 1 : 0));
				} else {
					$table_data['tbody'][] = array($performance[2], $output->$files[$performance[0]][1]->get_filename($files[$performance[0]][0]), '<div style="text-align: left;"><img src="pepper/tillkruess/downloads/images/icn-up.gif" /> '.round($performance[1], 0).'%</div>', 'folderargs' => array('action' => 'getfilehistory', 'file' => $performance[0], 'isnew' => $performance[1] == 'NEW' ? 1 : 0));
				}

			}

			return $filter_data.$this->Mint->generateTable($table_data);

		}

		function build_history($file, $is_new) {

			$table_data['classes'] = array('sort', 'focus', 'sort');

			if (isset($_COOKIE['MintPepper'.$this->pepperId.'PopularFilter']) && $_COOKIE['MintPepper'.$this->pepperId.'PopularFilter'] != 0) {
				$days = $_COOKIE['MintPepper'.$this->pepperId.'PopularFilter'] / 24;
			} else {
				$days = $this->prefs['days'];
			}

			if ($days > 1) {
				$days = round($days);
			}

			$hits = array();
			$dates = array();

			$time_hourstart = $this->Mint->getOffsetTime('hour');
			$time_daystart = $this->Mint->getOffsetTime('today');

			if ($is_new) {

				$table_data['tbody'][] = array('', 'This file is new. No history available.', '');

			} else {			

				$result1 = $this->query('SELECT dt FROM '.$this->Mint->db['tblPrefix'].'downloads WHERE file = '.$file.' ORDER BY dt ASC');
				$row1 = mysql_fetch_assoc($result1);

				$i = 0;

				while ($i <= 9) {

					if ($days >= 1) {
						if ($i == 0) {
							$time_start1 = $time_daystart - (($days - 1) * 24 * 3600);
							$time_stop1 = time();
						} else {
							$time_start1 = $time_daystart - (($days - 1) * 24 * 3600) - ($days * 24 * 3600 * $i);
							$time_stop1 = $time_daystart - (($days - 1) * 24 * 3600) - (($days * 24 * 3600) * ($i-1));
						}
					} else {
						if ($i == 0) {
							$time_start1 = $time_hourstart - (($days * 24 - 1) * 3600);
							$time_stop1 = time();
						} else {
							$time_start1 = $time_hourstart - (($days * 24 - 1) * 3600) - (($days * 24 * 3600) * $i);
							$time_stop1 = $time_hourstart - (($days * 24 - 1) * 3600) - (($days * 24 * 3600) * ($i-1));
						}
					}

					if ($row1['dt'] > $time_start1) {
						break;
					}

					$result2 = $this->query('SELECT COUNT(id) as total FROM '.$this->Mint->db['tblPrefix'].'downloads WHERE dt > '.$time_start1.' AND dt <= '.$time_stop1.' AND file = '.$file.' GROUP BY file');
					$row2 = mysql_fetch_assoc($result2);

					$hits[] = $row2['total'] == 0 ? 0 : $row2['total'];

					if ($days == 1) {
						$dates[] = $this->Mint->offsetDate("l, jS M 'y", $time_start1);
					} elseif ($days < 1) {
						if ($i == 0) {
							$dates[] = $this->Mint->offsetDate('H:i', $time_start1).' - Now';
						} else {
							$dates[] = $this->Mint->offsetDate('H:i', $time_start1).' - '.$this->Mint->offsetDate('H:i (l)', $time_stop1); 
						}
					} else {
						if ($i ==0) {
							$dates[] = $this->Mint->offsetDate("jS M 'y", $time_start1).' - Now';
						} else {
							$dates[] = $this->Mint->offsetDate("jS M 'y", $time_start1).' - '.$this->Mint->offsetDate("jS M 'y", $time_stop1); 
						}
					}

					$i++;

				}

				for($j = 0; $j < $i; $j++) {
					$bars = floor(37 / max($hits) * $hits[$j]);
					$table_data['tbody'][] = array($hits[$j], $dates[$j], "<img align='left' src='pepper/tillkruess/downloads/images/icn-bar.gif' width='".$bars."' height='15px' />");
				}

			}

			return $this->Mint->generateTableRows($table_data);

		}

		function compare_files($tab) {

			$data = array();

			if ($this->filter == 0) {
				$time_start1 = time() - ($this->prefs['days'] * 24 * 3600);
			} else {
				$time_start1 = time() - ($this->filter * 3600);
			}

			$time_stop1 = time();

			$time_start2 = $time_start1 - ($this->prefs['against'] * 24 * 3600);
			$time_stop2 = $time_start1;

			if ($tab == 'Watched') {

				$watched = $this->data['watched'];

				if (!empty($watched)) {
					$where = 'WHERE (dt > '.$time_start1.' AND dt <= '.$time_stop1.') AND (file = '.implode(" OR file = ", $watched).')';
				} else {
					$where = 'WHERE id = -1';
				}

			} else {
				$where = 'WHERE dt > '.$time_start1.' AND dt <= '.$time_stop1;
			}

			$result1 = $this->query('SELECT file, COUNT(id) as total FROM '.$this->Mint->db['tblPrefix'].'downloads '.$where.' GROUP BY file ORDER BY total DESC, dt DESC');
			$i = 0;

			while (($row1 = mysql_fetch_assoc($result1)) && $i < $this->Mint->cfg['preferences']['rows']) {

				$result2 = $this->query('SELECT file, COUNT(id) as total FROM '.$this->Mint->db['tblPrefix'].'downloads WHERE dt > '.$time_start2.' AND dt <= '.$time_stop2.' AND file = '.$row1['file'].' GROUP BY file ORDER BY total DESC, dt DESC LIMIT 1');

				if (mysql_num_rows($result2) == 0) {
					
					if ($tab == 'Popular' || $tab == 'Watched') {

						$result3 = $this->query('SELECT COUNT(id) as total FROM '.$this->Mint->db['tblPrefix'].'downloads WHERE dt <= '.$time_start2.' AND file = '.$row1['file'].' GROUP BY file ORDER BY total DESC, dt DESC LIMIT 1');

						if (mysql_num_rows($result3) == 0) {
							$diff = 'NEW';
						} else {
							$diff = 'HITS';
						}

						$data[] = array($row1['file'], $diff, $row1['total'], FALSE);

						if ($tab != 'Watched') {
							$i++;
						}

					}

				} else {

					$row2 = mysql_fetch_assoc($result2);

					$hits1 = $row1['total'];
					
					if ($this->filter == 0) {
						$hits2 = $row2['total'] / $this->prefs['timeframe'];
					} else {
						$hits2 = $row2['total'] / $this->prefs['against'] / ($this->filter / 24);
					}
					
					$diff = (($hits1 / $hits2) * 100) - 100;

					if ($tab == 'Popular' || ($tab == 'Best' && $diff > 0) || ($tab == 'Worst' && $diff < 0) || $tab == 'Watched') {

						if ($tab != 'Watched') {
							$i++;
						}

						$data[] = array($row1['file'], $diff, $row1['total'], $diff == 0 ? FALSE : $row2['total']);

					}

				}

			}

			return $data;

		}

		function gen_filelist() {

			if ($result = $this->query('SELECT id, file FROM '.$this->Mint->db['tblPrefix'].'files ORDER BY hits DESC')) {

				if (mysql_num_rows($result)) {

					$table_data['table'] = array('id' => '', 'class' => '');

					while ($row = mysql_fetch_assoc($result)) {
						$components = parse_url($row['file']);
						$table_data['tbody'][] = array('<span><input type="text" value="'.$components['path'].'" /></span>', '<a href="#delete" onclick="TK_delete_file(\''.$row['id'].'\'); return false;"><img src="pepper/tillkruess/downloads/images/btn-delete-mini.png" width="51" height="17" alt="Delete" /></a>');
					}

				} else {

					$table_data['tbody'][] = array('Mint has not tracked any files yet.');

				}

			}

			return '<div id="file-list">'.$this->Mint->generateTable($table_data).'</div>';

		}

		function gen_filter($tabs) {

			$output = new TK_Downloads_Output;

			if ($result = $this->query('SELECT id, file, type FROM '.$this->Mint->db['tblPrefix'].'files ORDER BY file')) {
				while ($row = mysql_fetch_assoc($result)) {
					$files[$row['id']] = array($row['file'], $row['type']);
					$output->load_module($row['type']);
				}
			}

			$downloads = $this->load_data();
			ksort($downloads);
			reset($downloads);

			$filters[0] = 'All files';

			foreach($files as $id => $data) {
				if (isset($downloads[$id])) {
					$filters[$id] = $output->$data[1]->get_fullname($data[0]);
				}
			}

			if (!isset($this->filter)) {
				$this->filter = 0;
			}

			$shared = $tabs;
			unset($shared[0]);
			$tab = $tabs[0];

			if (count($filters) < 3) {

				return NULL;

			} else {

				$clean_tab = str_replace(' ', '', $tab);

				foreach($shared as $key => $shared_tab) {
					$shared[$key] = str_replace(' ', '', $shared_tab);
				}

				$filter_data = "<span>&nbsp;Show: <select name=\"filters\" class=\"selectfilter\" onchange=\"SI.Cookie.set('MintPepper{$this->pepperId}{$clean_tab}Filter', this.value); SI.Cookie.set('MintPepper{$this->pepperId}".implode("Filter', this.value); SI.Cookie.set('MintPepper{$this->pepperId}", $shared)."Filter', this.value); TK_load_filter('{$tab}', this); return false;\">\n";
				$this->filter = isset($_COOKIE['MintPepper'.$this->pepperId.$clean_tab.'Filter']) ? $_COOKIE['MintPepper'.$this->pepperId.$clean_tab.'Filter'] : 0;
				$j = 0;

				foreach ($filters as $id => $file) {

					$selected = $this->filter == $id ? ' selected="selected"' : '';
					$filter_data .= '<option value="'.$id.'"'.$selected.'>'.$file.'</option>'."\n";

					$j++;

				}

				return $filter_data."</select></span>";

			}

		}

		function cut_string($string) {

			if (strlen($string) > 28) {
				$string = '<abbr title="'.$string.'">'.substr($string, 0, 28).'&#8230;</abbr>';
			}

			return $string;

		}

		function get_filesize($size, $precision = 2){

			$iec = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
			$i = 0;

			while (($size / 1024 ) > 1) {
				$size = $size / 1024;
				$i++;
			}

			return round($size, $precision).' '.$iec[$i];

		}

		function is_watched($id) {

			if (in_array($id, $this->data['watched'])) {
				return array('action' => 'unwatchfile', 'title' => 'Unwatch this file', 'icon' => '&times;');
			} else  {
				return array('action' => 'watchfile', 'title' => 'Watch this file', 'icon' => '+');
			}

		}

	}

	class TK_Downloads_Output {

		var $modules = array();

		function load_module($module) {

			if (empty($module)) {
				$module = 'http';
			}

			if (!in_array($module, $this->modules)) {

				require_once dirname(__FILE__).'/modules/'.$module.'/output.php';

				$class = 'TK_Downloads_Output_'.$module;
				$this->$module = new $class;
				
				$this->modules[] = $module;

			}

		}

	}
