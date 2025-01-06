<?php

/***************************************************************************
 *
 *    ougc Profile Fields Categories plugin (/inc/plugins/ougc_profiecats/admin.php)
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

namespace ougc\ProfileFieldsCategories\Admin;

use DirectoryIterator;

use function ougc\ProfileFieldsCategories\Core\load_language;
use function ougc\ProfileFieldsCategories\Core\load_pluginlibrary;

use const ougc\ProfileFieldsCategories\ROOT;

function _info(): array
{
    global $lang;

    load_language();

    return [
        'name' => 'ougc Profile Fields Categories',
        'description' => $lang->setting_group_ougc_profiecats_desc,
        'website' => 'https://ougc.network',
        'author' => 'Omar G.',
        'authorsite' => 'https://ougc.network',
        'version' => '1.8.2',
        'versioncode' => 1802,
        'codename' => 'ougc_profiecats',
        'compatibility' => '18*',
        'pl' => [
            'version' => 13,
            'url' => 'https://community.mybb.com/mods.php?action=view&pid=573'
        ],
    ];
}

function _activate(): bool
{
    global $PL, $lang, $cache, $db;

    load_pluginlibrary();

    // Add templates
    $templatesDirIterator = new DirectoryIterator(ROOT . '/templates');

    $templates = [];

    foreach ($templatesDirIterator as $template) {
        if (!$template->isFile()) {
            continue;
        }

        $pathName = $template->getPathname();

        $pathInfo = pathinfo($pathName);

        if ($pathInfo['extension'] === 'html') {
            $templates[$pathInfo['filename']] = file_get_contents($pathName);
        }
    }

    if ($templates) {
        $PL->templates('ougcprofiecats', 'ougc Profile Fields Categories', $templates);
    }

    // Insert/update version into cache
    $plugins = $cache->read('ougc_plugins');

    if (!$plugins) {
        $plugins = [];
    }

    $_info = _info();

    if (!isset($plugins['profiecats'])) {
        $plugins['profiecats'] = $_info['versioncode'];
    }

    _db_verify_tables();

    _db_verify_columns();

    /*~*~* RUN UPDATES START *~*~*/

    if ($plugins['profiecats'] <= 1800) {
        $db->update_query('ougc_profiecats_categories', ['forums' => -1], "forums=''");
    }

    /*~*~* RUN UPDATES END *~*~*/

    $plugins['profiecats'] = $_info['versioncode'];

    $cache->update('ougc_plugins', $plugins);

    return true;
}

function _deactivate(): bool
{
    return true;
}

function _install(): bool
{
    _db_verify_tables();

    _db_verify_columns();

    return true;
}

function _is_installed(): bool
{
    global $db;

    foreach (_db_tables() as $name => $table) {
        $installed = $db->table_exists($name);

        break;
    }

    return $installed;
}

function _uninstall(): bool
{
    global $db, $PL, $cache;

    load_pluginlibrary();

    // Drop DB entries
    foreach (_db_tables() as $name => $table) {
        $db->drop_table($name);
    }

    foreach (_db_columns() as $table => $columns) {
        foreach ($columns as $name => $definition) {
            !$db->field_exists($name, $table) || $db->drop_column($table, $name);
        }
    }

    $PL->templates_delete('ougcprofiecats');

    // Delete version from cache
    $plugins = (array)$cache->read('ougc_plugins');

    if (isset($plugins['profiecats'])) {
        unset($plugins['profiecats']);
    }

    if (!empty($plugins)) {
        $cache->update('ougc_plugins', $plugins);
    } else {
        $cache->delete('ougc_plugins');
    }

    return true;
}

// List of tables
function _db_tables(): array
{
    return [
        'ougc_profiecats_categories' => [
            'cid' => 'int UNSIGNED NOT NULL AUTO_INCREMENT',
            'name' => "varchar(100) NOT NULL DEFAULT ''",
            'active' => "tinyint(1) NOT NULL DEFAULT '1'",
            'forums' => "varchar(100) NOT NULL DEFAULT '-1'",
            'required' => "tinyint(1) NOT NULL DEFAULT '0'",
            'disporder' => "smallint NOT NULL DEFAULT '0'",
            'primary_key' => 'cid'
        ],
    ];
}

// List of columns
function _db_columns(): array
{
    return [
        'profilefields' => [
            'cid' => "int NOT NULL DEFAULT '0'",
        ],
    ];
}

// Verify DB tables
function _db_verify_tables(): bool
{
    global $db;

    $collation = $db->build_create_table_collation();

    foreach (_db_tables() as $table => $fields) {
        if ($db->table_exists($table)) {
            foreach ($fields as $field => $definition) {
                if ($field == 'primary_key') {
                    continue;
                }

                if ($db->field_exists($field, $table)) {
                    $db->modify_column($table, "`{$field}`", $definition);
                } else {
                    $db->add_column($table, $field, $definition);
                }
            }
        } else {
            $query = 'CREATE TABLE IF NOT EXISTS `' . TABLE_PREFIX . "{$table}` (";

            foreach ($fields as $field => $definition) {
                if ($field == 'primary_key') {
                    $query .= "PRIMARY KEY (`{$definition}`)";
                } else {
                    $query .= "`{$field}` {$definition},";
                }
            }

            $query .= ") ENGINE=MyISAM{$collation};";

            $db->write_query($query);
        }
    }

    return true;
}

// Verify DB columns
function _db_verify_columns(): bool
{
    global $db;

    foreach (_db_columns() as $table => $columns) {
        foreach ($columns as $field => $definition) {
            if ($db->field_exists($field, $table)) {
                $db->modify_column($table, "`{$field}`", $definition);
            } else {
                $db->add_column($table, $field, $definition);
            }
        }
    }

    return true;
}