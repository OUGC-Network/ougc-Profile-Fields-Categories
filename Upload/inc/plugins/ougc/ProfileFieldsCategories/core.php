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

namespace OUGCProfiecats\Core;

use function OUGCProfiecats\Admin\_info;

function load_language()
{
    global $lang;

    isset($lang->setting_group_ougc_profiecats) || $lang->load('ougc_profiecats');
}

function load_pluginlibrary($check = true)
{
    global $PL, $lang;

    load_language();

    if ($file_exists = file_exists(PLUGINLIBRARY)) {
        global $PL;

        $PL or require_once PLUGINLIBRARY;
    }

    if (!$check) {
        return;
    }

    $_info = _info();

    if (!$file_exists || $PL->version < $_info['pl']['version']) {
        flash_message(
            $lang->sprintf($lang->ougc_profiecats_pluginlibrary, $_info['pl']['url'], $_info['pl']['version']),
            'error'
        );

        admin_redirect('index.php?module=config-plugins');
    }
}

function addHooks(string $namespace)
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

    foreach ($definedUserFunctions as $callable) {
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;

        if (substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase . '\\') {
            $hookName = substr_replace($callable, null, 0, $namespaceWithPrefixLength);

            $priority = substr($callable, -2);

            if (is_numeric(substr($hookName, -2))) {
                $hookName = substr($hookName, 0, -2);
            } else {
                $priority = 10;
            }

            $plugins->add_hook($hookName, $callable, $priority);
        }
    }
}

// Log admin action
function log_action()
{
    global $profiecats;

    $data = array();

    if ($profiecats->cid) {
        $data['cid'] = $profiecats->cid;
    }

    log_admin_action($data);
}

// Update the cache
function update_cache()
{
    global $db, $cache;

    $d = array();

    $query = $db->simple_select('ougc_profiecats_categories', '*', '', array('order_by' => 'disporder'));
    while ($category = $db->fetch_array($query)) {
        $d[$category['cid']] = $category;
    }

    $cache->update('ougc_profiecats_categories', $d);
}

// Clean input
function clean_ints(&$val, $implode = false)
{
    if (!is_array($val)) {
        $val = (array)explode(',', (string)$val);
    }

    foreach ($val as $k => &$v) {
        $v = (int)$v;
    }

    $val = array_filter($val);

    if ($implode) {
        $val = (string)implode(',', $val);
    }

    return $val;
}

// Insert a new rate to the DB
function insert_category($data, $cid = null, $update = false)
{
    global $db, $profiecats;

    $cleandata = array();

    !isset($data['name']) or $cleandata['name'] = $db->escape_string($data['name']);
    !isset($data['forums']) or $cleandata['forums'] = $db->escape_string(clean_ints($data['forums'], true));
    !isset($data['active']) or $cleandata['active'] = (int)$data['active'];
    !isset($data['required']) or $cleandata['required'] = (int)$data['required'];
    !isset($data['disporder']) or $cleandata['disporder'] = (int)$data['disporder'];

    if ($update) {
        $profiecats->cid = (int)$cid;

        $db->update_query('ougc_profiecats_categories', $cleandata, 'cid=\'' . $profiecats->cid . '\'');
    } else {
        $profiecats->cid = (int)$db->insert_query('ougc_profiecats_categories', $cleandata);
    }

    return true;
}

// Update espesific rate
function update_category($data, $cid)
{
    insert_category($data, $cid, true);
}

// Completely delete a category from the DB
function delete_category($cid)
{
    global $db, $profiecats;

    $profiecats->cid = (int)$cid;

    $db->update_query('profilefields', array('cid' => 0), 'cid=\'' . $profiecats->cid . '\'');

    $db->delete_query('ougc_profiecats_categories', 'cid=\'' . $profiecats->cid . '\'');
}

function get_category($cid)
{
    global $db;

    $query = $db->simple_select('ougc_profiecats_categories', '*', 'cid=\'' . (int)$cid . '\'');
    return $db->fetch_array($query);
}

// Generate a categories selection box.
function generate_category_select($name, $selected)
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

    $query = $db->simple_select('ougc_profiecats_categories', '*', '', array('order_by' => 'name'));
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

function cache($key, $contents)
{
    static $cache = [
        'original' => [],
        'profilefields' => [],
        'modified' => [],
        'original' => [],
    ];

    $cache[$key] = $contents;
}