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

use Glpi\RichText\RichText;

class PluginCostsfixTask extends CommonDBTM
{
    public static $rightname = 'task';

    /**
     * {@inheritdoc}
     */
    public static function getTypeName($nb = 0): string
    {
        return __('Costs', 'costsfix');
    }

    /**
     * Build cost name with profile and user full name
     * Format: [ProfileName] Firstname Lastname
     *
     * @param  User $user
     * @param  int  $task_id
     * @param  int  $users_id_tech
     * @return string
     */
    private static function buildCostName(User $user, int $task_id, int $users_id_tech): string
    {
        $firstName = $user->fields['firstname'] ?? '';
        $lastName  = $user->fields['realname'] ?? '';

        // Build full name
        if ($firstName === '' && $lastName === '') {
            $fullName = $user->fields['name'] ?? $task_id . '_' . $users_id_tech;
        } else {
            $fullName = trim("$firstName $lastName");
        }

        // Get profile name
        $profileName = '';
        if (!empty($user->fields['profiles_id'])) {
            $profileName = Dropdown::getDropdownName("glpi_profiles", $user->fields['profiles_id']);
        }

        // Format: [ProfileName] Firstname Lastname
        if (!empty($profileName) && $profileName !== '&nbsp;') {
            return "[$profileName] $fullName";
        }

        return $fullName;
    }

    /**
     * Calculate cost_time from actiontime
     * Pellissari billing system expects time in minutes in cost_time field
     * actiontime is in seconds, so we divide by 60 to get minutes
     *
     * @param  int $actiontime Time in seconds
     * @return float Time in minutes
     */
    private static function calculateCostTime(int $actiontime): float
    {
        return $actiontime / 60;
    }

    /**
     * taskAdd
     *
     * @param  TicketTask $task
     * @return void
     */
    public static function taskAdd(TicketTask $task): void
    {
        if (PluginCostsfixTicket::isBillable($task->fields['tickets_id'])) {
            $ticket = new Ticket();
            $ticket->getFromDB($task->fields['tickets_id']);

            if (array_key_exists('state', $task->input)) {
                if ($task->fields['state'] == Planning::DONE) {
                    $cost_config = new PluginCostsfixEntity();
                    $cost_config->getFromDBByCrit(["entities_id" => $ticket->fields['entities_id']]);
                    if ($cost_config->fields['inheritance']) {
                        $parent_id = PluginCostsfixEntity::getConfigID($ticket->fields['entities_id']);
                        $cost_config->getFromDB($parent_id);
                    }

                    $entity_profile = new PluginCostsfixEntityProfile();
                    $user = new User();
                    $user->getFromDB($task->fields['users_id_tech']);
                    if (
                        $entity_profile->getFromDBByCrit([
                            'entities_id' => $cost_config->fields['entities_id'],
                            'profiles_id' => $user->fields['profiles_id'],
                        ])
                    ) {
                        $cost_time = $entity_profile->fields['time_cost'];
                        $cost_fixed = $entity_profile->fields['fixed_cost'];
                    } else {
                        $cost_time = $cost_config->fields['time_cost'];
                        $cost_fixed = $cost_config->fields['fixed_cost'];
                    }

                    if ($cost_time > 0) {
                        if (!$task->fields['is_private'] || $cost_config->fields['cost_private']) {
                            // Build cost name with [Profile] FirstName LastName format
                            $costName = self::buildCostName($user, $task->fields['id'], $task->fields['users_id_tech']);

                            // Build comment with same format
                            $config = PluginCostsfixConfig::getConfig();
                            if ($config->fields['taskdescription']) {
                                $comment = self::taskContentAsComment($task->fields['content']);
                                $comment .= " \n" . __('Automatically generated by GLPI') . ' -> CostsFix Plugin';
                            } else {
                                $comment = $costName;
                            }

                            // Calculate cost_time from actiontime (seconds to minutes for Pellissari billing)
                            $calculatedCostTime = self::calculateCostTime($task->fields['actiontime']);

                            $cost = new TicketCost();
                            $cost_id = $cost->add([
                                'tickets_id'    => $task->fields['tickets_id'],
                                'name'          => $costName,
                                'comment'       => $comment,
                                'begin_date'    => (array_key_exists('begin', $task->fields)) ? $task->fields['begin'] : null,
                                'end_date'      => (array_key_exists('end', $task->fields)) ? $task->fields['end'] : null,
                                'actiontime'    => $task->fields['actiontime'],
                                'cost_time'     => $calculatedCostTime,
                                'cost_fixed'    => $cost_fixed,
                                'entities_id'   => $ticket->fields['entities_id'],
                            ]);

                            $taskcost = new self();
                            $taskcost->add(['tasks_id' => $task->fields['id'],'costs_id' => $cost_id]);
                        }
                    }
                }
            }
        }
    }

