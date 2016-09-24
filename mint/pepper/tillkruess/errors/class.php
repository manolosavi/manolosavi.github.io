<?php

/******************************************************************************
 Pepper

 Developer: Till Krüss
 Plug-in Name: Errors

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

	$installPepper = 'TK_Errors';

	class TK_Errors extends Pepper {

		var $version = 101;

		var $info = array(
			'pepperName' => 'Errors',
			'pepperUrl' => 'http://pepper.pralinenschachtel.de/',
			'pepperDesc' => 'Pepper that helps tracking down dead links.',
			'developerName' => 'Till Kr&uuml;ss',
			'developerUrl' => 'http://pralinenschachtel.de/'
		);
 
		var $panes = array(
			'Errors' => array(
				'Most Popular',
				'Most Recent'
			)
		);

		var $prefs = array(
			'regexps' => array(
				403 => 'LzQwMyguKylGb3JiaWRkZW4v',
				404 => 'LzQwNCguKylOb3QgRm91bmQv',
				410 => 'LzQxMCguKylHb25lLw=='
			)
		);

		var $manifest = array(
			'visit' => array(
				'status_code' => 'smallint(3) unsigned NOT NULL'
			)
		);

		function isCompatible() {

			if ($this->Mint->version < 215) {
				return array('isCompatible' => FALSE, 'explanation' => '<p>This Pepper requires Mint 2.15 or higher.</p>');
			} else {
				return array('isCompatible' => TRUE);
			}

		}

		function onRecord() {

			$title = $this->escapeSQL(trim(str_replace('\n', ' ', preg_replace('/%u([\d\w]{4})/', '&#x$1;', ($_GET['resource_title_encoded']) ? $_GET['resource_title'] : htmlentities($_GET['resource_title'])))));

			foreach ($this->prefs['regexps'] as $code => $regexp) {
				if (!empty($regexp)) {
					if (preg_match(base64_decode($regexp), $title, $matches)) {
						return array('status_code' => $code);
					}
					unset($matches);
				}
			}

		}

		function onCustom() {

			if (isset($_POST['action']) && $_POST['action'] == 'getpagereferrers' && isset($_POST['checksum']) && isset($_POST['filter'])) {
				print $this->build_referrers($_POST['checksum'], $_POST['filter']);
			}

		}
	
		function onDisplay($pane, $tab, $column = '', $sort = '') {

			switch ($pane) {
				case 'Errors': 
					switch ($tab) {
						case 'Most Popular':
							return $this->build_mostpopular();
						break;
						case 'Most Recent':
							return $this->build_mostrecent();
						break;
					}
				break;
			}

		}

		function onDisplayPreferences() {

			$regexps = array(
				403 => htmlspecialchars(base64_decode($this->prefs['regexps'][403])),
				404 => htmlspecialchars(base64_decode($this->prefs['regexps'][404])),
				410 => htmlspecialchars(base64_decode($this->prefs['regexps'][410]))
			);

			$preferences['403 Forbidden'] = <<<HTML
<table>
	<tr>
		<td>Regular expression used to identify 403 page titles.</td>
	</tr>
	<tr>
		<td><span><input type="text" name="errors_403" value="{$regexps[403]}" /></span></td>
	</tr>
</table>
HTML;

			$preferences['404 Not Found'] = <<<HTML
<table>
	<tr>
		<td>Regular expression used to identify 404 page titles.</td>
	</tr>
	<tr>
		<td><span><input type="text" name="errors_404" value="{$regexps[404]}" /></span></td>
	</tr>
</table>
HTML;

			$preferences['410 Gone'] = <<<HTML
<table>
	<tr>
		<td>Regular expression used to identify 410 page titles.</td>
	</tr>
	<tr>
		<td><span><input type="text" name="errors_410" value="{$regexps[410]}" /></span></td>
	</tr>
</table>
HTML;

			$preferences['Update'] = <<<HTML
<table>
	<tr>
		<td>
			<label>
				<input type="checkbox" name="errors_update" value="1" />
				Apply regular expressions to all visits when saving the preferences this time.
			</label>
		</td>
	</tr>
</table>
HTML;

			return $preferences;

		}

		function onSavePreferences() {

			if (isset($_POST['errors_403'])) {
				$this->prefs['regexps'][403] = base64_encode($_POST['errors_403']);
			}

			if (isset($_POST['errors_404'])) {
				$this->prefs['regexps'][404] = base64_encode($_POST['errors_404']);
			}

			if (isset($_POST['errors_410'])) {
				$this->prefs['regexps'][410] = base64_encode($_POST['errors_410']);
			}

			if (isset($_POST['errors_update'])) {

				if ($result = $this->query('SELECT resource_title FROM '.$this->Mint->db['tblPrefix'].'visit GROUP BY resource_title')) {

					while ($row = mysql_fetch_assoc($result)) {
						foreach ($this->prefs['regexps'] as $code => $regexp) {
							unset($matches);
							if (!empty($regexp)) {
								if (preg_match(base64_decode($regexp), $row['resource_title'], $matches)) {
									$this->query('UPDATE '.$this->Mint->db['tblPrefix'].'visit SET status_code = '.$code." WHERE resource_title = '".$row['resource_title']."'");
									break;
								}
							}
						}
					}

				}

			}
			
		}

		function build_mostpopular() {

			$table_data['hasFolders'] = true;
			$table_data['table'] = array('id' => '', 'class' => 'folder');
			$table_data['thead'] = array(array('value' => 'Hits', 'class' => 'sort'), array('value' => 'Page', 'class' => 'focus'), array('value' => 'Code', 'class' => 'sort'));

			$filters = array('Show all' => 0, 'Past hour' => 1, '2h' => 2, '4h' => 4, '8h' => 8, '24h' => 24, '48h' => 48, '72h' => 72);
			$filter_data = $this->generateFilterList('Most Popular', $filters);
			$timespan = $this->filter ? ' AND dt > '.(time() - ($this->filter * 60 * 60)) : '';

			if ($result = $this->query('SELECT status_code, resource, resource_checksum, COUNT(resource_checksum) as total, dt FROM '.$this->Mint->db['tblPrefix'].'visit WHERE status_code > 0'.$timespan.' GROUP BY resource_checksum ORDER BY total DESC, dt DESC LIMIT 0, '.$this->Mint->cfg['preferences']['rows'])) {

				while ($row = mysql_fetch_assoc($result)) {
					$table_data['tbody'][] = array($row['total'], $this->Mint->abbr($row['resource']), $row['status_code'], 'folderargs' => array('action' => 'getpagereferrers', 'checksum' => $row['resource_checksum'], 'filter' => $this->filter));
				}

			}

			return $filter_data.$this->Mint->generateTable($table_data);

		}

		function build_mostrecent() {

			$table_data['thead'] = array(array('value' => 'Page', 'class' => 'focus'), array('value' => 'Code', 'class' => 'sort'), array('value' => 'When', 'class' => 'sort'));

			if (isset($this->Mint->cfg['manifest']['visit']['referred_by_feed'])) {
				$additional_columns = ', referred_by_feed';
			}

			if ($result = $this->query('SELECT dt, status_code, referer, resource, search_terms, img_search_found'.$additional_columns.' FROM '.$this->Mint->db['tblPrefix'].'visit WHERE status_code > 0 ORDER BY dt DESC LIMIT 0, '.$this->Mint->cfg['preferences']['rows'])) {

				while ($row = mysql_fetch_assoc($result)) {

					if (!empty($row['search_terms'])) {
						$referer = 'From a search for <a href="'.$row['referer'].'" rel="nofollow"'.($row['img_search_found'] ? ' class="image-search"' : '').'>'.$this->Mint->abbr(stripslashes($row['search_terms'])).'</a>';
					} elseif(!empty($row['referer'])) {	
						$referer = 'From <a href="'.$row['referer'].'" rel="nofollow">'.$this->Mint->abbr($row['referer']).'</a>';
					}

					if (isset($this->Mint->cfg['manifest']['visit']['referred_by_feed']) && $row['referred_by_feed']) {
						$referer = 'From a seed';
					}

					$page = '<a href="'.$row['resource'].'">'.$this->Mint->abbr($row['resource'], 36).'</a>';

					if (isset($referer) && $this->Mint->cfg['preferences']['secondary'])
					{
						$page .= '<br /><span>'.$referer.'</span>';
					}

					$table_data['tbody'][] = array($page, $row['status_code'], $this->Mint->formatDateTimeRelative($row['dt']));

				}

			}

			return $this->Mint->generateTable($table_data);

		}

		function build_referrers($checksum, $filter) {

			$table_data['classes'] = array('sort', 'focus', 'sort');
			$timespan = $filter ? ' AND dt > '.(time() - ($filter * 60 * 60)) : '';

			if ($result = $this->query('SELECT referer, COUNT(referer) as total, dt FROM '.$this->Mint->db['tblPrefix']."visit WHERE resource_checksum = '".$this->escapeSQL($checksum)."'".$timespan.' GROUP BY referer ORDER BY total DESC, dt DESC')) {

				while ($row = mysql_fetch_assoc($result)) {

					$referrer = empty($row['referer']) ? 'No referrer' : '<a href="'.$row['referer'].'" rel="nofollow">'.$this->Mint->abbr($row['referer']).'</a>';
					$table_data['tbody'][] = array($row['total'], $referrer, '&nbsp;');
				}

			}

			return $this->Mint->generateTableRows($table_data);

		}

	}
