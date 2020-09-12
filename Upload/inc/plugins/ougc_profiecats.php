<?php

/***************************************************************************
 *
 *	OUGC Profile Fields Categories plugin (/inc/plugins/ougc_profiecats.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2014 Omar Gonzalez
 *
 *	Website: https://omargc.me
 *
 *	Allow administrators to create custom profile fields categories.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('Direct initialization of this file is disallowed.');

define('OUGC_PROFIECATS_ROOT', MYBB_ROOT . 'inc/plugins/ougc_profiecats');

require_once OUGC_PROFIECATS_ROOT.'/core.php';

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

// Add our hooks
if(defined('IN_ADMINCP'))
{
	require_once OUGC_PROFIECATS_ROOT.'/admin.php';
	require_once OUGC_PROFIECATS_ROOT.'/admin_hooks.php';

	\OUGCProfiecats\Core\addHooks('OUGCProfiecats\AdminHooks');
}
else
{
	require_once OUGC_PROFIECATS_ROOT.'/forum_hooks.php';

	\OUGCProfiecats\Core\addHooks('OUGCProfiecats\ForumHooks');
}

// Plugin API
function ougc_profiecats_info()
{
	return \OUGCProfiecats\Admin\_info();
}

// Activate the plugin.
function ougc_profiecats_activate()
{
	\OUGCProfiecats\Admin\_activate();
}

// Deactivate the plugin.
function ougc_profiecats_deactivate()
{
	\OUGCProfiecats\Admin\_deactivate();
}

// Install the plugin.
function ougc_profiecats_install()
{
	\OUGCProfiecats\Admin\_install();
}

// Check if installed.
function ougc_profiecats_is_installed()
{
	return \OUGCProfiecats\Admin\_is_installed();
}

// Unnstall the plugin.
function ougc_profiecats_uninstall()
{
	\OUGCProfiecats\Admin\_uninstall();
}

// control_object by Zinga Burga from MyBBHacks ( mybbhacks.zingaburga.com ), 1.62
if(!function_exists('control_object'))
{
	function control_object(&$obj, $code)
	{
		static $cnt = 0;
		$newname = '_objcont_'.(++$cnt);
		$objserial = serialize($obj);
		$classname = get_class($obj);
		$checkstr = 'O:'.strlen($classname).':"'.$classname.'":';
		$checkstr_len = strlen($checkstr);
		if(substr($objserial, 0, $checkstr_len) == $checkstr)
		{
			$vars = array();
			// grab resources/object etc, stripping scope info from keys
			foreach((array)$obj as $k => $v)
			{
				if($p = strrpos($k, "\0"))
				{
					$k = substr($k, $p+1);
				}
				$vars[$k] = $v;
			}
			if(!empty($vars))
			{
				$code .= '
					function ___setvars(&$a) {
						foreach($a as $k => &$v)
							$this->$k = $v;
					}
				';
			}
			eval('class '.$newname.' extends '.$classname.' {'.$code.'}');
			$obj = unserialize('O:'.strlen($newname).':"'.$newname.'":'.substr($objserial, $checkstr_len));
			if(!empty($vars))
			{
				$obj->___setvars($vars);
			}
		}
		// else not a valid object or PHP serialize has changed
	}
}

// Plugin Class
class OUGC_ProfiecatsCache
{
	var $cache = [
		'original' => [],
		'profilefields' => [],
		'modified' => [],
		'original' => [],
	];

	var $output = [
	];

	var $backup = [
	];
}

$GLOBALS['profiecats'] = new OUGC_ProfiecatsCache;