    /**
     * preTaskUpdate
     *
     * @param  TicketTask $task
     * @return void
     */
    public static function preTaskUpdate(TicketTask $task): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (PluginCostsfixTicket::isBillable($task->fields['tickets_id'])) {
            $ticket = new Ticket();
            $ticket->getFromDB($task->fields['tickets_id']);

            if ($task->input['state'] == Planning::DONE) {
                if (isset($task->input['is_private'])) {
                    $is_private = $task->input['is_private'];
                } else {
                    $is_private = $task->fields['is_private'];
                }

                if (isset($task->input['content'])) {
                    $content = $task->input['content'];
                } else {
                    $content = $task->fields['content'];
                }
                $content = self::taskContentAsComment($content);

                if (isset($task->input['actiontime'])) {
                    $actiontime = $task->input['actiontime'];
                } else {
                    $actiontime = $task->fields['actiontime'];
                }

                if (isset($task->input['users_id_tech'])) {
                    $users_id_tech = $task->input['users_id_tech'];
                } else {
                    $users_id_tech = $task->fields['users_id_tech'];
                }

                $cost_config = new PluginCostsfixEntity();
                $cost_config->getFromDBByEntity($ticket->fields['entities_id']);
                if ($cost_config->fields['inheritance']) {
                    $parent_id = PluginCostsfixEntity::getConfigID($ticket->fields['entities_id']);
                    $cost_config->getFromDB($parent_id);
                }

                $entity_profile = new PluginCostsfixEntityProfile();
                $user = new User();
                $user->getFromDB($users_id_tech);
                if (
                    $entity_profile->getFromDBByCrit([
                        'entities_id' => $cost_config->fields['entities_id'],
                        'profiles_id' => $user->fields['profiles_id'],
                    ])
                ) {
                    $cost_time = $entity_profile->fields['time_cost'];
                    $cost_fixed = $entity_profile->fields['fixed_cost'];
                } else {
                    $cost_time = $cost_config->fields['time_cost'];
                    $cost_fixed = $cost_config->fields['fixed_cost'];
                }

                if ($cost_time > 0) {
                    if (!$is_private || $cost_config->fields['cost_private']) {
                        $query = [
                            'FROM' => self::getTable(),
                            'WHERE' => [
                                'tasks_id' => $task->fields['id'],
                            ],
                        ];
                        $req = $DB->request($query);

                        // Build cost name with [Profile] FirstName LastName format
                        $costName = self::buildCostName($user, $task->fields['id'], $users_id_tech);

                        // Calculate cost_time from actiontime (seconds to minutes for Pellissari billing)
                        $calculatedCostTime = self::calculateCostTime($actiontime);

                        if (count($req)) {
                            foreach ($req as $row) {
                                $cost_id = $row['costs_id'];

                                $config = PluginCostsfixConfig::getConfig();
                                if ($config->fields['taskdescription']) {
                                    $comment = $content . " \n" . __('Automatically generated by GLPI') . ' -> CostsFix Plugin';
                                } else {
                                    $comment = $costName;
                                }

                                $input = [
                                    'id'         => $cost_id,
                                    'name'       => $costName,
                                    'comment'    => $comment,
                                    'actiontime' => $actiontime,
                                    'cost_time'  => $calculatedCostTime,
                                ];
                                if (array_key_exists('begin', $task->input)) {
                                    $input['begin_date'] = $task->input['begin'];
                                }
                                if (array_key_exists('end', $task->input)) {
                                    $input['end_date'] = $task->input['end'];
                                }

                                $cost = new TicketCost();
                                $cost->update($input);
                            }
                        } else {
                            $config = PluginCostsfixConfig::getConfig();
                            if ($config->fields['taskdescription']) {
                                $comment = $content . " \n" . __('Automatically generated by GLPI') . ' -> CostsFix Plugin';
                            } else {
                                $comment = $costName;
                            }

                            $cost = new TicketCost();
                            $input = [
                                'tickets_id'    => $task->fields['tickets_id'],
                                'name'          => $costName,
                                'comment'       => $comment,
                                'actiontime'    => $actiontime,
                                'cost_time'     => $calculatedCostTime,
                                'cost_fixed'    => $cost_fixed,
                                'entities_id'   => $ticket->fields['entities_id'],
                            ];
                            if (array_key_exists('begin', $task->input)) {
                                $input['begin_date'] = $task->input['begin'];
                            } else {
                                $input['begin_date'] = $task->fields['begin'];
                            }
                            if (array_key_exists('end', $task->input)) {
                                $input['end_date'] = $task->input['end'];
                            } else {
                                $input['end_date'] = $task->fields['end'];
                            }
                            $cost_id = $cost->add($input);

                            $taskcost = new self();
                            $taskcost->add(['tasks_id' => $task->fields['id'],'costs_id' => $cost_id]);
                        }
                    }
                }
            }
        }
    }

    /**
     * taskPurge
     *
     * @param  TicketTask $task
     * @return void
     */
    public static function taskPurge(TicketTask $task): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $query = [
            'FROM' => self::getTable(),
            'WHERE' => [
                'tasks_id' => $task->fields['id'],
            ],
        ];
        $req = $DB->request($query);
        foreach ($req as $row) {
            $cost = new TicketCost();
            $cost->delete(['id' => $row['costs_id']]);
            $taskcost = new self();
            $taskcost->deleteByCriteria(['id' => $row['id']]);
        }
    }

    /**
     * Format task content as comment cleaning html tags and reduce it to text length
     *
     * @param  string $content
     * @return string
     */
    private static function taskContentAsComment(string $content): string
    {
        $content = htmlspecialchars(RichText::getTextFromHtml($content));
        // longtext to text
        $tagline = " \n" . __('Automatically generated by GLPI') . ' -> CostsFix Plugin';
        $total = 65535 - strlen($tagline);
        if (strlen($content) > $total) {
            $content = substr($content, 0, $total);
        }
        return $content;
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

        $table = self::getTable();
        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");

            $query = "CREATE TABLE IF NOT EXISTS `$table` (
                `id` INT {$default_key_sign} NOT NULL auto_increment,
                `tasks_id` INT {$default_key_sign} NOT NULL,
                `costs_id` INT {$default_key_sign} NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `tasks_id` (`tasks_id`),
                KEY `costs_id` (`costs_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
            $DB->doQuery($query);
        } else {
            $migration->changeField($table, 'costs_id', 'costs_id', 'fkey');
        }

        $migration->executeMigration();
    }
}
