<?php

/***************************************************************************
 *
 *    OUGC Profile Fields Categories plugin (/inc/languages/english/admin/ougc_profiecats.lang.php)
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

// Plugin API
$l['setting_group_ougc_profiecats'] = 'OUGC Profile Fields Categories';
$l['setting_group_ougc_profiecats_desc'] = 'Allow administrators to create custom profile fields categories.';

// ACP Page
$l['ougc_profiecats_admin_tab'] = 'Categories';
$l['ougc_profiecats_admin_tab_desc'] = 'Manage custom profile fields categories.';
$l['ougc_profiecats_admin_empty'] = 'There are currently no custom profile fields categories to show.';
$l['ougc_profiecats_admin_desc'] = "<br /><b>Templates:</b> <i>usercp_profile</i>, <i>modcp_editprofile</i>, <i>member_profile</i>, <i>postbit</i>, <i>postbit_classic</i>
<br /><b>Variables:</b> {\$GLOBALS['profiecats']->output['{1}']}</small>";
$l['ougc_profiecats_admin_name'] = 'Category Name';
$l['ougc_profiecats_admin_name_desc'] = 'Enter the name of the custom category.';
$l['ougc_profiecats_admin_active'] = 'Enabled?';
$l['ougc_profiecats_admin_active_desc'] = '';
$l['ougc_profiecats_admin_forums'] = 'Display in Forums';
$l['ougc_profiecats_admin_forums_desc'] = 'Select the forums this custom category will be shown in.';
$l['ougc_profiecats_admin_required'] = 'Required?';
$l['ougc_profiecats_admin_required_desc'] = 'Are the fields in this category required to be filled in for users to be able to post in the selected forums? Note that this does not apply if the field is hidden or the field is not editable.';
$l['ougc_profiecats_admin_disporder'] = 'Display Order';
$l['ougc_profiecats_admin_disporder_desc'] = 'Enter the display order for this custom category.';
$l['ougc_profiecats_admin_tab_add'] = 'Add Category';
$l['ougc_profiecats_admin_tab_add_desc'] = 'Add custom profile fields categories.';
$l['ougc_profiecats_admin_tab_edit'] = 'Edit Category';
$l['ougc_profiecats_admin_tab_edit_desc'] = 'Edit custom profile fields categories.';
$l['ougc_profiecats_admin_summit'] = 'Summit';
$l['ougc_profiecats_admin_none'] = 'None';
$l['ougc_profiecats_admin_error_invalid_name'] = 'The entered name is invalid.';
$l['ougc_profiecats_admin_error_invalid_category'] = 'The selected category is invalid.';
$l['ougc_profiecats_admin_success_add'] = 'The category was successfully added.';
$l['ougc_profiecats_admin_success_edit'] = 'The category was successfully edited.';
$l['ougc_profiecats_admin_success_delete'] = 'The category was successfully deleted.';
$l['ougc_profiecats_admin_success_rebuild'] = 'The templates were successfully rebuilt.';
$l['ougc_profiecats_admin_category'] = 'Category';
$l['ougc_profiecats_admin_category_desc'] = 'Select the category this profile field belongs to.';
$l['ougc_profiecats_admin_rebuild'] = 'Rebuild Templates';
$l['ougc_profiecats_pluginlibrary'] = 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later to be uploaded to your forum.';