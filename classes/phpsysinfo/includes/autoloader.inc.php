<?php
/**
 * class autoloader
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PSI
 * @author    Michael Cramer <BigMichi1@users.sourceforge.net>
 * @copyright 2009 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   SVN: $Id: autoloader.inc.php 660 2012-08-27 11:08:40Z namiltd $
 * @link      http://phpsysinfo.sourceforge.net
 */

error_reporting(E_ALL | E_STRICT);

/**
 * Anonymous function for automatic loading classes
 *
 * @param string $class_name name of the class which must be loaded
 *
 * @return void
 */

function psi_autoloader($class_name) {
	//$class_name = str_replace('-', '', $class_name);

	/* case-insensitive folders */
	$dirs = ['/plugins/'.strtolower($class_name).'/', '/includes/mb/', '/includes/ups/'];

	foreach ($dirs as $dir) {
		if (file_exists(APP_ROOT.$dir.'class.'.strtolower($class_name).'.inc.php')) {
			include_once APP_ROOT.$dir.'class.'.strtolower($class_name).'.inc.php';

			return;
		}
	}

	/* case-sensitive folders */
	$dirs = ['/includes/', '/includes/interface/', '/includes/to/', '/includes/to/device/', '/includes/os/', '/includes/plugin/', '/includes/xml/', '/includes/web/', '/includes/error/', '/includes/js/', '/includes/output/'];

	foreach ($dirs as $dir) {
		if (file_exists(APP_ROOT.$dir.'class.'.$class_name.'.inc.php')) {
			include_once APP_ROOT.$dir.'class.'.$class_name.'.inc.php';

			return;
		}
	}

	$error = PSIError::singleton();

	$error->addError("_autoload(\"".$class_name."\")", "autoloading of class file (class.".$class_name.".inc.php) failed!");
	$error->errorsAsXML();
}

/**
 * sets a user-defined error handler function
 *
 * @param integer $level   contains the level of the error raised, as an integer.
 * @param string  $message contains the error message, as a string.
 * @param string  $file    which contains the filename that the error was raised in, as a string.
 * @param integer $line    which contains the line number the error was raised at, as an integer.
 *
 * @return void
 */
function errorHandlerPsi($level, $message, $file, $line)
{
	$error = PSIError::singleton();
	$error->addPhpError("errorHandlerPsi : ", "Level : ".$level." Message : ".$message." File : ".$file." Line : ".$line);
}

set_error_handler('errorHandlerPsi');
