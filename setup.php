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

use Glpi\Plugin\Hooks;

define('PLUGIN_COSTSFIX_VERSION', '4.0.0');
// Minimal GLPI version, inclusive
define("PLUGIN_COSTSFIX_MIN_GLPI", "11.0");
// Maximum GLPI version, exclusive
define("PLUGIN_COSTSFIX_MAX_GLPI", "11.9");
define("PLUGIN_COSTSFIX_ICON", "fa-solid fa-money-bill-wave");

/** @var array $CFG_GLPI */
global $CFG_GLPI;
if (!defined('PLUGIN_COSTSFIX_NUMBER_STEP')) {
    define("PLUGIN_COSTSFIX_NUMBER_STEP", 1 / pow(1, $CFG_GLPI["decimal_number"]));
}

function plugin_version_costsfix()
{
    return [
        'name'           => 'CostsFix - Pellissari',
        'version'        => PLUGIN_COSTSFIX_VERSION,
        'author'         => '<a href="https://github.com/O-Ampris">Ampris</a> (based on <a href="https://tic.gal">TICGAL</a>)',
        'homepage'       => 'https://github.com/O-Ampris/costsfix',
        'license'        => 'GPLv3+',
        'requirements'   => [
            'glpi'   => [
                'min' => PLUGIN_COSTSFIX_MIN_GLPI,
                'max' => PLUGIN_COSTSFIX_MAX_GLPI,
            ],
        ],
    ];
}

function plugin_init_costsfix()
{
    /** @var array $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS;

    if (Session::haveRight('entity', UPDATE)) {
        Plugin::registerClass('PluginCostsfixEntity', ['addtabon' => 'Entity']);
    }
    if (Session::haveRightsOr("config", [READ, UPDATE])) {
        Plugin::registerClass('PluginCostsfixConfig', ['addtabon' => 'Config']);
        $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['costsfix'] = 'front/config.form.php';
    }

    $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['costsfix'] = [
        'Ticket' => ['PluginCostsfixTicket', 'ticketUpdate'],
        'TicketTask' => ['PluginCostsfixTask', 'preTaskUpdate'],
    ];
    $PLUGIN_HOOKS[Hooks::POST_ITEM_FORM]['costsfix'] = ['PluginCostsfixTicket', 'postItemForm'];
    $PLUGIN_HOOKS[Hooks::ITEM_ADD]['costsfix'] = [
        'Ticket' => ['PluginCostsfixTicket', 'ticketAdd'],
        'TicketTask' => ['PluginCostsfixTask', 'taskAdd'],
    ];
    $PLUGIN_HOOKS[Hooks::ITEM_PURGE]['costsfix'] = [
        'TicketTask' => ['PluginCostsfixTask', 'taskPurge'],
    ];
}
