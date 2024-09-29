<?php

/***************************************************************************
 *
 *    OUGC Profile Fields Categories plugin (/inc/plugins/ougc_profiecats/admin_hooks.php)
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

namespace ougc\ProfileFieldsCategories\AdminHooks;

use Form;
use FormContainer;
use MyBB;

use PopupMenu;

use Table;

use function ougc\ProfileFieldsCategories\Core\clean_ints;
use function ougc\ProfileFieldsCategories\Core\controlProfileFieldsCache;
use function ougc\ProfileFieldsCategories\Core\delete_category;
use function ougc\ProfileFieldsCategories\Core\generate_category_select;
use function ougc\ProfileFieldsCategories\Core\get_category;
use function ougc\ProfileFieldsCategories\Core\insert_category;
use function ougc\ProfileFieldsCategories\Core\load_language;
use function ougc\ProfileFieldsCategories\Core\log_action;
use function ougc\ProfileFieldsCategories\Core\update_cache;
use function ougc\ProfileFieldsCategories\Core\update_category;

function admin_load()
{
    controlProfileFieldsCache();
}

function admin_config_profile_fields_begin(): bool
{
    global $sub_tabs, $lang;
    load_language();

    $sub_tabs['ougc_profiecats_admin_tab'] = [
        'title' => $lang->ougc_profiecats_admin_tab,
        'link' => 'index.php?module=config-profile_fields&amp;action=categories',
        'description' => $lang->ougc_profiecats_admin_tab_desc
    ];

    global $mybb, $plugins, $page, $db;

    if ($mybb->get_input('action') != 'categories') {
        return false;
    }

    $sub_tabs['ougc_profiecats_admin_tab_add'] = [
        'title' => $lang->ougc_profiecats_admin_tab_add,
        'link' => 'index.php?module=config-profile_fields&amp;action=categories&amp;do=add',
        'description' => $lang->ougc_profiecats_admin_tab_add_desc
    ];

    if ($mybb->get_input('do') == 'edit') {
        $sub_tabs['ougc_profiecats_admin_tab_edit'] = [
            'title' => $lang->ougc_profiecats_admin_tab_edit,
            'link' => 'index.php?module=config-profile_fields&amp;action=categories&amp;do=edit&amp;cid=' . $mybb->get_input(
                    'cid',
                    MyBB::INPUT_INT
                ),
            'description' => $lang->ougc_profiecats_admin_tab_edit_desc
        ];
    }

    $sub_tabs['custom_profile_fields'] = [
        'title' => $lang->custom_profile_fields,
        'link' => 'index.php?module=config-profile_fields',
        'description' => $lang->custom_profile_fields_desc
    ];

    $plugins->run_hooks('admin_config_profile_fields_categories_start');

    if ($mybb->get_input('do') == 'add' || $mybb->get_input('do') == 'edit') {
        $add = $mybb->get_input('do') == 'add';

        $url = ($add ? $sub_tabs['ougc_profiecats_admin_tab_add']['link'] : $sub_tabs['ougc_profiecats_admin_tab_edit']['link']);

        $page->add_breadcrumb_item($lang->ougc_profiecats_admin_tab, $url);

        if (!$add) {
            if (!($category = get_category($mybb->get_input('cid', MyBB::INPUT_INT)))) {
                flash_message($lang->ougc_profiecats_admin_error_invalid_category, 'error');
                admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
            }

            $page->add_breadcrumb_item(htmlspecialchars_uni($category['name']));
        }

        $mergeinput = [];
        foreach (['name', 'forums', 'active', 'required', 'disporder'] as $key) {
            $mergeinput[$key] = $mybb->input[$key] ?? ($add ? '' : $category[$key]);
            if ($key == 'forums') {
                clean_ints(is_array($mergeinput[$key]) ? $mergeinput[$key] : explode(',', $mergeinput[$key]));
            }
        }
        $mybb->input = array_merge($mybb->input, $mergeinput);

        $page->output_header($sub_tabs['ougc_profiecats_admin_tab_add']['title']);
        $page->output_nav_tabs($sub_tabs, $add ? 'ougc_profiecats_admin_tab_add' : 'ougc_profiecats_admin_tab_edit');

        if ($mybb->request_method == 'post') {
            $errors = [];
            if (!$mybb->get_input('name') || isset($mybb->input{100})) {
                $errors[] = $lang->ougc_profiecats_admin_error_invalid_name;
            }

            if (empty($errors)) {
                $lang_val = $add ? 'ougc_profiecats_admin_success_add' : 'ougc_profiecats_admin_success_edit';

                if ($add) {
                    insert_category([
                        'name' => $mybb->get_input('name'),
                        'forums' => $mybb->get_input('forums', MyBB::INPUT_ARRAY),
                        'active' => $mybb->get_input('active', MyBB::INPUT_INT),
                        'required' => $mybb->get_input('required', MyBB::INPUT_INT),
                        'disporder' => $mybb->get_input('disporder', MyBB::INPUT_INT)
                    ], $mybb->get_input('cid', MyBB::INPUT_INT));
                } else {
                    update_category([
                        'name' => $mybb->get_input('name'),
                        'forums' => $mybb->get_input('forums', MyBB::INPUT_ARRAY),
                        'active' => $mybb->get_input('active', MyBB::INPUT_INT),
                        'required' => $mybb->get_input('required', MyBB::INPUT_INT),
                        'disporder' => $mybb->get_input('disporder', MyBB::INPUT_INT)
                    ], $mybb->get_input('cid', MyBB::INPUT_INT));
                }

                update_cache();
                log_action();

                flash_message($lang->{$lang_val}, 'success');
                admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
            } else {
                $page->output_inline_error($errors);
            }
        }

        $form = new Form($url, 'post');
        $form_container = new FormContainer($sub_tabs['ougc_profiecats_admin_tab_' . ($add ? 'add' : 'edit')]['title']);

        $form_container->output_row(
            $lang->ougc_profiecats_admin_name . ' <em>*</em>',
            $lang->ougc_profiecats_admin_name_desc,
            $form->generate_text_box('name', $mybb->get_input('name'))
        );
        $form_container->output_row(
            $lang->ougc_profiecats_admin_forums,
            $lang->ougc_profiecats_admin_forums_desc,
            $form->generate_forum_select(
                'forums[]',
                $mybb->get_input('forums', MyBB::INPUT_ARRAY),
                ['multiple' => true, 'size' => 5, 'main_option' => $lang->all_forums]
            )
        );
        $form_container->output_row(
            $lang->ougc_profiecats_admin_active,
            $lang->ougc_profiecats_admin_active_desc,
            $form->generate_yes_no_radio('active', $mybb->get_input('active', MyBB::INPUT_INT))
        );
        $form_container->output_row(
            $lang->ougc_profiecats_admin_required,
            $lang->ougc_profiecats_admin_required_desc,
            $form->generate_yes_no_radio('required', $mybb->get_input('required', MyBB::INPUT_INT))
        );
        $form_container->output_row(
            $lang->ougc_profiecats_admin_disporder,
            $lang->ougc_profiecats_admin_disporder_desc,
            $form->generate_text_box(
                'disporder',
                $mybb->get_input('disporder', MyBB::INPUT_INT),
                ['style' => 'text-align: center; width: 30px;" maxlength="5']
            )
        );

        $form_container->end();
        $form->output_submit_wrapper(
            [
                $form->generate_submit_button($lang->ougc_profiecats_admin_summit),
                $form->generate_reset_button($lang->reset)
            ]
        );
        $form->end();
        $page->output_footer();
    } elseif ($mybb->get_input('do') == 'rebuildcatmpl') {
        if (!($category = get_category($mybb->get_input('cid', MyBB::INPUT_INT)))) {
            flash_message($lang->ougc_profiecats_admin_error_invalid_category, 'error');
            admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
        }

        if ($mybb->request_method == 'post') {
            if (!verify_post_check($mybb->input['my_post_key'], true)) {
                flash_message($lang->invalid_post_verify_key2, 'error');
                admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
            }

            !isset($mybb->input['no']) || admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);

            $templates = [
                'usercp_profile' => '{$customfields}',
                'modcp_editprofile' => '{$customfields}',
                'member_profile' => '{$profilefields}',
                'postbit' => '{$post[\'user_details\']}',
                'postbit_classic' => '{$post[\'user_details\']}',
            ];

            $variable = "{\$GLOBALS['profiecats']->output['{$category['cid']}']}";

            require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';

            foreach ($templates as $name => $search) {
                find_replace_templatesets($name, '#' . preg_quote($variable) . '#i', '', 0);

                find_replace_templatesets($name, '#' . preg_quote($search) . '#', "{$search}{$variable}");
            }

            flash_message($lang->ougc_profiecats_admin_success_rebuild, 'success');
            admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
        }

        $page->output_confirm_action(
            $sub_tabs['ougc_profiecats_admin_tab']['link'] . '&amp;do=rebuildcatmpl&amp;cid=' . $mybb->get_input(
                'cid',
                MyBB::INPUT_INT
            )
        );
    } elseif ($mybb->get_input('do') == 'delete') {
        if (!($category = get_category($mybb->get_input('cid', MyBB::INPUT_INT)))) {
            flash_message($lang->ougc_profiecats_admin_error_invalid_category, 'error');
            admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
        }

        if ($mybb->request_method == 'post') {
            if (!verify_post_check($mybb->input['my_post_key'], true)) {
                flash_message($lang->invalid_post_verify_key2, 'error');
                admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
            }

            !isset($mybb->input['no']) || admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);

            delete_category($category['cid']);

            update_cache();
            log_action();

            flash_message($lang->ougc_profiecats_admin_success_delete, 'success');
            admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
        }

        $page->output_confirm_action(
            $sub_tabs['ougc_profiecats_admin_tab']['link'] . '&amp;do=delete&amp;cid=' . $mybb->get_input(
                'cid',
                MyBB::INPUT_INT
            )
        );
    } else {
        $page->add_breadcrumb_item(
            $sub_tabs['ougc_profiecats_admin_tab']['title'],
            $sub_tabs['ougc_profiecats_admin_tab']['link']
        );
        $page->output_header($sub_tabs['ougc_profiecats_admin_tab']['title']);
        $page->output_nav_tabs($sub_tabs, 'ougc_profiecats_admin_tab');

        $table = new Table();
        $table->construct_header($lang->ougc_profiecats_admin_name, ['width' => '55%']);
        $table->construct_header($lang->ougc_profiecats_admin_active, ['width' => '10%', 'class' => 'align_center']
        );
        $table->construct_header(
            $lang->ougc_profiecats_admin_required,
            ['width' => '10%', 'class' => 'align_center']
        );
        $table->construct_header(
            $lang->ougc_profiecats_admin_disporder,
            ['width' => '15%', 'class' => 'align_center']
        );
        $table->construct_header($lang->options, ['width' => '10%', 'class' => 'align_center']);

        isset($mybb->input['limit']) || $mybb->input['limit'] = 20;

        $limit = (int)$mybb->get_input('limit', MyBB::INPUT_INT);
        $limit = $limit > 100 ? 100 : ($limit < 1 ? 1 : $limit);

        if ($mybb->get_input('page', MyBB::INPUT_INT) > 0) {
            $start = ($mybb->get_input('page', MyBB::INPUT_INT) - 1) * $limit;
        } else {
            $start = 0;
            $mybb->input['page'] = 1;
        }

        $query = $db->simple_select(
            'ougc_profiecats_categories',
            '*',
            '',
            ['limit_start' => $start, 'limit' => $limit, 'order_by' => 'disporder']
        );

        if (!$db->num_rows($query)) {
            $table->construct_cell(
                '<div align="center">' . $lang->ougc_profiecats_admin_empty . '</div>',
                ['colspan' => 6]
            );
            $table->construct_row();
            $table->output($sub_tabs['ougc_profiecats_admin_tab']['title']);
        } else {
            if ($mybb->request_method == 'post' && $mybb->get_input('do') == 'updatedisporder') {
                foreach ($mybb->get_input('disporder', MyBB::INPUT_ARRAY) as $cid => $disporder) {
                    update_category(['disporder' => $disporder], $cid);
                }
                update_cache();
                admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
            }

            $form = new Form($sub_tabs['ougc_profiecats_admin_tab']['link'] . '&amp;do=updatedisporder', 'post');

            $query2 = $db->simple_select('ougc_profiecats_categories', 'COUNT(cid) AS categories');
            $catscount = (int)$db->fetch_field($query2, 'categories');

            echo draw_admin_pagination(
                $mybb->get_input('page', MyBB::INPUT_INT),
                $limit,
                $catscount,
                $sub_tabs['ougc_profiecats_admin_tab']['link'] . '&amp;limit=' . $limit
            );

            while ($category = $db->fetch_array($query)) {
                $edit_link = 'index.php?module=config-profile_fields&amp;action=categories&amp;do=edit&amp;cid=' . $category['cid'];

                $category['name'] = htmlspecialchars_uni($category['name']);

                $category['active'] || $category['name'] = '<i>' . $category['name'] . '</i>';

                $table->construct_cell(
                    '<a href="' . $edit_link . '">' . $category['name'] . '</a>' . $lang->sprintf(
                        $lang->ougc_profiecats_admin_desc,
                        $category['cid']
                    )
                );
                $table->construct_cell(
                    '<img src="styles/default/images/icons/bullet_o' . (!$category['active'] ? 'ff' : 'n') . '.png" alt="" title="" />',
                    ['class' => 'align_center']
                );
                $table->construct_cell(
                    '<img src="styles/default/images/icons/bullet_o' . (!$category['required'] ? 'ff' : 'n') . '.png" alt="" title="" />',
                    ['class' => 'align_center']
                );

                $table->construct_cell(
                    $form->generate_text_box(
                        'disporder[' . $category['cid'] . ']',
                        (int)$category['disporder'],
                        ['style' => 'text-align: center; width: 30px;']
                    ),
                    ['class' => 'align_center']
                );

                $popup = new PopupMenu("category_{$category['cid']}", $lang->options);
                $popup->add_item($lang->edit, $edit_link);
                $popup->add_item(
                    $lang->ougc_profiecats_admin_rebuild,
                    'index.php?module=config-profile_fields&amp;action=categories&amp;do=rebuildcatmpl&amp;cid=' . $category['cid']
                );
                $popup->add_item(
                    $lang->view,
                    'index.php?module=config-profile_fields&amp;view_cat=' . $category['cid']
                );
                $popup->add_item(
                    $lang->delete,
                    'index.php?module=config-profile_fields&amp;action=categories&amp;do=delete&amp;cid=' . $category['cid']
                );
                $table->construct_cell($popup->fetch(), ['class' => 'align_center']);

                $table->construct_row();
            }
            $table->output($sub_tabs['ougc_profiecats_admin_tab']['title']);

            $form->output_submit_wrapper(
                [
                    $form->generate_submit_button($lang->ougc_profiecats_admin_summit),
                    $form->generate_reset_button($lang->reset)
                ]
            );
            $form->end();
        }
        $page->output_footer();
    }

    return true;
}

// Add/Edit fields
function admin_formcontainer_end(array &$hook_arguments): array
{
    global $run_module, $lang;

    if (
        $run_module == 'config' &&
        !empty($hook_arguments['this']->_title) &&
        (
            !empty($lang->edit_profile_field) &&
            $hook_arguments['this']->_title == $lang->edit_profile_field || !empty($lang->add_new_profile_field) &&
            $hook_arguments['this']->_title == $lang->add_new_profile_field
        )
    ) {
        global $mybb, $profile_field;

        load_language();

        if (isset($profile_field['cid']) && $mybb->request_method != 'post') {
            $mybb->input['category'] = $profile_field['cid'];
        }

        $checked = (!empty($mybb->input['vgroup_1_vgroups']) ? [
            'all' => '',
            'select' => ' checked="checked"'
        ] : ['all' => 'checked="checked"', 'select' => '']);

        $hook_arguments['this']->output_row(
            $lang->ougc_profiecats_admin_category,
            $lang->ougc_profiecats_admin_category_desc,
            generate_category_select('category', $mybb->get_input('category', MyBB::INPUT_INT))
        );
    }

    return $hook_arguments;
}

// Commit changes
function admin_config_profile_fields_edit_commit(): bool
{
    global $mybb, $plugins, $updated_profile_field, $db, $fid;

    if ($plugins->current_hook == 'admin_config_profile_fields_add_commit') {
        $db->update_query(
            'profilefields',
            ['cid' => $mybb->get_input('category', MyBB::INPUT_INT)],
            "fid='{$fid}'"
        );
    } else {
        $updated_profile_field['cid'] = $mybb->get_input('category', MyBB::INPUT_INT);
    }

    return true;
}

function admin_config_profile_fields_add_commit(): bool
{
    admin_config_profile_fields_edit_commit();

    return true;
}

// Hijack fields listing
function admin_config_profile_fields_start(): bool
{
    global $mybb;

    if (!$mybb->get_input('view_cat', MyBB::INPUT_INT)) {
        return false;
    }

    control_db(
        '
		function query($string, $hide_errors=0, $write_query=0)
		{
			if(!$write_query && strpos($string, \'SELECT * FROM \') !== false)
			{
				$string = strtr($string, array(
					\'ORDER\' => \'WHERE cid=\\\'' . $mybb->get_input('view_cat', MyBB::INPUT_INT) . '\\\' ORDER\'
				));
			}

			return parent::query($string, $hide_errors, $write_query);
		}
	'
    );

    return true;
}