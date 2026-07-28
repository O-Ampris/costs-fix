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

use Glpi\Application\View\TemplateRenderer;

class PluginCostsfixConfig extends CommonDBTM
{
    public static $rightname = 'config';
    public const KEEP_ALL = 'all';
    private static $instance = null;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        /** @var \DBmysql $DB */
        global $DB;
        if ($DB->tableExists(self::getTable())) {
            $this->getFromDB(1);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getTypeName($nb = 0): string
    {
        return __("CostsFix", "costsfix");
    }

    public static function getIcon(): string
    {
        return PLUGIN_COSTSFIX_ICON;
    }

    /**
     * getInstance
     *
     * @param  int $n
     * @return PluginCostsfixConfig
     */
    public static function getInstance(int $n = 1): PluginCostsfixConfig
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            if (!self::$instance->getFromDB($n)) {
                self::$instance->getEmpty();
            }
        }

        return self::$instance;
    }

    /**
     * getConfig
     *
     * @param  bool $update
     * @return PluginCostsfixConfig
     */
    public static function getConfig(bool $update = false): PluginCostsfixConfig
    {
        static $config = null;
        if (is_null($config)) {
            $config = new self();
        }
        if ($update) {
            $config->getFromDB(1);
        }

        return $config;
    }

    /**
     * Summary of showConfigForm
     *
     * @return boolean
     */
    public static function showConfigForm(): bool
    {
        /** @var \DBmysql $DB */
        global $DB;

        $config = self::getInstance();

        $options = [
            'full_width' => true,
        ];

        TemplateRenderer::getInstance()->display('@costsfix/config.html.twig', [
            'item' => $config,
            'options' => $options,
        ]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        if ($item->getType() == 'Config') {
            return self::createTabEntry(__("CostsFix", "costsfix"));
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item->getType() == 'Config') {
            return self::showConfigForm();
        }

        return false;
    }

    /**
     * install
     *
     * @param  Migration $migration
     * @return void
     */
    public static function install(Migration $migration): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $default_charset    = DBConnection::getDefaultCharset();
        $default_collation  = DBConnection::getDefaultCollation();
        $default_key_sign   = DBConnection::getDefaultPrimaryKeySignOption();

        $table  = self::getTable();
        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");
            $query = "CREATE TABLE `$table` (
                `id` INT {$default_key_sign} NOT NULL AUTO_INCREMENT,
                `taskdescription` TINYINT NOT NULL DEFAULT '0',
                PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
            $DB->doQuery($query);

            $config = new self();
            $config->add([
                'id'                => 1,
                'taskdescription'   => 0,
            ]);
        }
    }
}
