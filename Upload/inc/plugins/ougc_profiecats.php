<?php

/***************************************************************************
 *
 *	OUGC Profile Fields Categories plugin (/inc/plugins/ougc_profiecats.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2014 Omar Gonzalez
 *
 *	Website: http://omarg.me
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
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.');

// Run/Add Hooks
if(defined('IN_ADMINCP'))
{
	$plugins->add_hook('admin_config_profile_fields_begin', 'ougc_profiecats_admin');
}
else
{
	$plugins->add_hook('global_start', 'ougc_profiecats_global', 9);
	$plugins->add_hook('xmlhttp', 'ougc_profiecats_global', 9);
	$plugins->add_hook('postbit', 'ougc_profiecats_postbit', 10);
	$plugins->add_hook('postbit_prev', 'ougc_profiecats_postbit', 10);
	$plugins->add_hook('postbit_pm', 'ougc_profiecats_postbit', 10);
	$plugins->add_hook('postbit_announcement', 'ougc_profiecats_postbit', 10);
	//$plugins->add_hook('showthread_start', 'ougc_profiecats_showthread');
	//$plugins->add_hook('newthread_start', 'ougc_profiecats_newthread');
	//$plugins->add_hook('newthread_do_newthread_start', 'ougc_profiecats_newthread');
	$plugins->add_hook('usercp_profile_end', 'ougc_profiecats_usercp_profile_end');
	$plugins->add_hook('usercp_do_profile_start', 'ougc_profiecats_revert_cache');
	$plugins->add_hook('modcp_editprofile_end', 'ougc_profiecats_usercp_profile_end');
	$plugins->add_hook('modcp_do_editprofile_start', 'ougc_profiecats_revert_cache');
	$plugins->add_hook('member_profile_end', 'ougc_profiecats_profile_end');


	if(THIS_SCRIPT == 'showthread.php')
	{
		global $templatelist;

		if(!isset($templatelist))
		{
			$templatelist = '';
		}
		else
		{
			$templatelist .= ',';
		}

		$templatelist .= 'ougcprofiecats_postbit, ougcprofiecats_field, ougcprofiecats_multiselect, ougcprofiecats_multiselect_value';
	}
}

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

// Plugin API
function ougc_profiecats_info()
{
	global $lang, $profiecats;
	$profiecats->lang_load();

	return array(
		'name'			=> 'OUGC Profile Fields Categories',
		'description'	=> $lang->setting_group_ougc_profiecats_desc,
		'website'		=> 'http://omarg.me',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '0.1',
		'versioncode'	=> '0100',
		'compatibility'	=> '18*'
	);
}

// _activate() routine
function ougc_profiecats_activate()
{
	global $cache, $PL;
	$PL or require_once PLUGINLIBRARY;

	// Add template group
	$PL->templates('ougcprofiecats', 'OUGC Profile Fields Categories', array(
		/*'postbit'	=> '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder tfixed float_right" style="width: auto; min-width: 400px; margin-left: 10px;">
<tr>
<td colspan="2" class="thead"><strong>{$title}</strong></td>
</tr>

		'postbit'	=> '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder tfixed float_right" style="width: auto; min-width: 400px; margin-left: 10px;">
<tr>
<td colspan="2" class="thead"><strong>{$title}</strong></td>
</tr>

</table>
<br />',
</table>
<br />',
		'field'	=> '<tr>
<td class="{$bgcolor}"><strong>{$post[\'fieldname\']}:</strong></td>
<td class="{$bgcolor} scaleimages">{$post[\'fieldvalue\']}</td>
</tr>',*/
		'postbit'	=> '{$customfields}',
		'field'	=> '<div class="post_head">
	<strong>{$post[\'fieldname\']}:</strong> {$post[\'fieldvalue\']}
</div>',
		'multiselect'	=> '<ul style="margin: 0; padding-left: 15px;">
{$customfield_val}
</ul>',
		'multiselect_value'	=> '<li style="margin-left: 0;">{$val}</li>',
	));

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = ougc_profiecats_info();

	if(!isset($plugins['pages']))
	{
		$plugins['pages'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/

	/*~*~* RUN UPDATES END *~*~*/

	$plugins['pages'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);
}

// _install() routine
function ougc_profiecats_install()
{
	global $db;

	$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_profiecats_categories` (
			`cid` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(100) NOT NULL DEFAULT '',
			`forums` varchar(100) NOT NULL DEFAULT '',
			`disporder` smallint NOT NULL DEFAULT '0',
			`active` tinyint(1) NOT NULL DEFAULT '1',
			`required` tinyint(1) NOT NULL DEFAULT '1',
			PRIMARY KEY (`cid`)
		) ENGINE=MyISAM{$db->build_create_table_collation()};"
	);

	$db->add_column('profilefields', 'cid', 'int NOT NULL DEFAULT \'0\'');
}

// _is_installed() routine
function ougc_profiecats_is_installed()
{
	global $db;

	return $db->table_exists('ougc_profiecats_categories');
}

// _uninstall() routine
function ougc_profiecats_uninstall()
{
	global $db, $cache, $PL;
	$PL or require_once PLUGINLIBRARY;

	// Drop DB entries
	$db->drop_table('ougc_profiecats_categories');
	$db->drop_column('profilefields', 'cid');

	$cache->delete('ougc_profiecats_categories');

	$PL->templates_delete('ougcprofiecats');

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['profiecats']))
	{
		unset($plugins['profiecats']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		$cache->delete('ougc_plugins');
	}
}

// Hijack Tabs
function ougc_profiecats_admin()
{
	global $sub_tabs, $lang, $profiecats;
	$profiecats->lang_load();

	$sub_tabs['ougc_profiecats_admin_tab'] = array(
		'title'			=> $lang->ougc_profiecats_admin_tab,
		'link'			=> 'index.php?module=config-profile_fields&amp;action=categories',
		'description'	=> $lang->ougc_profiecats_admin_tab_desc
	);

	global $mybb, $plugins, $page, $db, $db, $db, $db, $db, $db;

	$plugins->add_hook('admin_formcontainer_end', 'ougc_profiecats_admin_field');
	$plugins->add_hook('admin_config_profile_fields_start', 'ougc_profiecats_admin_hijack');
	$plugins->add_hook('admin_config_profile_fields_edit_commit', 'ougc_profiecats_admin_field_commit');
	$plugins->add_hook('admin_config_profile_fields_add_commit', 'ougc_profiecats_admin_field_commit');

	if($mybb->get_input('action') != 'categories')
	{
		return;
	}

	$sub_tabs['ougc_profiecats_admin_tab_add'] = array(
		'title'			=> $lang->ougc_profiecats_admin_tab_add,
		'link'			=> 'index.php?module=config-profile_fields&amp;action=categories&amp;do=add',
		'description'	=> $lang->ougc_profiecats_admin_tab_add_desc
	);

	if($mybb->get_input('do') == 'edit')
	{
		$sub_tabs['ougc_profiecats_admin_tab_edit'] = array(
			'title'			=> $lang->ougc_profiecats_admin_tab_edit,
			'link'			=> 'index.php?module=config-profile_fields&amp;action=categories&amp;do=edit&amp;cid='.$mybb->get_input('cid', 1),
			'description'	=> $lang->ougc_profiecats_admin_tab_edit_desc
		);
	}

	$sub_tabs['custom_profile_fields'] = array(
		'title'			=> $lang->custom_profile_fields,
		'link'			=> 'index.php?module=config-profile_fields',
		'description'	=> $lang->custom_profile_fields_desc
	);

	$plugins->run_hooks('admin_config_profile_fields_categories_start');

	if($mybb->get_input('do') == 'add' || $mybb->get_input('do') == 'edit')
	{
		$add = $mybb->get_input('do') == 'add';

		$url = ($add ? $sub_tabs['ougc_profiecats_admin_tab_add']['link'] : $sub_tabs['ougc_profiecats_admin_tab_edit']['link']);

		$page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $url);

		if(!$add)
		{
			if(!($category = $profiecats->get_category($mybb->get_input('cid', 1))))
			{
				flash_message($lang->ougc_profiecats_admin_error_invalid_category, 'error');
				admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
			}

			$page->add_breadcrumb_item(htmlspecialchars_uni($category['name']));
		}

		$mergeinput = array();
		foreach(array('name', 'forums', 'active', 'required', 'disporder') as $key)
		{
			$mergeinput[$key] = isset($mybb->input[$key]) ? $mybb->input[$key] : ($add ? '' : $category[$key]);
			if($key == 'forums')
			{
				$profiecats->clean_ints($mergeinput[$key]);
			}
		}
		$mybb->input = array_merge($mybb->input, $mergeinput);

		$page->output_header($sub_tabs['ougc_profiecats_admin_tab_add']['title']);
		$page->output_nav_tabs($sub_tabs, $add ? 'ougc_profiecats_admin_tab_add' : 'ougc_profiecats_admin_tab_edit');

		if($mybb->request_method == 'post')
		{
			$errors = array();
			if(!$mybb->get_input('name') || isset($mybb->input{100}))
			{
				$errors[] = $lang->ougc_profiecats_admin_error_invalid_name;
			}

			if(empty($errors))
			{
				$method = $add ? 'insert_category' : 'update_category';
				$lang_val = $add ? 'ougc_profiecats_admin_success_add' : 'ougc_profiecats_admin_success_edit';

				$profiecats->{$method}(array(
					'name'			=> $mybb->get_input('name'),
					'forums'		=> $mybb->get_input('forums', 2),
					'active'		=> $mybb->get_input('active', 1),
					'required'		=> $mybb->get_input('required', 1),
					'disporder'		=> $mybb->get_input('disporder', 1)
				), $mybb->get_input('cid', 1));

				$profiecats->update_cache();
				$profiecats->log_action();

				flash_message($lang->{$lang_val}, 'success');
				admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
			}
			else
			{
				$page->output_inline_error($errors);
			}
		}

		$form = new Form($url, 'post');
		$form_container = new FormContainer($sub_tabs['ougc_profiecats_admin_tab_'.($add ? 'add' : 'edit')]['title']);

		$form_container->output_row($lang->ougc_profiecats_admin_name.' <em>*</em>', $lang->ougc_profiecats_admin_name_desc, $form->generate_text_box('name', $mybb->get_input('name')));
		$form_container->output_row($lang->ougc_profiecats_admin_forums, $lang->ougc_profiecats_admin_forums_desc, $form->generate_forum_select('forums[]', $mybb->get_input('forums', 2), array('multiple' => true)));
		$form_container->output_row($lang->ougc_profiecats_admin_active, $lang->ougc_profiecats_admin_active_desc, $form->generate_yes_no_radio('active', $mybb->get_input('active', 1)));
		$form_container->output_row($lang->ougc_profiecats_admin_required, $lang->ougc_profiecats_admin_required_desc, $form->generate_yes_no_radio('required', $mybb->get_input('required', 1)));
		$form_container->output_row($lang->ougc_profiecats_admin_disporder, $lang->ougc_profiecats_admin_disporder_desc, $form->generate_text_box('disporder', $mybb->get_input('disporder', 1), array('style' => 'text-align: center; width: 30px;" maxlength="5')));

		$form_container->end();
		$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_profiecats_admin_summit), $form->generate_reset_button($lang->reset)));
		$form->end();
		$page->output_footer();
	}
	elseif($mybb->get_input('do') == 'delete')
	{
		if(!($category = $profiecats->get_category($mybb->get_input('cid', 1))))
		{
			flash_message($lang->ougc_profiecats_admin_error_invalid_category, 'error');
			admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
		}

		if($mybb->request_method == 'post')
		{
			if(!verify_post_check($mybb->input['my_post_key'], true))
			{
				flash_message($lang->invalid_post_verify_key2, 'error');
				admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
			}

			!isset($mybb->input['no']) or admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);

			$profiecats->delete_category($category['cid']);

			$profiecats->update_cache();
			$profiecats->log_action();

			flash_message($lang->ougc_profiecats_admin_success_delete, 'success');
			admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
		}

		$page->output_confirm_action($sub_tabs['ougc_profiecats_admin_tab']['link'].'&amp;do=delete&amp;cid='.$mybb->get_input('cid', 1));
	}
	else
	{
		$page->add_breadcrumb_item($sub_tabs['ougc_profiecats_admin_tab']['title'], $sub_tabs['ougc_profiecats_admin_tab']['link']);
		$page->output_header($sub_tabs['ougc_profiecats_admin_tab']['title']);
		$page->output_nav_tabs($sub_tabs, 'ougc_profiecats_admin_tab');

		$table = new Table;
		$table->construct_header($lang->ougc_profiecats_admin_name, array('width' => '55%'));
		$table->construct_header($lang->ougc_profiecats_admin_active, array('width' => '10%', 'class' => 'align_center'));
		$table->construct_header($lang->ougc_profiecats_admin_required, array('width' => '10%', 'class' => 'align_center'));
		$table->construct_header($lang->ougc_profiecats_admin_disporder, array('width' => '15%', 'class' => 'align_center'));
		$table->construct_header($lang->options, array('width' => '10%', 'class' => 'align_center'));

		isset($mybb->input['limit']) or $mybb->input['limit'] = 20;

		$limit = (int)$mybb->get_input('limit', 1);
		$limit = $limit > 100 ? 100 : ($limit < 1 ? 1 : $limit);

		if($mybb->get_input('page', 1) > 0)
		{
			$start = ($mybb->get_input('page', 1)-1)*$limit;
		}
		else
		{
			$start = 0;
			$mybb->input['page'] = 1;
		}

		$query = $db->simple_select('ougc_profiecats_categories', '*', '', array('limit_start' => $start, 'limit' => $limit, 'order_by' => 'disporder'));
		
		if(!$db->num_rows($query))
		{
			$table->construct_cell('<div align="center">'.$lang->ougc_profiecats_admin_empty.'</div>', array('colspan' => 6));
			$table->construct_row();
			$table->output($sub_tabs['ougc_profiecats_admin_tab']['title']);
		}
		else
		{
			if($mybb->request_method == 'post' && $mybb->get_input('do') == 'updatedisporder')
			{
				foreach($mybb->get_input('disporder', 2) as $cid => $disporder)
				{
					$profiecats->update_category(array('disporder' => $disporder), $cid);
				}
				$profiecats->update_cache();
				admin_redirect($sub_tabs['ougc_profiecats_admin_tab']['link']);
			}

			$form = new Form($sub_tabs['ougc_profiecats_admin_tab']['link'].'&amp;do=updatedisporder', 'post');

			$query2 = $db->simple_select('ougc_profiecats_categories', 'COUNT(cid) AS categories');
			$catscount = (int)$db->fetch_field($query2, 'categories');

			echo draw_admin_pagination($mybb->get_input('page', 1), $limit, $catscount, $sub_tabs['ougc_profiecats_admin_tab']['link'].'&amp;limit='.$limit);

			while($category = $db->fetch_array($query))
			{
				$edit_link = 'index.php?module=config-profile_fields&amp;action=categories&amp;do=edit&amp;cid='.$category['cid'];

				$category['active'] or $category['name'] = '<i>'.$category['name'].'</i>';

				$table->construct_cell('<a href="'.$edit_link.'">'.htmlspecialchars_uni($category['name']).'</a>'.$lang->sprintf($lang->ougc_profiecats_admin_desc, $category['cid']));
				$table->construct_cell('<img src="styles/default/images/icons/bullet_o'.(!$category['active'] ? 'ff' : 'n').'.png" alt="" title="'.(!$category['active'] ? $lang->ougc_awards_form_hidden : $lang->ougc_awards_form_visible).'" />', array('class' => 'align_center'));
				$table->construct_cell('<img src="styles/default/images/icons/bullet_o'.(!$category['required'] ? 'ff' : 'n').'.png" alt="" title="'.(!$category['required'] ? $lang->ougc_awards_form_hidden : $lang->ougc_awards_form_visible).'" />', array('class' => 'align_center'));

				$table->construct_cell($form->generate_text_box('disporder['.$category['cid'].']', (int)$category['disporder'], array('style' => 'text-align: center; width: 30px;')), array('class' => 'align_center'));

				$popup = new PopupMenu("category_{$category['cid']}", $lang->options);
				$popup->add_item($lang->edit, $edit_link);
				$popup->add_item($lang->view, 'index.php?module=config-profile_fields&amp;view_cat='.$category['cid']);
				$popup->add_item($lang->delete, 'index.php?module=config-profile_fields&amp;action=categories&amp;do=delete&amp;cid='.$category['cid']);
				$table->construct_cell($popup->fetch(), array('class' => 'align_center'));

				$table->construct_row();
			}
			$table->output($sub_tabs['ougc_profiecats_admin_tab']['title']);

			$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_profiecats_admin_summit), $form->generate_reset_button($lang->reset)));
			$form->end();
		}
		$page->output_footer();
	}
}

// Add/Edit fields
function ougc_profiecats_admin_field()
{
	global $run_module, $form_container, $lang;

	if($run_module == 'config' && ($form_container->_title == $lang->edit_profile_field || $form_container->_title == $lang->add_new_profile_field))
	{
		global $form, $mybb, $profiecats, $profile_field;
		$profiecats->lang_load();

		if(isset($profile_field['cid']) && $mybb->request_method != 'post')
		{
			$mybb->input['category'] = $profile_field['cid'];
		}

		$checked = (!empty($mybb->input['vgroup_1_vgroups']) ? array('all' => '', 'select' => ' checked="checked"') : array('all' => 'checked="checked"', 'select' => ''));

		$form_container->output_row($lang->ougc_profiecats_admin_category, $lang->ougc_profiecats_admin_category_desc, $profiecats->generate_category_select('category', $mybb->get_input('category', 1)));
	}
}

// Commit changes
function ougc_profiecats_admin_field_commit()
{
	global $mybb, $plugins, $updated_profile_field, $db, $fid;

	if($plugins->current_hook == 'admin_config_profile_fields_add_commit')
	{
		$db->update_query('profilefields', array('cid' => $mybb->get_input('category', 1)), "fid='{$fid}'");
	}
	else
	{
		$updated_profile_field['cid'] = $mybb->get_input('category', 1);
	}
}

// Hijack fields listing
function ougc_profiecats_admin_hijack()
{
	global $mybb;

	if(!$mybb->get_input('view_cat', 1))
	{
		return;
	}

	control_object($GLOBALS['db'], '
		function query($string, $hide_errors=0, $write_query=0)
		{
			if(!$write_query && strpos($string, \'SELECT * FROM \') !== false)
			{
				$string = strtr($string, array(
					\'ORDER\' => \'WHERE cid=\\\''.$mybb->get_input('view_cat', 1).'\\\' ORDER\'
				));
			}

			return parent::query($string, $hide_errors, $write_query);
		}
	');
}

// Break down categorization here
function ougc_profiecats_global()
{
	global $cache, $profiecats;

	$profilefields = $cache->read('profilefields');
	foreach($profilefields as $key => $field)
	{
		if($field['cid'])
		{
			unset($profilefields[$key]);
			$profiecats->cache['profilefields'][$field['cid']][$key] = $field;
		}
	}

	$cache->cache['profilefields'] = $profilefields;
}

// Enable for post bit
function ougc_profiecats_showthread()
{
	/*global $plugins, $cache, $profiecats, $thread;

	$categories = (array)$cache->read('ougc_profiecats_categories');
$done = false;
	foreach($profiecats->cache['profilefields'] as $cid => $custom_fields)
	{
		if(!($category = $categories[$cid]))
		{
			continue;
		}

		if(strpos(','.$category['forums'].',', ','.$thread['fid'].',') !== false && $category['active'])
		{
			$profiecats->lang_load();
$done = true;
			$plugins->add_hook('postbit', 'ougc_profiecats_postbit');
			break;
		}
	}
	_dump($done);*/
}

// Post-bit table
function ougc_profiecats_postbit(&$post)
{
	global $profiecats, $mybb, $parser, $templates, $theme, $lang;

	$categories = (array)$mybb->cache->read('ougc_profiecats_categories');

	foreach($categories as $category)
	{
		if(!is_array($profiecats->cache['profilefields'][$category['cid']]))
		{
			return;
		}

		$profiecats->output[$category['cid']] = '';

		foreach($profiecats->cache['profilefields'][$category['cid']] as $field)
		{
			$fieldfid = "fid{$field['fid']}";
			if(!empty($post[$fieldfid]))
			{
				$fieldfid = "fid{$field['fid']}";
				if(!empty($post[$fieldfid]) && $field['postbit'])
				{
					$post['fieldvalue'] = '';
					$post['fieldname'] = htmlspecialchars_uni($field['name']);

					$thing = explode("\n", $field['type'], "2");
					$type = trim($thing[0]);
					$useropts = explode("\n", $post[$fieldfid]);

					if(is_array($useropts) && ($type == "multiselect" || $type == "checkbox"))
					{
						foreach($useropts as $val)
						{
							if($val != '')
							{
								eval("\$post['fieldvalue_option'] .= \"".$templates->get("postbit_profilefield_multiselect_value")."\";");
							}
						}
						if($post['fieldvalue_option'] != '')
						{
							eval("\$post['fieldvalue'] .= \"".$templates->get("postbit_profilefield_multiselect")."\";");
						}
					}
					else
					{
						$field_parser_options = array(
							"allow_html" => $field['allowhtml'],
							"allow_mycode" => $field['allowmycode'],
							"allow_smilies" => $field['allowsmilies'],
							"allow_imgcode" => $field['allowimgcode'],
							"allow_videocode" => $field['allowvideocode'],
							#"nofollow_on" => 1,
							"filter_badwords" => 1
						);

						if($customfield['type'] == "textarea")
						{
							$field_parser_options['me_username'] = $post['username'];
						}
						else
						{
							$field_parser_options['nl2br'] = 0;
						}

						if($mybb->user['showimages'] != 1 && $mybb->user['uid'] != 0 || $mybb->settings['guestimages'] != 1 && $mybb->user['uid'] == 0)
						{
							$field_parser_options['allow_imgcode'] = 0;
						}

						$post['fieldvalue'] = $parser->parse_message($post[$fieldfid], $field_parser_options);
					}

					eval("\$profiecats->output[{$category['cid']}] .= \"".$templates->get("postbit_profilefield")."\";");
				}
			}
		}
	}
}

// New thread
function ougc_profiecats_newthread()
{
	global $mybb, $profiecats, $fid;

	$categories = (array)$mybb->cache->read('ougc_profiecats_categories');

	$error = false;
	foreach($categories as $category)
	{
		if(empty($profiecats->cache[$category['cid']]))
		{
			continue;
		}

		if(!$category['required'])
		{
			continue;
		}

		if(my_strpos(','.$category['forums'].',', ','.$fid.',') === false)
		{
			continue;
		}

		foreach($profiecats->cache[$category['cid']] as $field)
		{
			if(!$field['required'])
			{
				continue;
			}

			$fid = (int)$field['fid'];
			if(/*isset($mybb->user['fid'.$fid]) && */empty($mybb->user['fid'.$fid]))
			{
				$error = true;
				break 2;
			}
		}
	}

	if(!$error)
	{
		return;
	}

	global $lang, $forum;
	$profiecats->lang_load();

	$foruminfo = & $forum;

	if($foruminfo['rulestype'] && $foruminfo['rules'])
	{
		global $parser, $templates, $theme, $rules;

		if(!is_object($parser))
		{
			require_once MYBB_ROOT.'inc/class_parser.php';
			$parser = new postParser();
		}

		if(!$foruminfo['rulestitle'])
		{
			$foruminfo['rulestitle'] = $lang->sprintf($lang->forum_rules, $foruminfo['name']);
		}

		$parser_options = array(
			'allow_html'		=> 1,
			'allow_mycode'		=> 1,
			'allow_smilies'		=> 1,
			'allow_imgcode'		=> 1,
			'filter_badwords'	=> 1
		);

		$foruminfo['rules'] = $parser->parse_message($foruminfo['rules'], $parser_options);
		if($foruminfo['rulestype'] == 1 || $foruminfo['rulestype'] == 3)
		{
			$rules = eval($templates->render('forumdisplay_rules'));
		}
		elseif($foruminfo['rulestype'] == 2)
		{
			$rules = eval($templates->render('forumdisplay_rules_link'));
		}

		$templates->cache['error'] = str_replace('{$header}', '{$header}{$GLOBALS[\'rules\']}', $templates->cache['error']);
	}

	$lang->ougc_profiecats_newthread_error = $lang->sprintf($lang->ougc_profiecats_newthread_error, htmlspecialchars_uni($field['name']), htmlspecialchars_uni($category['name']), $mybb->settings['bburl']);

	error($lang->ougc_profiecats_newthread_error);
}

// Profile display
function ougc_profiecats_profile_end()
{
	global $mybb, $userfields, $parser, $templates, $theme, $lang, $memprofile, $bgcolor, $profiecats;

	$categories = (array)$mybb->cache->read('ougc_profiecats_categories');

	foreach($categories as $category)
	{
		if(!is_array($profiecats->cache['profilefields'][$category['cid']]))
		{
			return;
		}

		$profiecats->output[$category['cid']] = '';

		$lang->users_additional_info = htmlspecialchars_uni($category['name']);

		$bgcolor = $alttrow = 'trow1';
		$customfields = $profilefields = '';

		foreach($profiecats->cache['profilefields'][$category['cid']] as $customfield)
		{
			if($mybb->usergroup['cancp'] != 1 && $mybb->usergroup['issupermod'] != 1 && $mybb->usergroup['canmodcp'] != 1 && !is_member($customfield['viewableby']) || !$customfield['profile'])
			{
				continue;
			}

			if(function_exists('xt_proffields_inp'))
			{
				$field = "fid{$customfield['fid']}";
	
				if(isset($userfields[$field]))
				{
					$customfieldval = xt_proffields_disp($customfield, $userfields[$field]);
				}

				if($customfieldval)
				{
					$customfield['name'] = htmlspecialchars_uni($customfield['name']);

					eval('$customfields .= "'.$templates->get('member_profile_customfields_field').'";');

					$bgcolor = alt_trow();
				}
				continue;
			}

			$thing = explode("\n", $customfield['type'], "2");
			$type = trim($thing[0]);

			$customfieldval = $customfield_val = '';
			$field = "fid{$customfield['fid']}";

			if(isset($userfields[$field]))
			{
				$useropts = explode("\n", $userfields[$field]);
				$customfieldval = $comma = '';
				if(is_array($useropts) && ($type == "multiselect" || $type == "checkbox"))
				{
					foreach($useropts as $val)
					{
						if($val != '')
						{
							eval("\$customfield_val .= \"".$templates->get("member_profile_customfields_field_multi_item")."\";");
						}
					}
					if($customfield_val != '')
					{
						eval("\$customfieldval = \"".$templates->get("member_profile_customfields_field_multi")."\";");
					}
				}
				else
				{
					$parser_options = array(
						"allow_html" => $customfield['allowhtml'],
						"allow_mycode" => $customfield['allowmycode'],
						"allow_smilies" => $customfield['allowsmilies'],
						"allow_imgcode" => $customfield['allowimgcode'],
						"allow_videocode" => $customfield['allowvideocode'],
						#"nofollow_on" => 1,
						"filter_badwords" => 1
					);

					if($customfield['type'] == "textarea")
					{
						$parser_options['me_username'] = $memprofile['username'];
					}
					else
					{
						$parser_options['nl2br'] = 0;
					}

					if($mybb->user['showimages'] != 1 && $mybb->user['uid'] != 0 || $mybb->settings['guestimages'] != 1 && $mybb->user['uid'] == 0)
					{
						$parser_options['allow_imgcode'] = 0;
					}

					$customfieldval = $parser->parse_message($userfields[$field], $parser_options);
				}
			}

			if($customfieldval)
			{
				$customfield['name'] = htmlspecialchars_uni($customfield['name']);
				eval("\$customfields .= \"".$templates->get("member_profile_customfields_field")."\";");
				$bgcolor = alt_trow();
			}
		}

		if($customfields)
		{
			eval("\$profilefields = \"".$templates->get("member_profile_customfields")."\";");
		}

		$profiecats->output[$category['cid']] = $profilefields;
	}
	//_dump($profiecats->cache['profilefields'][2],$profiecats->output[2]);
}

// UCP Display
function ougc_profiecats_usercp_profile_end()
{
	global $mybb, $userfields, $parser, $templates, $theme, $lang, $memprofile, $bgcolor, $user, $errors, $profiecats, $xtpf_inp;

	$profiecats->backup['lang_additional_information'] = $lang->additional_information;

	$categories = (array)$mybb->cache->read('ougc_profiecats_categories');

	$profiecats->lang_load();

	is_array($xtpf_inp) or $xtpf_inp = array();

	// Most of this code belongs to MYBB::usercp.php Lines #516 ~ #708
	foreach($categories as $category)
	{
		if(!is_array($profiecats->cache['profilefields'][$category['cid']]))
		{
			return;
		}

		$profiecats->output[$category['cid']] = '';

		$lang->additional_information = htmlspecialchars_uni($category['name']);

		$requiredfields = $customfields = '';

		$altbg = 'trow1';

		foreach($profiecats->cache['profilefields'][$category['cid']] as $profilefield)
		{
			if(!is_member($profilefield['editableby']) || ($profilefield['postnum'] && $profilefield['postnum'] > $mybb->user['postnum']))
			{
				continue;
			}

			if(function_exists('xt_proffields_inp'))
			{
				$code = '';
				$code = xt_proffields_inp($profilefield, $mybb->user, $errors, $vars);

				if(!$profilefield['xt_proffields_cinp'])
				{
					if($profilefield['required'] == 1)
					{
						eval("\$requiredfields .= \"".$templates->get("usercp_profile_customfield")."\";");
					}
					else
					{
						eval("\$customfields .= \"".$templates->get("usercp_profile_customfield")."\";");
					}
				}
				else
				{
					$xtpf_inp['fid'.$profilefield['fid']] = xt_proffields_cinp($profilefield, $vars);
				}

				continue;
			}

			$profilefield['type'] = htmlspecialchars_uni($profilefield['type']);
			$profilefield['name'] = htmlspecialchars_uni($profilefield['name']);
			$profilefield['description'] = htmlspecialchars_uni($profilefield['description']);
			$thing = explode("\n", $profilefield['type'], "2");
			$type = $thing[0];
			if(isset($thing[1]))
			{
				$options = $thing[1];
			}
			else
			{
				$options = array();
			}
			$field = "fid{$profilefield['fid']}";
			$select = '';
			if($errors)
			{
				if(!isset($mybb->input['profile_fields'][$field]))
				{
					$mybb->input['profile_fields'][$field] = '';
				}
				$userfield = $mybb->input['profile_fields'][$field];
			}
			else
			{
				$userfield = $user[$field];
			}
			if($type == "multiselect")
			{
				if($errors)
				{
					$useropts = $userfield;
				}
				else
				{
					$useropts = explode("\n", $userfield);
				}
				if(is_array($useropts))
				{
					foreach($useropts as $key => $val)
					{
						$val = htmlspecialchars_uni($val);
						$seloptions[$val] = $val;
					}
				}
				$expoptions = explode("\n", $options);
				if(is_array($expoptions))
				{
					foreach($expoptions as $key => $val)
					{
						$val = trim($val);
						$val = str_replace("\n", "\\n", $val);

						$sel = "";
						if(isset($seloptions[$val]) && $val == $seloptions[$val])
						{
							$sel = " selected=\"selected\"";
						}

						eval("\$select .= \"".$templates->get("usercp_profile_profilefields_select_option")."\";");
					}
					if(!$profilefield['length'])
					{
						$profilefield['length'] = 3;
					}

					eval("\$code = \"".$templates->get("usercp_profile_profilefields_multiselect")."\";");
				}
			}
			elseif($type == "select")
			{
				$expoptions = explode("\n", $options);
				if(is_array($expoptions))
				{
					foreach($expoptions as $key => $val)
					{
						$val = trim($val);
						$val = str_replace("\n", "\\n", $val);
						$sel = "";
						if($val == htmlspecialchars_uni($userfield))
						{
							$sel = " selected=\"selected\"";
						}

						eval("\$select .= \"".$templates->get("usercp_profile_profilefields_select_option")."\";");
					}
					if(!$profilefield['length'])
					{
						$profilefield['length'] = 1;
					}

					eval("\$code = \"".$templates->get("usercp_profile_profilefields_select")."\";");
				}
			}
			elseif($type == "radio")
			{
				$userfield = htmlspecialchars_uni($userfield);
				$expoptions = explode("\n", $options);
				if(is_array($expoptions))
				{
					foreach($expoptions as $key => $val)
					{
						$checked = "";
						if($val == $userfield)
						{
							$checked = " checked=\"checked\"";
						}

						eval("\$code .= \"".$templates->get("usercp_profile_profilefields_radio")."\";");
					}
				}
			}
			elseif($type == "checkbox")
			{
				$userfield = htmlspecialchars_uni($userfield);
				if($errors)
				{
					$useropts = $userfield;
				}
				else
				{
					$useropts = explode("\n", $userfield);
				}
				if(is_array($useropts))
				{
					foreach($useropts as $key => $val)
					{
						$seloptions[$val] = $val;
					}
				}
				$expoptions = explode("\n", $options);
				if(is_array($expoptions))
				{
					foreach($expoptions as $key => $val)
					{
						$checked = "";
						if(isset($seloptions[$val]) && $val == $seloptions[$val])
						{
							$checked = " checked=\"checked\"";
						}

						eval("\$code .= \"".$templates->get("usercp_profile_profilefields_checkbox")."\";");
					}
				}
			}
			elseif($type == "textarea")
			{
				$value = htmlspecialchars_uni($userfield);
				eval("\$code = \"".$templates->get("usercp_profile_profilefields_textarea")."\";");
			}
			else
			{
				$value = htmlspecialchars_uni($userfield);
				$maxlength = "";
				if($profilefield['maxlength'] > 0)
				{
					$maxlength = " maxlength=\"{$profilefield['maxlength']}\"";
				}

				eval("\$code = \"".$templates->get("usercp_profile_profilefields_text")."\";");
			}

			if($profilefield['required'] == 1)
			{
				eval("\$requiredfields .= \"".$templates->get("usercp_profile_customfield")."\";");
			}
			else
			{
				eval("\$customfields .= \"".$templates->get("usercp_profile_customfield")."\";");
			}
			$altbg = alt_trow();
			$code = "";
			$select = "";
			$val = "";
			$options = "";
			$expoptions = "";
			$useropts = "";
			$seloptions = array();
		}

		/*if($requiredfields)
		{
			$requiredfields = '<tr><td class="tcat">Required:</td></tr>'.$requiredfields;

			if($customfields)
			{
				$customfields = '<tr><td class="tcat">Optional:</td></tr>'.$customfields;
			}

			$customfields = $requiredfields.$customfields;
		}*/

		$customfields = $requiredfields.$customfields;

		if($customfields)
		{
			$profiecats->output[$category['cid']] = eval($templates->render('usercp_profile_profilefields'));
		}
	}

	!isset($profiecats->backup['lang_additional_information']) or $lang->additional_information = $profiecats->backup['lang_additional_information'];
}

// Revert cache for validation
function ougc_profiecats_revert_cache(&$dh)
{
	global $profiecats, $cache, $plugins;

	$cache->cache['profilefields'] = $profiecats->cache['original'];

	$plugins->add_hook('usercp_profile_start', 'ougc_profiecats_revert_cache_revert');
	$plugins->add_hook('modcp_editprofile_start', 'ougc_profiecats_revert_cache_revert');
}

// Hijack it back after validation
function ougc_profiecats_revert_cache_revert(&$dh)
{
	global $profiecats, $cache;

	$cache->cache['profilefields'] = $profiecats->cache['modified'];
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
class OUGC_PROFIECATS
{
	// Build the class
	function __construct()
	{
	}

	// Loads language strings
	function lang_load()
	{
		global $lang;

		isset($lang->setting_group_ougc_profiecats) or $lang->load(defined('IN_ADMINCP') ? 'ougc_profiecats' : 'admin/ougc_profiecats');
	}

	// Log admin action
	function log_action()
	{
		$data = array();

		if($this->cid)
		{
			$data['cid'] = $this->cid;
		}

		log_admin_action($data);
	}

	// Update the cache
	function update_cache()
	{
		global $db, $cache;

		$d = array();

		$query = $db->simple_select('ougc_profiecats_categories', '*', '', array('order_by' => 'disporder'));
		while($category = $db->fetch_array($query))
		{
			$d[$category['cid']] = $category;
		}

		$cache->update('ougc_profiecats_categories', $d);
	}

	// Clean input
	function clean_ints(&$val, $implode=false)
	{
		if(!is_array($val))
		{
			$val = (array)explode(',', (string)$val);
		}

		foreach($val as $k => &$v)
		{
			$v = (int)$v;
		}

		$val = array_filter($val);

		if($implode)
		{
			$val = (string)implode(',', $val);
		}

		return $val;
	}

	// Insert a new rate to the DB
	function insert_category($data, $cid=null, $update=false)
	{
		global $db;

		$cleandata = array();

		!isset($data['name']) or $cleandata['name'] = $db->escape_string($data['name']);
		!isset($data['forums']) or $cleandata['forums'] = $db->escape_string($this->clean_ints($data['forums'], true));
		!isset($data['active']) or $cleandata['active'] = (int)$data['active'];
		!isset($data['required']) or $cleandata['required'] = (int)$data['required'];
		!isset($data['disporder']) or $cleandata['disporder'] = (int)$data['disporder'];

		if($update)
		{
			$this->cid = (int)$cid;
			$db->update_query('ougc_profiecats_categories', $cleandata, 'cid=\''.$this->cid.'\'');
		}
		else
		{
			$this->cid = (int)$db->insert_query('ougc_profiecats_categories', $cleandata);
		}

		return true;
	}

	// Update espesific rate
	function update_category($data, $cid)
	{
		$this->insert_category($data, $cid, true);
	}

	// Completely delete a category from the DB
	function delete_category($cid)
	{
		global $db;
		$this->cid = (int)$cid;

		$db->update_query('profilefields', array('cid' => 0), 'cid=\''.$this->cid.'\'');
		$db->delete_query('ougc_profiecats_categories', 'cid=\''.$this->cid.'\'');
	}

	// Get a award from the DB
	function get_category($cid)
	{
		global $db;

		$query = $db->simple_select('ougc_profiecats_categories', '*', 'cid=\''.(int)$cid.'\'');
		return $db->fetch_array($query);
	}

	// Generate a categories selection box.
	function generate_category_select($name, $selected)
	{
		global $db, $lang;
		$this->lang_load();

		$selected = (int)$selected;

		$select = "<select name=\"{$name}\">\n";

		$select_add = '';
		if($selected == 0)
		{
			$select_add = ' selected="selected"';
		}
		$select .= "<option value=\"0\"{$select_add}>{$lang->ougc_profiecats_admin_none}</option>\n";

		$query = $db->simple_select('ougc_profiecats_categories', '*', '', array('order_by' => 'name'));
		while($category = $db->fetch_array($query))
		{
			$select_add = '';
			if($selected == $category['cid'])
			{
				$select_add = ' selected="selected"';
			}
	
			$category['name'] = htmlspecialchars_uni($category['name']);
			$select .= "<option value=\"{$category['cid']}\"{$select_add}>{$category['name']}</option>\n";
		}

		$select .= "</select>\n";

		return $select;
	}
}

$GLOBALS['profiecats'] = new OUGC_PROFIECATS;