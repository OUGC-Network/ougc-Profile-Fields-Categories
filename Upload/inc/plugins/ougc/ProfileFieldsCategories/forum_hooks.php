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

namespace OUGCProfiecats\ForumHooks;

// Break down categorization here
use postParser;

use function ougc\FileProfileFields\Hooks\Forum\ougc_plugins_customfields_usercp_end80;
use function OUGCProfiecats\Core\load_language;

function global_start09()
{
    global $cache, $profiecats;

    $profiecats->cache['original'] = $profilefields = $cache->read('profilefields');

    if ($profilefields) {
        foreach ($profilefields as $key => $field) {
            if ($field['cid']) {
                unset($profilefields[$key]);

                $profiecats->cache['profilefields'][$field['cid']][$key] = $field;
            }
        }
    }

    $profiecats->cache['modified'] = $cache->cache['profilefields'] = $profilefields;

    global $templatelist;

    if (!isset($templatelist)) {
        $templatelist = '';
    } else {
        $templatelist .= ',';
    }

    if (THIS_SCRIPT == 'showthread.php') {
        $templatelist .= 'ougcprofiecats_postbit';
    }

    if (THIS_SCRIPT == 'usercp.php' || THIS_SCRIPT == 'modcp.php') {
        $templatelist .= 'ougcprofiecats_usercp_profile_requiredfields';
    }
}

function xmlhttp09()
{
    global_start09();
}

// Post-bit table
function postbit(&$post)
{
    global $mybb, $parser, $templates, $theme, $lang, $profiecats;

    $categories = (array)$mybb->cache->read('ougc_profiecats_categories');

    load_language();

    foreach ($categories as $category) {
        if (!is_array($profiecats->cache['profilefields'][$category['cid']])) {
            return;
        }

        $category['name'] = htmlspecialchars_uni($category['name']);

        $category_name = $lang->sprintf($lang->ougc_profiecats_postbit, $category['name']);

        $profiecats->output[$category['cid']] = '';

        $profilefields = '';

        foreach ($profiecats->cache['profilefields'][$category['cid']] as $field) {
            /*~~~*/
            $fieldfid = "fid{$field['fid']}";
            if (!empty($post[$fieldfid])) {
                $post['fieldvalue'] = '';
                $post['fieldname'] = htmlspecialchars_uni($field['name']);

                $thing = explode("\n", $field['type'], '2');
                $type = trim($thing[0]);
                $useropts = explode("\n", $post[$fieldfid]);

                if (is_array($useropts) && ($type == 'multiselect' || $type == 'checkbox')) {
                    $post['fieldvalue_option'] = '';

                    foreach ($useropts as $val) {
                        if ($val != '') {
                            eval(
                                "\$post['fieldvalue_option'] .= \"" . $templates->get(
                                    'postbit_profilefield_multiselect_value'
                                ) . "\";"
                            );
                        }
                    }
                    if ($post['fieldvalue_option'] != '') {
                        eval(
                            "\$post['fieldvalue'] .= \"" . $templates->get(
                                'postbit_profilefield_multiselect'
                            ) . "\";"
                        );
                    }
                } else {
                    $field_parser_options = array(
                        'allow_html' => $field['allowhtml'],
                        'allow_mycode' => $field['allowmycode'],
                        'allow_smilies' => $field['allowsmilies'],
                        'allow_imgcode' => $field['allowimgcode'],
                        'allow_videocode' => $field['allowvideocode'],
                        #"nofollow_on" => 1,
                        'filter_badwords' => 1
                    );

                    if ($type == 'textarea') {
                        $field_parser_options['me_username'] = $post['username'];
                    } else {
                        $field_parser_options['nl2br'] = 0;
                    }

                    if ($mybb->user['showimages'] != 1 && $mybb->user['uid'] != 0 || $mybb->settings['guestimages'] != 1 && $mybb->user['uid'] == 0) {
                        $field_parser_options['allow_imgcode'] = 0;
                    }

                    $post['fieldvalue'] = $parser->parse_message($post[$fieldfid], $field_parser_options);
                }
                /*~~~*/

                $profilefields .= eval($templates->render('postbit_profilefield'));
            }
        }

        if ($profilefields) {
            $profiecats->output[$category['cid']] = eval($templates->render('ougcprofiecats_postbit'));
        }
    }
}

