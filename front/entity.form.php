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

if (!Plugin::isPluginActive('costsfix')) {
    throw new \Glpi\Exception\Http\NotFoundHttpException();
}

Session::haveRight("entity", UPDATE);

$Entity                 = new Entity();
$PluginCostsfixEntity   = new PluginCostsfixEntity();

if (isset($_POST["plugin_costsfix_update"])) {
    $PluginCostsfixEntity->update($_POST);
    Html::back();
}

throw new \Glpi\Exception\Http\BadRequestHttpException();
