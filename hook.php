<?php

/**
 * -------------------------------------------------------------------------
 * CostsFix plugin for GLPI
 * Based on Costs plugin by TICGAL Team
 * Customized for Pellissari by Ampris
 *
 * https://github.com/O-Ampris/costsfix
 * -------------------------------------------------------------------------
 * LICENSE
 *
 * This file is part of the CostsFix plugin.
 *
 * CostsFix plugin is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * CostsFix plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CostsFix. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @package   CostsFix
 * @author    Ampris (based on TICGAL team work)
 * @copyright Copyright (C) 2024-2026 Ampris
 * @license   AGPL License 3.0 or (at your option) any later version
 *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      https://github.com/O-Ampris/costsfix
 * @since     2024
 * -------------------------------------------------------------------------
 */

/**
 * plugin_costsfix_install
 *
 * @return bool
 */
function plugin_costsfix_install(): bool
{
    $migration = new Migration(PLUGIN_COSTSFIX_VERSION);

    // Parse inc directory
    foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
        // Load *.class.php files and get the class name
        if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
            $classname = 'PluginCostsfix' . ucfirst($matches[1]);
            include_once($filepath);
            // If the install method exists, load it
            if (method_exists($classname, 'install')) {
                $classname::install($migration);
            }
        }
    }
    return true;
}

/**
 * plugin_costsfix_uninstall
 *
 * @return bool
 */
function plugin_costsfix_uninstall(): bool
{
    $migration = new Migration(PLUGIN_COSTSFIX_VERSION);

    // Parse inc directory
    foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
        // Load *.class.php files and get the class name
        if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
            $classname = 'PluginCostsfix' . ucfirst($matches[1]);
            include_once($filepath);
            // If the install method exists, load it
            if (method_exists($classname, 'uninstall')) {
                $classname::uninstall($migration);
            }
        }
    }
    return true;
}

/**
 * plugin_costsfix_getAddSearchOptions
 *
 * @param  mixed $itemtype
 * @return array
 */
function plugin_costsfix_getAddSearchOptions($itemtype): array
{
    if ($itemtype == Ticket::getType()) {
        return PluginCostsfixTicket::rawSearchOptionsToAdd();
    }

    return [];
}
