<?php

	// Add more or alter these lines if you want the Downloads Pepper
	// to search within these directories for files.
	
	$TK_HTTP_PATHS[] = '/path/to/your/files/directory';
	$TK_HTTP_PATHS[] = '/another/path/to/your/files/directory';
	$TK_HTTP_PATHS[] = '/yet/another/path/to/your/files/directory';

	// IGNORE EVERYTHING BELOW THIS LINE ...

	if (is_array($TK_HTTP_PATHS)) {
		foreach ($TK_HTTP_PATHS as $key => $path) {
			if (strpos($path, 'path/to/your/files') !== FALSE) {
				unset($TK_HTTP_PATHS[$key]);
			} elseif (substr($path, -1) == '/') {
				$TK_HTTP_PATHS[$key] = substr($path, 0, -1);
			}
		}
	}

	if (!defined('MINT') & is_array($TK_HTTP_PATHS)) {
		$tests = array('found' => array(), 'notfound' => array());
		foreach ($TK_HTTP_PATHS as $path) {
			if (file_exists($path) && is_dir($path)) {
				$tests['found'][] = $path;
			} else {
				$tests['notfound'][] = $path;
			}
		}
		print count($tests['found']).' of '.count($TK_HTTP_PATHS).' paths are valid!<br />';
		if ($tests['notfound']) {
			print 'The following paths are invalid:<br /><code>';
			foreach ($tests['notfound'] as $path) {			
				print str_repeat('*', strlen($path) - strlen($path) / 2).substr($path, -(strlen($path) / 2)).'<br />';
			}
			print '</code>';
		}
	}

?>