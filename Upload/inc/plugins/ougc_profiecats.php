<?php

/***************************************************************************
 *
 *    ougc Profile Fields Categories plugin (/inc/plugins/ougc_profiecats.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2014 Omar Gonzalez
 *
 *    Website: https://omargc.me
 *
 *    Allow administrators to create custom profile fields categories.
 *
 ***************************************************************************
 ****************************************************************************
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 ****************************************************************************/

declare(strict_types=1);

use function ougc\ProfileFieldsCategories\Admin\pluginActivation;
use function ougc\ProfileFieldsCategories\Admin\pluginDeactivation;
use function ougc\ProfileFieldsCategories\Admin\pluginInformation;
use function ougc\ProfileFieldsCategories\Admin\pluginInstallation;
use function ougc\ProfileFieldsCategories\Admin\pluginIsInstalled;
use function ougc\ProfileFieldsCategories\Admin\pluginUninstallation;
use function ougc\ProfileFieldsCategories\Core\addHooks;

use const ougc\ProfileFieldsCategories\ROOT;

defined('IN_MYBB') || die('Direct initialization of this file is disallowed.');

// You can uncomment the lines below to avoid storing some settings in the DB
define('ougc\ProfileFieldsCategories\Core\SETTINGS', [
    //'key' => '',
    'stockImageForFileFields' => 'images/stock-clock-icon.png'
]);

define('ougc\ProfileFieldsCategories\Core\DEBUG', false);

define('ougc\ProfileFieldsCategories\ROOT', constant('MYBB_ROOT') . 'inc/plugins/ougc/ProfileFieldsCategories');

require_once ROOT . '/core.php';

defined('PLUGINLIBRARY') || define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

// Add our hooks
if (defined('IN_ADMINCP')) {
    require_once ROOT . '/admin.php';
    require_once ROOT . '/admin_hooks.php';

    addHooks('ougc\ProfileFieldsCategories\Hooks\Admin');
} else {
    require_once ROOT . '/forum_hooks.php';

    addHooks('ougc\ProfileFieldsCategories\Hooks\Forum');
}
require_once ROOT . '/hooks/shared.php';

addHooks('ougc\ProfileFieldsCategories\Hooks\Shared');

function ougc_profiecats_info(): array
{
    return pluginInformation();
}

function ougc_profiecats_activate(): bool
{
    return pluginActivation();
}

function ougc_profiecats_deactivate(): bool
{
    return pluginDeactivation();
}

function ougc_profiecats_install(): bool
{
    return pluginInstallation();
}

function ougc_profiecats_is_installed(): bool
{
    return pluginIsInstalled();
}

function ougc_profiecats_uninstall()
{
    pluginUninstallation();
}

class OUGC_ProfiecatsCache
{
    public $cache = [
        'original' => [],
        'profilefields' => [],
        'modified' => [],
    ];

    public $output = [];

    public $backup = [];
}

$GLOBALS['profiecats'] = new OUGC_ProfiecatsCache();