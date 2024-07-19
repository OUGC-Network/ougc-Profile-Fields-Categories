<?php

/***************************************************************************
 *
 *    OUGC Profile Fields Categories plugin (/inc/plugins/ougc_profiecats/core.php)
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

namespace OUGCProfiecats\Core;

use function OUGCProfiecats\Admin\_info;

function load_language(): bool
{
    global $lang;

    isset($lang->setting_group_ougc_profiecats) || $lang->load('ougc_profiecats');

    return true;
}

function load_pluginlibrary(bool $check = true): bool
{
    global $PL, $lang;

    load_language();

    if ($file_exists = file_exists(PLUGINLIBRARY)) {
        global $PL;

        $PL || require_once PLUGINLIBRARY;
    }

    if (!$check) {
        return true;
    }

    $_info = _info();

    if (!$file_exists || $PL->version < $_info['pl']['version']) {
        flash_message(
            $lang->sprintf($lang->ougc_profiecats_pluginlibrary, $_info['pl']['url'], $_info['pl']['version']),
            'error'
        );

        admin_redirect('index.php?module=config-plugins');
    }

    return true;
}

function addHooks(string $namespace): bool
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

    foreach ($definedUserFunctions as $callable) {
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;

        if (substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase . '\\') {
            $hookName = substr_replace($callable, '', 0, $namespaceWithPrefixLength);

            $priority = substr($callable, -2);

            if (is_numeric(substr($hookName, -2))) {
                $hookName = substr($hookName, 0, -2);
            } else {
                $priority = 10;
            }

            $plugins->add_hook($hookName, $callable, $priority);
        }
    }

    return true;
}

// Log admin action
function log_action(): bool
{
    global $profiecats;

    $data = [];

    if ($profiecats->cid) {
        $data['cid'] = $profiecats->cid;
    }

    log_admin_action($data);

    return true;
}

// Update the cache
function update_cache(): bool
{
    global $db, $cache;

    $d = [];

    $query = $db->simple_select('ougc_profiecats_categories', '*', '', ['order_by' => 'disporder']);
    while ($category = $db->fetch_array($query)) {
        $d[$category['cid']] = $category;
    }

    $cache->update('ougc_profiecats_categories', $d);

    return true;
}

// Clean input
function clean_ints(array $val): array
{
    foreach ($val as $k => &$v) {
        $v = (int)$v;
    }

    return array_filter($val);
}

// Insert a new rate to the DB
function insert_category(array $data, int $cid = 0, bool $update = false): bool
{
    global $db, $profiecats;

    $cleandata = [];

    !isset($data['name']) || $cleandata['name'] = $db->escape_string($data['name']);
    !isset($data['forums']) || $cleandata['forums'] = $db->escape_string(
        implode(',', clean_ints(is_array($data['forums']) ? $data['forums'] : explode(',', v)))
    );
    !isset($data['active']) || $cleandata['active'] = (int)$data['active'];
    !isset($data['required']) || $cleandata['required'] = (int)$data['required'];
    !isset($data['disporder']) || $cleandata['disporder'] = (int)$data['disporder'];

    if ($update) {
        $profiecats->cid = $cid;

        $db->update_query('ougc_profiecats_categories', $cleandata, 'cid=\'' . $profiecats->cid . '\'');
    } else {
        $profiecats->cid = (int)$db->insert_query('ougc_profiecats_categories', $cleandata);
    }

    return true;
}

// Update espesific rate
function update_category(array $data, int $cid): bool
{
    insert_category($data, $cid, true);

    return true;
}

// Completely delete a category from the DB
function delete_category(int $cid): bool
{
    global $db, $profiecats;

    $profiecats->cid = $cid;

    $db->update_query('profilefields', ['cid' => 0], 'cid=\'' . $profiecats->cid . '\'');

    $db->delete_query('ougc_profiecats_categories', 'cid=\'' . $profiecats->cid . '\'');

    return true;
}

function get_category(int $cid): array
{
    global $db;

    $query = $db->simple_select('ougc_profiecats_categories', '*', 'cid=\'' . (int)$cid . '\'');

    if ($db->num_rows($query)) {
        return $db->fetch_array($query);
    }

    return [];
}

// Generate a categories selection box.
function generate_category_select(string $name, int $selected): string
{
    global $db, $lang;

    load_language();

    $selected = (int)$selected;

    $select = "<select name=\"{$name}\">\n";

    $select_add = '';
    if ($selected == 0) {
        $select_add = ' selected="selected"';
    }
    $select .= "<option value=\"0\"{$select_add}>{$lang->ougc_profiecats_admin_none}</option>\n";

    $query = $db->simple_select('ougc_profiecats_categories', '*', '', ['order_by' => 'name']);
    while ($category = $db->fetch_array($query)) {
        $select_add = '';
        if ($selected == $category['cid']) {
            $select_add = ' selected="selected"';
        }

        $category['name'] = htmlspecialchars_uni($category['name']);
        $select .= "<option value=\"{$category['cid']}\"{$select_add}>{$category['name']}</option>\n";
    }

    $select .= "</select>\n";

    return $select;
}

function cache($key, $contents): bool
{
    static $cache = [
        'original' => [],
        'profilefields' => [],
        'modified' => [],
        'original' => [],
    ];

    $cache[$key] = $contents;

    return true;
}