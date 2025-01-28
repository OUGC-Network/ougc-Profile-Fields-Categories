<?php

/***************************************************************************
 *
 *    ougc Profile Fields Categories plugin (/inc/plugins/ougc_profiecats/hooks/shared.php)
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

namespace ougc\ProfileFieldsCategories\Hooks\Shared;

use userDataHandler;

function datahandler_user_insert09(userDataHandler &$userDataHandler): userDataHandler
{
    global $profiecats;

    if (!empty($profiecats->cache['original'])) {
        global $cache;

        $cache->cache['profilefields'] = $profiecats->cache['original'];
    }

    return $userDataHandler;
}

function datahandler_user_insert_end90(userDataHandler &$userDataHandler): userDataHandler
{
    global $profiecats;

    if (!empty($profiecats->cache['original'])) {
        global $cache;

        $cache->cache['profilefields'] = $profiecats->cache['modified'];
    }

    return $userDataHandler;
}