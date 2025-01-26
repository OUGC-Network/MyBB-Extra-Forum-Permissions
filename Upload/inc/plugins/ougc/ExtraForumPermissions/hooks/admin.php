<?php
/**
 * Extra Forum Permission Pack
 * Copyright 2011 Aries-Belgium
 *
 * $Id$
 */

namespace ExtraForumPermissions\Hooks\Admin;

use const ExtraForumPermissions\Core\FIELDS_DATA;

function admin_formcontainer_output_row(array &$hook_arguments): array
{
    global $group;
    global $extra_forum_permissions;

    if (empty($extra_forum_permissions) || empty($group) || $group !== 'extra') {
        return $hook_arguments;
    }

    global $form, $lang;
    global $permission_data, $fields;

    foreach (FIELDS_DATA['forumpermissions'] as $field_name => $field_definition) {
        if (empty($field_definition['form_type'])) {
            continue;
        }

        $lang_field = "{$group}_field_{$field_name}";

        switch ($field_definition['form_type']) {
            case 'numeric':
                $fields[] = "{$lang->{$lang_field}}<br /><small class=\"input\">{$lang->{"{$lang_field}_desc"}}</small><br />" . $form->generate_numeric_field(
                        "permissions[{$field_name}]",
                        $permission_data[$field_name] ?? 0,
                        $field_definition['form_options']
                    );
                break;
        }
    }

    $hook_arguments['content'] = '<div class="forum_settings_bit">' . implode(
            '</div><div class="forum_settings_bit">',
            $fields
        ) . '</div>';

    return $hook_arguments;
}

function admin_forum_management_permissions_commit(): bool
{
    global $db;
    global $update_array;
    global $fid, $pid;

    if ($fid && !$pid) {
        $pid = $db->insert_id();
    }

    global $mybb;

    $update_data = [];

    foreach (FIELDS_DATA['forumpermissions'] as $field_name => $field_definition) {
        if (empty($field_definition['form_type']) || $field_definition['form_type'] !== 'numeric') {
            continue;
        }

        $update_data[$field_name] = (int)($mybb->input['permissions'][$field_name] ?? 0);
    }

    $db->update_query('forumpermissions', $update_data, "pid='{$pid}'");

    return true;
}