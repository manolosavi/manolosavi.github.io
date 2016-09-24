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

	class TK_Downloads_Output_http {

		function get_path($url, $domains) {

			$_components = parse_url($url);
			$_domains = explode(', ', $domains);

			$path = str_replace(substr(strrchr($_components['path'], '/'), 1), '', $_components['path']);

			if (!in_array($_components['host'], $_domains)) {
				$path = strlen($path) > 1 ? $_components['host'].$path : $_components['host'];
			}

			if (strlen($path) > 25) {
				$path = '<abbr title="'.$this->cut_abbr($path).'">'.substr($path, 0, 25).'&#8230;</abbr>';
			}

			return $path;

		}

		function get_filename($url) {

			$file = substr(strrchr($url, '/'), 1);

			if (strlen($file) > 28) {
				$file = substr($file, 0, 28).'&#8230;';
			}

			return '<abbr title="'.$this->cut_abbr($url).'">'.$file.'</abbr>';

		}

		function get_fullname($url) {

			$components = parse_url($url);

			if (strlen($components['path']) > 45) {
				$components['path'] = '&#8230;'.substr($components['path'], -45);
			}

			return $components['path'];

		}

		function get_link($filename, $url) {

			return '<a href="'.$url.'">'.$filename.'</a>';

		}

		function cut_abbr($string) {

			if (strlen($string) > 75) {
				$string = '&#8230;'.substr($string, -75);
			}

			return $string;

		}

	}
