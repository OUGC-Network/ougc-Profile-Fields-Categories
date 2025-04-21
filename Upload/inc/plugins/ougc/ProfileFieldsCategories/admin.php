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

use function ougc\ProfileFieldsCategories\Core\languageLoad;

use const ougc\ProfileFieldsCategories\ROOT;

const TABLES_DATA = [
    'ougc_profiecats_categories' => [
        'cid' => [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'name' => [
            'type' => 'VARCHAR',
            'size' => 100,
            'default' => ''
        ],
        'active' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1
        ],
        'forums' => [
            'type' => 'VARCHAR',
            'size' => 100,
            'default' => -1
        ],
        'required' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0
        ],
        'disporder' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ]
    ]
];

const FIELDS_DATA = [
    'profilefields' => [
        'cid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ]
    ]
];

function pluginInformation(): array
{
    global $lang;

    languageLoad();

    return [
        'name' => 'ougc Profile Fields Categories',
        'description' => $lang->setting_group_ougc_profiecats_desc,
        'website' => 'https://ougc.network',
        'author' => 'Omar G.',
        'authorsite' => 'https://ougc.network',
        'version' => '1.8.3',
        'versioncode' => 1803,
        'codename' => 'ougc_profiecats',
        'compatibility' => '18*',
        'pl' => [
            'version' => 13,
            'url' => 'https://community.mybb.com/mods.php?action=view&pid=573'
        ],
    ];
}

function pluginActivation(): bool
{
    global $PL, $lang, $cache, $db;

    pluginLibraryLoad();

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

    $_info = pluginInformation();

    if (!isset($plugins['profiecats'])) {
        $plugins['profiecats'] = $_info['versioncode'];
    }

    dbVerifyTables();

    dbVerifyColumns();

    /*~*~* RUN UPDATES START *~*~*/

    if ($plugins['profiecats'] <= 1800) {
        $db->update_query('ougc_profiecats_categories', ['forums' => -1], "forums=''");
    }

    /*~*~* RUN UPDATES END *~*~*/

    $plugins['profiecats'] = $_info['versioncode'];

    $cache->update('ougc_plugins', $plugins);

    return true;
}

function pluginDeactivation(): bool
{
    return true;
}

function pluginInstallation(): bool
{
    dbVerifyTables();

    dbVerifyColumns();

    return true;
}

function pluginIsInstalled(): bool
{
    static $isInstalled = null;

    if ($isInstalled === null) {
        global $db;

        $isInstalledEach = true;

        foreach (TABLES_DATA as $tableName => $tableColumns) {
            $isInstalledEach = $db->table_exists($tableName) && $isInstalledEach;
        }

        $isInstalled = $isInstalledEach;
    }

    return $isInstalled;
}

