<?php

/***************************************************************************
 *
 *    OUGC Profile Fields Categories plugin (/inc/plugins/ougc_profiecats/forum_hooks.php)
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

namespace ougc\ProfileFieldsCategories\ForumHooks;

use postParser;

use function ougc\ProfileFieldsCategories\Core\buildFieldsCategories;
use function ougc\ProfileFieldsCategories\Core\controlProfileFieldsCache;
use function ougc\ProfileFieldsCategories\Core\getTemplate;
use function ougc\ProfileFieldsCategories\Core\load_language;

function global_start01(): bool
{
    global $templatelist;

    controlProfileFieldsCache();

    if (THIS_SCRIPT == 'showthread.php') {
        $templatelist .= ',ougcprofiecats_postbit';
    }

    if (THIS_SCRIPT == 'usercp.php' || THIS_SCRIPT == 'modcp.php') {
        $templatelist .= ',ougcprofiecats_usercp_profile_requiredfields';
    }

    return true;
}

function xmlhttp09(): bool
{
    global_start01();

    return true;
}

// Post-bit table
function postbit(array &$postData): array
{
    buildFieldsCategories($postData, 'postBit');

    return $postData;
}

function postbit_prev(array &$post): array
{
    postbit($post);

    return $post;
}

function postbit_pm(array &$post): array
{
    postbit($post);

    return $post;
}

function postbit_announcement(array &$post): array
{
    postbit($post);

    return $post;
}

function newthread_start(): bool
{
    global $mybb, $fid, $profiecats;

    $forumID = (int)$fid;

    if (!$categories = (array)$mybb->cache->read('ougc_profiecats_categories')) {
        return false;
    }

    $error = false;

    foreach ($categories as $categoryData) {
        if (empty($profiecats->cache['profilefields'][$categoryData['cid']])) {
            continue;
        }

        if (!$categoryData['required']) {
            continue;
        }

        if (!is_member($categoryData['forums'], ['usergroup' => $forumID])) {
            continue;
        }

        foreach ($profiecats->cache['profilefields'][$categoryData['cid']] as $profileFieldData) {
            if (empty($profileFieldData['required'])) {
                continue;
            }

            if (empty($mybb->user['fid' . (int)$profileFieldData['fid']])) {
                $error = true;

                break 2;
            }
        }
    }

    if (!$error) {
        return false;
    }

    global $lang, $forum;

    load_language();

    if ($forum['rulestype'] && $forum['rules']) {
        global $parser, $templates, $theme, $rules;

        if (!($parser instanceof postParser)) {
            require_once MYBB_ROOT . 'inc/class_parser.php';
            $parser = new postParser();
        }

        if (!$forum['rulestitle']) {
            $forum['rulestitle'] = $lang->sprintf($lang->forum_rules, $forum['name']);
        }

        $parser_options = [
            'allow_html' => 1,
            'allow_mycode' => 1,
            'allow_smilies' => 1,
            'allow_imgcode' => 1,
            'filter_badwords' => 1
        ];

        $rulesTitle = $parser->parse_message($forum['rules'], $parser_options);

        $rulesContent = $forum['rules'];

        if ($forum['rulestype'] == 1 || $forum['rulestype'] == 3) {
            $rules = eval(getTemplate('forumRules'));
        } elseif ($forum['rulestype'] == 2) {
            $rules = eval(getTemplate('forumRulesLink'));
            $rules = eval($templates->render('forumdisplay_rules_link'));
        }

        $templates->cache['error'] = str_replace(
            '{$header}',
            '{$header}{$GLOBALS[\'rules\']}',
            $templates->cache['error']
        );
    }

    $lang->ougc_profiecats_newthread_error = $lang->sprintf(
        $lang->ougc_profiecats_newthread_error,
        htmlspecialchars_uni($profileFieldData['name'] ?? ''),
        htmlspecialchars_uni($categoryData['name'] ?? ''),
        $mybb->settings['bburl']
    );

    error($lang->ougc_profiecats_newthread_error);

    return true;
}

function newthread_do_newthread_start(): bool
{
    newthread_start();

    return true;
}

function member_profile_end(): bool
{
    global $memprofile, $userfields;

    $userfields = array_merge($memprofile, (array)$userfields);

    buildFieldsCategories($userfields, 'profile');

    return true;
}

function usercp_profile_end()
{
    global $mybb, $plugins, $parser, $templates, $theme, $lang;
    global $userfields, $memprofile, $bgcolor, $user, $user_fields, $errors, $xtpf_inp, $profiecats;
    global $maxlength, $ougc_fileprofilefields, $field, $type;

    if (!empty($user_fields)) {
        $user = array_merge($user, $user_fields);
    }

    $profiecats->backup['lang_profile_required'] = $lang->profile_required;

    $profiecats->backup['lang_additional_information'] = $lang->additional_information;

    $categories = (array)$mybb->cache->read('ougc_profiecats_categories');

    load_language();

    is_array($xtpf_inp) || $xtpf_inp = [];

    $hookArguments = [
        'userData' => &$mybb->user
    ];

    // Most of this code belongs to MYBB::usercp.php Lines #516 ~ #708
    foreach ($categories as $category) {
        if (empty($profiecats->cache['profilefields']) || !isset(
                $profiecats->cache['profilefields'][$category['cid']]
            )) {
            continue;
        }

        $profiecats->output[$category['cid']] = '';

        $category['name'] = htmlspecialchars_uni($category['name']);

        $lang->profile_required = $lang->sprintf($lang->ougc_profiecats_profile_required, $category['name']);

        $lang->additional_information = $lang->sprintf(
            $lang->ougc_profiecats_additional_information,
            $category['name']
        );

        $requiredFields = $optionalFields = '';

        $alternativeBackground = alt_trow(true);

        foreach ($profiecats->cache['profilefields'][$category['cid']] as $profileFieldData) {
            $fieldCode = '';

            $fieldLength = (int)$profileFieldData['length'];

            /*~~~*/
            if (!is_member(
                    $profileFieldData['editableby']
                ) || ($profileFieldData['postnum'] && $profileFieldData['postnum'] > $mybb->user['postnum'])) {
                continue;
            }

            $hookArguments['profileFieldData'] = &$profileFieldData;

            $fieldIdentifier = "fid{$profileFieldData['fid']}";

            $profileFieldData['type'] = htmlspecialchars_uni($profileFieldData['type']);

            $profileFieldDataName = htmlspecialchars_uni($profileFieldData['name']);

            $profileFieldDataDescription = htmlspecialchars_uni($profileFieldData['description']);

            $thing = explode("\n", $profileFieldData['type'], 2);

            $type = $thing[0];

            $options = $thing[1] ?? [];

            $selectOptions = '';

            if ($errors) {
                if (!isset($mybb->input['profile_fields'][$fieldIdentifier])) {
                    $mybb->input['profile_fields'][$fieldIdentifier] = '';
                }

                $userfield = $mybb->input['profile_fields'][$fieldIdentifier];
            } else {
                $userfield = $user[$fieldIdentifier];
            }

            if (function_exists('xt_proffields_load')) {
                $vars = [];

                $fieldCode = xt_proffields_inp($profileFieldData, $user, $errors, $vars);
            } elseif ($type == 'multiselect') {
                if ($errors) {
                    $useropts = $userfield;
                } else {
                    $useropts = explode("\n", $userfield);
                }

                if (is_array($useropts)) {
                    foreach ($useropts as $key => $val) {
                        $val = htmlspecialchars_uni($val);

                        $seloptions[$val] = $val;
                    }
                }

                $expoptions = explode("\n", $options);

                if (is_array($expoptions)) {
                    foreach ($expoptions as $key => $optionValue) {
                        $optionValue = trim($optionValue);

                        $optionValue = str_replace("\n", "\\n", $optionValue);

                        $selectedElement = '';

                        if (isset($seloptions[$optionValue]) && $optionValue == $seloptions[$optionValue]) {
                            $selectedElement = ' selected="selected"';
                        }

                        $selectOptions .= eval(getTemplate('userControlPanelFieldSelectOption'));
                    }

                    if (!$profileFieldData['length']) {
                        $profileFieldData['length'] = 3;
                    }

                    $fieldCode = eval(getTemplate('userControlPanelFieldMultiSelect'));
                }
            } elseif ($type == 'select') {
                $expoptions = explode("\n", $options);

                if (is_array($expoptions)) {
                    foreach ($expoptions as $key => $optionValue) {
                        $optionValue = trim($optionValue);

                        $optionValue = str_replace("\n", "\\n", $optionValue);

                        $selectedElement = '';

                        if ($optionValue == $userfield) {
                            $selectedElement = ' selected="selected"';
                        }

                        $selectOptions .= eval(getTemplate('userControlPanelFieldSelectOption'));
                    }
                    if (!$profileFieldData['length']) {
                        $profileFieldData['length'] = 1;
                    }

                    $fieldCode = eval(getTemplate('userControlPanelFieldSelect'));
                }
            } elseif ($type == 'radio') {
                $userfield = htmlspecialchars_uni($userfield);

                $expoptions = explode("\n", $options);

                if (is_array($expoptions)) {
                    foreach ($expoptions as $key => $optionValue) {
                        $checkedElement = '';

                        if ($optionValue == $userfield) {
                            $checkedElement = ' checked=\"checked\"';
                        }

                        $fieldCode .= eval(getTemplate('userControlPanelFieldRadio'));
                    }
                }
            } elseif ($type == 'checkbox') {
                $userfield = htmlspecialchars_uni($userfield);

                if ($errors) {
                    $useropts = $userfield;
                } else {
                    $useropts = explode("\n", $userfield);
                }

                if (is_array($useropts)) {
                    foreach ($useropts as $key => $val) {
                        $seloptions[$val] = $val;
                    }
                }

                $expoptions = explode("\n", $options);

                if (is_array($expoptions)) {
                    foreach ($expoptions as $key => $optionValue) {
                        $checkedElement = '';

                        if (isset($seloptions[$optionValue]) && $optionValue == $seloptions[$optionValue]) {
                            $checkedElement = 'checked="checked"';
                        }

                        $fieldCode = eval(getTemplate('userControlPanelFieldCheckBox'));
                    }
                }
            } elseif ($type == 'textarea') {
                $userFieldValue = htmlspecialchars_uni($userfield);

                $fieldCode = eval(getTemplate('userControlPanelFieldTextArea'));
            } else {
                $userFieldValue = htmlspecialchars_uni($userfield);

                $fieldMaxLength = '';

                if ($profileFieldData['maxlength'] > 0) {
                    $fieldMaxLength = " maxlength=\"{$profileFieldData['maxlength']}\"";
                }

                $fieldCode = eval(getTemplate('userControlPanelFieldText'));
            }

            $hookArguments['fieldCode'] = &$fieldCode;

            $hookArguments = $plugins->run_hooks(
                'ougc_file_profile_fields_user_control_panel',
                $hookArguments
            );

            if (function_exists('xt_proffields_load') && !empty($profileFieldData['xt_proffields_cinp'])) {
                $xtpf_inp['fid' . $profileFieldData['fid']] = xt_proffields_cinp($profileFieldData, $vars);
                //$optionalFields .= $xtpf_inp['fid'.$profileFieldData['fid']];
            } elseif (!empty($profileFieldData['required'])) {
                $requiredFields .= eval(getTemplate('userControlPanelField'));
            } else {
                $optionalFields .= eval(getTemplate('userControlPanelField'));
            }

            $alternativeBackground = alt_trow();

            $val = $options = $expoptions = $useropts = '';

            $seloptions = [];
        }

        if ($requiredFields) {
            $profiecats->output[$category['cid']] .= eval(getTemplate('userControlPanelRequiredFields'));
        }

        if ($optionalFields) {
            $profiecats->output[$category['cid']] .= eval(getTemplate('userControlPanelOptionalFields'));
        }
    }

    !isset($profiecats->backup['lang_profile_required']) || $lang->additional_information = $profiecats->backup['lang_profile_required'];

    !isset($profiecats->backup['lang_additional_information']) || $lang->additional_information = $profiecats->backup['lang_additional_information'];
}

function modcp_editprofile_end()
{
    usercp_profile_end();
}

function usercp_do_profile_start(): bool
{
    global $cache, $plugins, $profiecats;

    $cache->cache['profilefields'] = $profiecats->cache['original'];

    return true;
}

function modcp_do_editprofile_start(): bool
{
    usercp_do_profile_start();

    return true;
}

function usercp_profile_start(): bool
{
    global $cache, $profiecats;

    $cache->cache['profilefields'] = $profiecats->cache['modified'];

    return true;
}

function modcp_editprofile_start20(): bool
{
    usercp_profile_start();

    return true;
}

function memberlist_user20(array $userData): array
{
    buildFieldsCategories($userData);

    return $userData;
}