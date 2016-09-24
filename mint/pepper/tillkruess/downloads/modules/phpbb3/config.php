<?php

	if (!defined('TK_PHPBB3_PATH')) {

		// Define the absolute path to root of your phpBB3 installation.
		define('TK_PHPBB3_PATH', '/path/to/phpbb3');

	}

	// IGNORE EVERYTHING BELOW THIS LINE ...

	if (!defined('MINT')) {
		if (file_exists(TK_PHPBB3_PATH.'/config.php')) {
			require TK_PHPBB3_PATH.'/config.php';
			if (!isset($dbms, $dbpasswd)) {
				$error = TRUE;
			}
		} else {
			$error = TRUE;
		}
		if (isset($error) && TK_PHPBB3_PATH == '/path/to/phpbb3') {
			print 'Define the absolut path to root of your phpBB3 installation in <i>/mint/pepper/tillkruess/downloads/modules/phpbb3/config.php</i>.';
		} elseif (isset($error)) {
			print 'Incorrect <b>TK_PHPBB3_PATH</b>! Could not find phpBB3\'s config file at <i>'.TK_PHPBB3_PATH.'/config.php</i><br />The absolut path to this file is <i>'.dirname(__FILE__).'</i>';
		} else {
			print 'Config file found.';
		}
	} else {
		require TK_PHPBB3_PATH.'/config.php';
	}

?>