function pluginUninstallation(): bool
{
    global $db, $PL, $cache;

    pluginLibraryLoad();

    // Drop DB entries
    foreach (TABLES_DATA as $name => $table) {
        $db->drop_table($name);
    }

    foreach (FIELDS_DATA as $table => $columns) {
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

function pluginLibraryLoad(): bool
{
    global $PL, $lang;

    languageLoad();

    if ($file_exists = file_exists(PLUGINLIBRARY)) {
        global $PL;

        $PL || require_once PLUGINLIBRARY;
    }

    $pluginInformation = pluginInformation();

    if (!$file_exists || $PL->version < $pluginInformation['pl']['version']) {
        flash_message(
            $lang->sprintf(
                $lang->ougc_profiecats_pluginlibrary,
                $pluginInformation['pl']['url'],
                $pluginInformation['pl']['version']
            ),
            'error'
        );

        admin_redirect('index.php?module=config-plugins');
    }

    return true;
}

function dbTables(): array
{
    $tables_data = [];

    foreach (TABLES_DATA as $tableName => $tableColumns) {
        foreach ($tableColumns as $fieldName => $fieldData) {
            if (!isset($fieldData['type'])) {
                continue;
            }

            $tables_data[$tableName][$fieldName] = dbBuildFieldDefinition($fieldData);
        }

        foreach ($tableColumns as $fieldName => $fieldData) {
            if (isset($fieldData['primary_key'])) {
                $tables_data[$tableName]['primary_key'] = $fieldName;
            }

            if ($fieldName === 'unique_key') {
                $tables_data[$tableName]['unique_key'] = $fieldData;
            }
        }
    }

    return $tables_data;
}

function dbVerifyTables(): bool
{
    global $db;

    $collation = $db->build_create_table_collation();

    foreach (dbTables() as $tableName => $tableColumns) {
        if ($db->table_exists($tableName)) {
            foreach ($tableColumns as $fieldName => $fieldData) {
                if ($fieldName == 'primary_key' || $fieldName == 'unique_key') {
                    continue;
                }

                if ($db->field_exists($fieldName, $tableName)) {
                    $db->modify_column($tableName, "`{$fieldName}`", $fieldData);
                } else {
                    $db->add_column($tableName, $fieldName, $fieldData);
                }
            }
        } else {
            $query_string = "CREATE TABLE IF NOT EXISTS `{$db->table_prefix}{$tableName}` (";

            foreach ($tableColumns as $fieldName => $fieldData) {
                if ($fieldName == 'primary_key') {
                    $query_string .= "PRIMARY KEY (`{$fieldData}`)";
                } elseif ($fieldName != 'unique_key') {
                    $query_string .= "`{$fieldName}` {$fieldData},";
                }
            }

            $query_string .= ") ENGINE=MyISAM{$collation};";

            $db->write_query($query_string);
        }
    }

    dbVerifyIndexes();

    return true;
}

function dbVerifyIndexes(): bool
{
    global $db;

    foreach (dbTables() as $tableName => $tableColumns) {
        if (!$db->table_exists($tableName)) {
            continue;
        }

        if (isset($tableColumns['unique_key'])) {
            foreach ($tableColumns['unique_key'] as $key_name => $key_value) {
                if ($db->index_exists($tableName, $key_name)) {
                    continue;
                }

                $db->write_query(
                    "ALTER TABLE {$db->table_prefix}{$tableName} ADD UNIQUE KEY {$key_name} ({$key_value})"
                );
            }
        }
    }

    return true;
}

function dbVerifyColumns(array $fieldsData = FIELDS_DATA): bool
{
    global $db;

    foreach ($fieldsData as $tableName => $tableColumns) {
        if (!$db->table_exists($tableName)) {
            continue;
        }

        foreach ($tableColumns as $fieldName => $fieldData) {
            if (!isset($fieldData['type'])) {
                continue;
            }

            if ($db->field_exists($fieldName, $tableName)) {
                $db->modify_column($tableName, "`{$fieldName}`", dbBuildFieldDefinition($fieldData));
            } else {
                $db->add_column($tableName, $fieldName, dbBuildFieldDefinition($fieldData));
            }
        }
    }

    return true;
}

function dbBuildFieldDefinition(array $fieldData): string
{
    $field_definition = '';

    $field_definition .= $fieldData['type'];

    if (isset($fieldData['size'])) {
        $field_definition .= "({$fieldData['size']})";
    }

    if (isset($fieldData['unsigned'])) {
        if ($fieldData['unsigned'] === true) {
            $field_definition .= ' UNSIGNED';
        } else {
            $field_definition .= ' SIGNED';
        }
    }

    if (!isset($fieldData['null'])) {
        $field_definition .= ' NOT';
    }

    $field_definition .= ' NULL';

    if (isset($fieldData['auto_increment'])) {
        $field_definition .= ' AUTO_INCREMENT';
    }

    if (isset($fieldData['default'])) {
        $field_definition .= " DEFAULT '{$fieldData['default']}'";
    }

    return $field_definition;
}