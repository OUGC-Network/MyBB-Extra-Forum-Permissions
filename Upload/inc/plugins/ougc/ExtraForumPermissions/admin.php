<?php
/**
 * Extra Forum Permission Pack
 * Copyright 2011 Aries-Belgium
 *
 * $Id$
 */

namespace ExtraForumPermissions\Admin;

use function ExtraForumPermissions\Core\load_language;

use const ExtraForumPermissions\Core\FIELDS_DATA;
use const ExtraForumPermissions\Core\FIELDS_DATA_CORE;

function plugin_information(): array
{
    global $lang;

    load_language();

    $donate_button =
        '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=RQNL345SN45DS" style="float:right;margin-top:-8px;padding:4px;" target="_blank"><img src="https://www.paypalobjects.com/WEBSCR-640-20110306-1/en_US/i/btn/btn_donate_SM.gif" /></a>';

    return [
        'name' => 'Extra Forum Permissions',
        'description' => "{$donate_button}{$lang->extraforumperm_description}",
        'website' => 'https://github.com/OUGC-Network/MyBB-Extra-Forum-Permissions',
        'author' => 'Aries-Belgium & maintained by Omar G.',
        'authorsite' => 'mailto:aries.belgium@gmail.com',
        'version' => '1.8.1',
        'versioncode' => '1801',
        'compatibility' => '18*',
        'codename' => 'extra_forum_perms'
    ];
}

function plugin_activation(): bool
{
    global $cache;

    // Insert/update version into cache
    $plugins = $cache->read('ougc_plugins');

    if (!$plugins) {
        $plugins = [];
    }

    $plugin_information = plugin_information();

    if (!isset($plugins['extra_forum_permissions'])) {
        $plugins['extra_forum_permissions'] = $plugin_information['versioncode'];
    }

    /*~*~* RUN UPDATES START *~*~*/

    global $db;

    foreach (FIELDS_DATA_CORE as $table_name => $table_columns) {
        foreach ($table_columns as $field_name => $field_definition) {
            $db->modify_column(
                $table_name,
                $field_name,
                db_build_field_definition($field_definition)
            );
        }
    }

    if ($db->field_exists('canrateownthreads', 'forumpermissions') &&
        !$db->field_exists('can_rate_own_threads', 'forumpermissions')) {
        $db->rename_column(
            'forumpermissions',
            'canrateownthreads',
            'can_rate_own_threads',
            db_build_field_definition(FIELDS_DATA['forumpermissions']['can_rate_own_threads'])
        );
    }

    if ($db->field_exists('canpostlinks', 'forumpermissions') &&
        !$db->field_exists('can_post_links', 'forumpermissions')) {
        $db->rename_column(
            'forumpermissions',
            'canpostlinks',
            'can_post_links',
            db_build_field_definition(FIELDS_DATA['forumpermissions']['can_post_links'])
        );
    }

    if ($db->field_exists('canrateownthreads', 'usergroups') &&
        !$db->field_exists('can_rate_own_threads', 'usergroups')) {
        $db->rename_column(
            'usergroups',
            'canrateownthreads',
            'can_rate_own_threads',
            db_build_field_definition(FIELDS_DATA['usergroups']['can_rate_own_threads'])
        );
    }

    if ($db->field_exists('canpostlinks', 'usergroups') &&
        !$db->field_exists('can_post_links', 'usergroups')) {
        $db->rename_column(
            'usergroups',
            'canpostlinks',
            'can_post_links',
            db_build_field_definition(FIELDS_DATA['usergroups']['can_post_links'])
        );
    }

    /*~*~* RUN UPDATES END *~*~*/

    db_verify_columns();

    $cache->update_usergroups();

    $cache->update_forumpermissions();

    $plugins['extra_forum_permissions'] = $plugin_information['versioncode'];

    $cache->update('ougc_plugins', $plugins);
    
    // Templates modifications
    require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';
    find_replace_templatesets('editpost', '#maxlength="85"#', 'maxlength="{$extra_maximum_subject_length}"');
    find_replace_templatesets('newthread', '#maxlength="85"#', 'maxlength="{$extra_maximum_subject_length}"');
    find_replace_templatesets('newreply', '#maxlength="85"#', 'maxlength="{$extra_maximum_subject_length}"');
    find_replace_templatesets('showthread_quickreply', '#{\$closeoption}#', '<!--EXTRAPERMISSIONS-->');

    return true;
}

function plugin_deactivation(): bool
{
    // Templates rollback
    require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';
    find_replace_templatesets('editpost', '#{\$extra_maximum_subject_length}#', '85');
    find_replace_templatesets('newthread', '#{\$extra_maximum_subject_length}#', '85');
    find_replace_templatesets('newreply', '#{\$extra_maximum_subject_length}#', '85');
    find_replace_templatesets('showthread_quickreply', '#<!--EXTRAPERMISSIONS-->#', '{\$closeoption}');
    
    return true;
}

function plugin_is_installed(): bool
{
    global $db;

    static $is_installed_each = null;

    if ($is_installed_each === null) {
        $is_installed_each = true;

        foreach (FIELDS_DATA as $table_name => $table_columns) {
            foreach ($table_columns as $field_name => $field_definition) {
                $is_installed_each = $db->field_exists($field_name, $table_name) && $is_installed_each;
            }
        }
    }

    return $is_installed_each;
}

function plugin_uninstallation(): bool
{
    global $db, $cache;

    foreach (FIELDS_DATA as $table_name => $table_columns) {
        foreach ($table_columns as $field_name => $field_definition) {
            if ($db->field_exists($field_name, $table_name)) {
                $db->drop_column($table_name, $field_name);
            }
        }
    }

    $cache->update_usergroups();

    $cache->update_forumpermissions();

    // Delete version from cache
    $plugins = (array)$cache->read('ougc_plugins');

    if (isset($plugins['extra_forum_permissions'])) {
        unset($plugins['extra_forum_permissions']);
    }

    if (!empty($plugins)) {
        $cache->update('ougc_plugins', $plugins);
    } else {
        $cache->delete('ougc_plugins');
    }

    return true;
}

function db_verify_columns(): bool
{
    global $db;

    foreach (FIELDS_DATA as $table_name => $table_columns) {
        foreach ($table_columns as $field_name => $field_data) {
            if (!isset($field_data['type'])) {
                continue;
            }

            if ($db->field_exists($field_name, $table_name)) {
                $db->modify_column($table_name, "`{$field_name}`", db_build_field_definition($field_data));
            } else {
                $db->add_column($table_name, $field_name, db_build_field_definition($field_data));
            }
        }
    }

    return true;
}

function db_build_field_definition(array $field_data): string
{
    $field_definition = '';

    $field_definition .= $field_data['type'];

    if (isset($field_data['size'])) {
        $field_definition .= "({$field_data['size']})";
    }

    if (isset($field_data['unsigned'])) {
        if ($field_data['unsigned'] === true) {
            $field_definition .= ' UNSIGNED';
        } else {
            $field_definition .= ' SIGNED';
        }
    }

    if (!isset($field_data['null'])) {
        $field_definition .= ' NOT';
    }

    $field_definition .= ' NULL';

    if (isset($field_data['auto_increment'])) {
        $field_definition .= ' AUTO_INCREMENT';
    }

    if (isset($field_data['default'])) {
        $field_definition .= " DEFAULT '{$field_data['default']}'";
    }

    return $field_definition;
}