function postbit_prev(&$post)
{
    postbit($post);
}

function postbit_pm(&$post)
{
    postbit($post);
}

function postbit_announcement(&$post)
{
    postbit($post);
}

// New thread
function newthread_start()
{
    global $mybb, $fid, $profiecats;

    if (!$categories = (array)$mybb->cache->read('ougc_profiecats_categories')) {
        return;
    }

    $error = false;

    foreach ($categories as $category) {
        if (empty($profiecats->cache['profilefields'][$category['cid']])) {
            continue;
        }

        if (!$category['required']) {
            continue;
        }

        if (!is_member($category['forums'], ['usergroup' => $fid])) {
            continue;
        }

        foreach ($profiecats->cache['profilefields'][$category['cid']] as $field) {
            if (!$field['required']) {
                continue;
            }

            if (empty($mybb->user['fid' . (int)$field['fid']])) {
                $error = true;

                break 2;
            }
        }
    }

    if (!$error) {
        return;
    }

    global $lang, $forum;

    load_language();

    $foruminfo = &$forum;

    if ($foruminfo['rulestype'] && $foruminfo['rules']) {
        global $parser, $templates, $theme, $rules;

        if (!($parser instanceof postParser)) {
            require_once MYBB_ROOT . 'inc/class_parser.php';
            $parser = new postParser();
        }

        if (!$foruminfo['rulestitle']) {
            $foruminfo['rulestitle'] = $lang->sprintf($lang->forum_rules, $foruminfo['name']);
        }

        $parser_options = array(
            'allow_html' => 1,
            'allow_mycode' => 1,
            'allow_smilies' => 1,
            'allow_imgcode' => 1,
            'filter_badwords' => 1
        );

        $foruminfo['rules'] = $parser->parse_message($foruminfo['rules'], $parser_options);

        if ($foruminfo['rulestype'] == 1 || $foruminfo['rulestype'] == 3) {
            $rules = eval($templates->render('forumdisplay_rules'));
        } elseif ($foruminfo['rulestype'] == 2) {
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
        htmlspecialchars_uni($field['name']),
        htmlspecialchars_uni($category['name']),
        $mybb->settings['bburl']
    );

    error($lang->ougc_profiecats_newthread_error);
}

function newthread_do_newthread_start()
{
    newthread_start();
}

// Profile display
function member_profile_end()
{
    global $mybb, $userfields, $parser, $templates, $theme, $lang, $memprofile, $bgcolor, $profiecats;

    $categories = (array)$mybb->cache->read('ougc_profiecats_categories');

    foreach ($categories as $category) {
        $profiecats->output[$category['cid']] = '';

        if (!is_array($profiecats->cache['profilefields'][$category['cid']])) {
            return;
        }

        $lang->users_additional_info = htmlspecialchars_uni($category['name']);

        $bgcolor = $alttrow = 'trow1';
        $customfields = $profilefields = '';

        foreach ($profiecats->cache['profilefields'][$category['cid']] as $customfield) {
            /*~~~*/
            if ($mybb->usergroup['cancp'] != 1 && $mybb->usergroup['issupermod'] != 1 && $mybb->usergroup['canmodcp'] != 1 && !is_member(
                    $customfield['viewableby']
                ) || !$customfield['profile']) {
                continue;
            }

            if (function_exists('xt_proffields_inp')) {
                $field = "fid{$customfield['fid']}";

                if (isset($userfields[$field])) {
                    $customfieldval = xt_proffields_disp($customfield, $userfields[$field]);
                }

                if ($customfieldval) {
                    $customfield['name'] = htmlspecialchars_uni($customfield['name']);

                    $customfields .= eval($templates->render('member_profile_customfields_field'));

                    $bgcolor = alt_trow();
                }

                continue;
            }

            $thing = explode("\n", $customfield['type'], '2');
            $type = trim($thing[0]);

            $customfieldval = $customfield_val = '';
            $field = "fid{$customfield['fid']}";

            if (isset($userfields[$field])) {
                $useropts = explode("\n", $userfields[$field]);
                $customfieldval = $comma = '';
                if (is_array($useropts) && ($type == 'multiselect' || $type == 'checkbox')) {
                    foreach ($useropts as $val) {
                        if ($val != '') {
                            eval(
                                "\$customfield_val .= \"" . $templates->get(
                                    'member_profile_customfields_field_multi_item'
                                ) . "\";"
                            );
                        }
                    }
                    if ($customfield_val != '') {
                        eval(
                            "\$customfieldval = \"" . $templates->get(
                                'member_profile_customfields_field_multi'
                            ) . "\";"
                        );
                    }
                } else {
                    $parser_options = array(
                        'allow_html' => $customfield['allowhtml'],
                        'allow_mycode' => $customfield['allowmycode'],
                        'allow_smilies' => $customfield['allowsmilies'],
                        'allow_imgcode' => $customfield['allowimgcode'],
                        'allow_videocode' => $customfield['allowvideocode'],
                        #"nofollow_on" => 1,
                        'filter_badwords' => 1
                    );

                    if ($customfield['type'] == 'textarea') {
                        $parser_options['me_username'] = $memprofile['username'];
                    } else {
                        $parser_options['nl2br'] = 0;
                    }

                    if ($mybb->user['showimages'] != 1 && $mybb->user['uid'] != 0 || $mybb->settings['guestimages'] != 1 && $mybb->user['uid'] == 0) {
                        $parser_options['allow_imgcode'] = 0;
                    }

                    $customfieldval = $parser->parse_message($userfields[$field], $parser_options);
                }
            }

            if ($customfieldval) {
                $customfield['name'] = htmlspecialchars_uni($customfield['name']);
                eval("\$customfields .= \"" . $templates->get('member_profile_customfields_field') . "\";");
                $bgcolor = alt_trow();
            }
            /*~~~*/
        }

        if ($customfields) {
            $profilefields = eval($templates->render('member_profile_customfields'));
        }

        $profiecats->output[$category['cid']] = $profilefields;
    }
}

// UCP Display
function usercp_profile_end()
{
    global $mybb, $plugins, $parser, $templates, $theme, $lang;
    global $userfields, $memprofile, $bgcolor, $user, $user_fields, $errors, $xtpf_inp, $profiecats;
    global $maxlength, $code, $ougc_fileprofilefields, $field, $profilefield, $type;

    //global $customfield, , , , $profilefields;

    if (!empty($user_fields)) {
        $user = array_merge($user, $user_fields);
    }

    $profiecats->backup['lang_profile_required'] = $lang->profile_required;

    $profiecats->backup['lang_additional_information'] = $lang->additional_information;

    $categories = (array)$mybb->cache->read('ougc_profiecats_categories');

    load_language();

    is_array($xtpf_inp) or $xtpf_inp = array();

    // Most of this code belongs to MYBB::usercp.php Lines #516 ~ #708
    foreach ($categories as $category) {
        if (empty($profiecats->cache['profilefields']) || !isset(
                $profiecats->cache['profilefields'][$category['cid']]
            )) {
            return;
        }

        $profiecats->output[$category['cid']] = '';

        $category['name'] = htmlspecialchars_uni($category['name']);

        $lang->profile_required = $lang->sprintf($lang->ougc_profiecats_profile_required, $category['name']);

        $lang->additional_information = $lang->sprintf(
            $lang->ougc_profiecats_additional_information,
            $category['name']
        );

        $requiredfields = $customfields = '';

        $altbg = alt_trow(true);

        foreach ($profiecats->cache['profilefields'][$category['cid']] as $profilefield) {
            /*~~~*/
            if (!is_member(
                    $profilefield['editableby']
                ) || ($profilefield['postnum'] && $profilefield['postnum'] > $mybb->user['postnum'])) {
                continue;
            }

            $profilefield['type'] = htmlspecialchars_uni($profilefield['type']);
            $profilefield['name'] = htmlspecialchars_uni($profilefield['name']);
            $profilefield['description'] = htmlspecialchars_uni($profilefield['description']);
            $thing = explode("\n", $profilefield['type'], '2');
            $type = $thing[0];
            if (isset($thing[1])) {
                $options = $thing[1];
            } else {
                $options = array();
            }
            $field = "fid{$profilefield['fid']}";
            $select = '';
            if ($errors) {
                if (!isset($mybb->input['profile_fields'][$field])) {
                    $mybb->input['profile_fields'][$field] = '';
                }
                $userfield = $mybb->input['profile_fields'][$field];
            } else {
                $userfield = $user[$field];
            }
            if ($type == 'multiselect') {
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
                    foreach ($expoptions as $key => $val) {
                        $val = trim($val);
                        $val = str_replace("\n", "\\n", $val);

                        $sel = '';
                        if (isset($seloptions[$val]) && $val == $seloptions[$val]) {
                            $sel = " selected=\"selected\"";
                        }

                        eval("\$select .= \"" . $templates->get('usercp_profile_profilefields_select_option') . "\";");
                    }
                    if (!$profilefield['length']) {
                        $profilefield['length'] = 3;
                    }

                    eval("\$code = \"" . $templates->get('usercp_profile_profilefields_multiselect') . "\";");
                }
            } elseif ($type == 'select') {
                $expoptions = explode("\n", $options);
                if (is_array($expoptions)) {
                    foreach ($expoptions as $key => $val) {
                        $val = trim($val);
                        $val = str_replace("\n", "\\n", $val);
                        $sel = '';
                        if ($val == htmlspecialchars_uni($userfield)) {
                            $sel = " selected=\"selected\"";
                        }

                        eval("\$select .= \"" . $templates->get('usercp_profile_profilefields_select_option') . "\";");
                    }
                    if (!$profilefield['length']) {
                        $profilefield['length'] = 1;
                    }

                    eval("\$code = \"" . $templates->get('usercp_profile_profilefields_select') . "\";");
                }
            } elseif ($type == 'radio') {
                $userfield = htmlspecialchars_uni($userfield);
                $expoptions = explode("\n", $options);
                if (is_array($expoptions)) {
                    foreach ($expoptions as $key => $val) {
                        $checked = '';
                        if ($val == $userfield) {
                            $checked = " checked=\"checked\"";
                        }

                        eval("\$code .= \"" . $templates->get('usercp_profile_profilefields_radio') . "\";");
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
                    foreach ($expoptions as $key => $val) {
                        $checked = '';
                        if (isset($seloptions[$val]) && $val == $seloptions[$val]) {
                            $checked = " checked=\"checked\"";
                        }

                        eval("\$code .= \"" . $templates->get('usercp_profile_profilefields_checkbox') . "\";");
                    }
                }
            } elseif ($type == 'textarea') {
                $value = htmlspecialchars_uni($userfield);
                eval("\$code = \"" . $templates->get('usercp_profile_profilefields_textarea') . "\";");
            } else {
                $value = htmlspecialchars_uni($userfield);
                $maxlength = '';
                if ($profilefield['maxlength'] > 0) {
                    $maxlength = " maxlength=\"{$profilefield['maxlength']}\"";
                }

                eval("\$code = \"" . $templates->get('usercp_profile_profilefields_text') . "\";");
            }

            $plugins->run_hooks('ougc_plugins_customfields_usercp_end');

            if ($profilefield['required'] == 1) {
                eval("\$requiredfields .= \"" . $templates->get('usercp_profile_customfield') . "\";");
            } else {
                eval("\$customfields .= \"" . $templates->get('usercp_profile_customfield') . "\";");
            }
            $altbg = alt_trow();
            $code = '';
            $select = '';
            $val = '';
            $options = '';
            $expoptions = '';
            $useropts = '';
            $seloptions = array();
            /*~~~*/
        }

        if ($requiredfields) {
            $profiecats->output[$category['cid']] .= eval(
            $templates->render(
                'ougcprofiecats_usercp_profile_requiredfields'
            )
            );
        }

        if ($customfields) {
            $profiecats->output[$category['cid']] .= eval($templates->render('usercp_profile_profilefields'));
        }
    }

    !isset($profiecats->backup['lang_profile_required']) or $lang->additional_information = $profiecats->backup['lang_profile_required'];

    !isset($profiecats->backup['lang_additional_information']) or $lang->additional_information = $profiecats->backup['lang_additional_information'];
}

function modcp_editprofile_end()
{
    usercp_profile_end();
}

// Revert cache for validation
function usercp_do_profile_start()
{
    global $cache, $plugins, $profiecats;

    $cache->cache['profilefields'] = $profiecats->cache['original'];
}

function modcp_do_editprofile_start()
{
    usercp_do_profile_start();
}

// Hijack it back after validation
function usercp_profile_start()
{
    global $cache, $profiecats;

    $cache->cache['profilefields'] = $profiecats->cache['modified'];
}

function modcp_editprofile_start()
{
    usercp_profile_start();
}