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

use postParser;

use function OUGCProfiecats\Admin\_info;

use const OUGCProfiecats\ROOT;

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

function getTemplateName(string $templateName = ''): string
{
    $templatePrefix = '';

    if ($templateName) {
        $templatePrefix = '_';
    }

    return "ougcprofiecats{$templatePrefix}{$templateName}";
}

function getTemplate(string $templateName = '', bool $enableHTMLComments = true): string
{
    global $templates;

    if (DEBUG) {
        $filePath = ROOT . "/templates/{$templateName}.html";

        $templateContents = file_get_contents($filePath);

        $templates->cache[getTemplateName($templateName)] = $templateContents;
    } elseif (my_strpos($templateName, '/') !== false) {
        $templateName = substr($templateName, strpos($templateName, '/') + 1);
    }

    return $templates->render(getTemplateName($templateName), true, $enableHTMLComments);
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

function getCachedProfileFieldsCategories(): array
{
    global $mybb;

    $profileFieldsCategories = $mybb->cache->read('ougc_profiecats_categories');

    if (!empty($profileFieldsCategories) &&
        is_array($profileFieldsCategories)
    ) {
        return $profileFieldsCategories;
    }

    return [];
}

function getCachedProfileFieldsByCategory(int $categoryID): array
{
    global $profiecats;

    if (
        !empty($profiecats->cache['profilefields']) &&
        !empty($profiecats->cache['profilefields'][$categoryID]) &&
        is_array($profiecats->cache['profilefields'][$categoryID])
    ) {
        return $profiecats->cache['profilefields'][$categoryID];
    }

    return [];
}

function customTemplateIsSet(string $templateName): bool
{
    global $templates;

    if (DEBUG) {
        $filePath = ROOT . "/templates/{$templateName}.html";

        if (file_exists($filePath)) {
            $templateContents = file_get_contents($filePath);

            $templates->cache["ougcprofiecats_{$templateName}"] = $templateContents;
        }
    }

    return isset($templates->cache["ougcprofiecats_{$templateName}"]);
}

function buildFieldsCategories(array &$userData, $templatePrefix = 'memberList'): bool
{
    global $mybb, $plugins, $parser, $lang, $profiecats;
    global $theme;

    if (!($parser instanceof postParser)) {
        require_once MYBB_ROOT . 'inc/class_parser.php';

        $parser = new postParser();
    }

    load_language();

    $profileFieldsCategories = getCachedProfileFieldsCategories();

    $hookArguments = [
        'profileFieldsCategories' => &$profileFieldsCategories,
        'userData' => &$userData,
        'templatePrefix' => &$templatePrefix
    ];

    foreach ($profileFieldsCategories as $categoryData) {
        $categoryID = (int)$categoryData['cid'];

        $profiecats->output[$categoryID] = '';

        if (!($categoryProfileFields = getCachedProfileFieldsByCategory($categoryID))) {
            continue;
        }

        $hookArguments['categoryData'] = &$categoryData;

        $hookArguments['categoryProfileFields'] = &$categoryProfileFields;

        $categoryName = htmlspecialchars_uni($categoryData['name']);

        $categoryTitleString = $lang->{"ougcProfileFieldsCategories_{$templatePrefix}Title"};

        $profileFieldsItems = '';

        $alternativeBackground = alt_trow(true);

        foreach ($categoryProfileFields as $profileFieldData) {
            if (!is_member($profileFieldData['viewableby'])) {
                continue;
            }

            $fieldID = (int)$profileFieldData['fid'];

            $fieldIdentifier = "fid{$fieldID}";

            if (empty($userData[$fieldIdentifier])) {
                continue;
            }

            $hookArguments['profileFieldData'] = &$profileFieldData;

            $userFieldValue = '';

            $userFieldName = htmlspecialchars_uni($profileFieldData['name']);

            $fieldType = trim(explode("\n", $profileFieldData['type'], 2)[0]);

            $hookArguments['fieldType'] = &$fieldType;

            $userFieldOptions = explode("\n", $userData[$fieldIdentifier]);

            $hookArguments = $plugins->run_hooks(
                'ougc_profile_fields_categories_build_fields_categories_start',
                $hookArguments
            );

            if (is_array($userFieldOptions) && in_array($fieldType, ['multiselect', 'checkbox'])) {
                $userFieldValueOption = '';

                foreach ($userFieldOptions as $userFieldOption) {
                    if (!empty($userFieldOption)) {
                        if (customTemplateIsSet("{$templatePrefix}FieldMultiSelectValueCategory{$categoryID}")) {
                            $userFieldValueOption .= eval(
                            getTemplate(
                                "{$templatePrefix}FieldMultiSelectValueCategory{$categoryID}"
                            )
                            );
                        } else {
                            $userFieldValueOption .= eval(getTemplate("{$templatePrefix}FieldMultiSelectValue"));
                        }
                    }
                }

                if (!empty($userFieldValueOption)) {
                    if (customTemplateIsSet("{$templatePrefix}FieldMultiSelectCategory{$categoryID}")) {
                        $userFieldValue .= eval(
                        getTemplate(
                            "{$templatePrefix}FieldMultiSelectCategory{$categoryID}"
                        )
                        );
                    } else {
                        $userFieldValue .= eval(getTemplate("{$templatePrefix}FieldMultiSelect"));
                    }
                }
            } else {
                $parserOptions = [
                    'allow_html' => (bool)$profileFieldData['allowhtml'],
                    'allow_mycode' => (bool)$profileFieldData['allowmycode'],
                    'allow_smilies' => (bool)$profileFieldData['allowsmilies'],
                    'allow_imgcode' => (bool)$profileFieldData['allowimgcode'],
                    'allow_videocode' => (bool)$profileFieldData['allowvideocode'],
                    'filter_badwords' => true
                ];

                if ($fieldType === 'textarea') {
                    $parserOptions['me_username'] = $userData['username'];
                } else {
                    $parserOptions['nl2br'] = 0;
                }

                if (empty($mybb->user['showimages']) && !empty($mybb->user['uid']) || empty($mybb->settings['guestimages']) && empty($mybb->user['uid'])) {
                    $parserOptions['allow_imgcode'] = false;
                }

                $userFieldValue = $parser->parse_message($userData[$fieldIdentifier], $parserOptions);
            }

            $hookArguments['userFieldValue'] = &$userFieldValue;

            $hookArguments = $plugins->run_hooks(
                'ougc_profile_fields_categories_build_fields_categories_end',
                $hookArguments
            );

            if (customTemplateIsSet("{$templatePrefix}FieldCategory{$categoryID}")) {
                $profileFieldsItems .= eval(getTemplate("{$templatePrefix}FieldCategory{$categoryID}"));
            } else {
                $profileFieldsItems .= eval(getTemplate("{$templatePrefix}Field"));
            }

            $alternativeBackground = alt_trow();
        }

        if ($profileFieldsItems) {
            if (customTemplateIsSet("{$templatePrefix}Category{$categoryID}")) {
                $profiecats->output[$categoryID] .= eval(getTemplate("{$templatePrefix}Category{$categoryID}"));
            } else {
                $profiecats->output[$categoryID] .= eval(getTemplate($templatePrefix));
            }
        }
    }

    return true;
}

function controlProfileFieldsCache()
{
    global $cache, $profiecats;
    global $templatelist;

    if (!isset($templatelist)) {
        $templatelist = '';
    } else {
        $templatelist .= ',';
    }

    $profiecats->cache['original'] = $profilefields = $cache->read('profilefields');

    if ($profilefields) {
        $mainPrefix = 'ougcprofiecats_';

        $fileFieldsPrefix = 'ougcfileprofilefields_';

        $templatePrefixes = ['profile', 'postBit', 'memberList', 'userControlPanel', 'moderatorControlPanel'];

        foreach ($profilefields as $profileFieldKey => $profileFieldData) {
            $categoryID = (int)$profileFieldData['cid'];

            if ($categoryID) {
                unset($profilefields[$profileFieldKey]);

                $profiecats->cache['profilefields'][$categoryID][$profileFieldKey] = $profileFieldData;

                foreach ($templatePrefixes as $templatePrefix) {
                    $templatelist .= ", {$mainPrefix}{$templatePrefix}FieldMultiSelectValueCategory{$categoryID}, {$mainPrefix}{$templatePrefix}FieldMultiSelectCategory{$categoryID}, {$mainPrefix}{$templatePrefix}FieldCategory{$categoryID}, {$mainPrefix}{$templatePrefix}Category{$categoryID}";

                    $templatelist .= ", {$fileFieldsPrefix}{$templatePrefix}StatusModeratorCategory{$categoryID}, {$fileFieldsPrefix}{$templatePrefix}StatusCategory{$categoryID}, {$fileFieldsPrefix}{$templatePrefix}ThumbnailCategory{$categoryID}, {$fileFieldsPrefix}{$templatePrefix}Category{$categoryID}, {$fileFieldsPrefix}{$templatePrefix}Category{$categoryID}";
                }
            }
        }
    }

    $profiecats->cache['modified'] = $cache->cache['profilefields'] = $profilefields;
}