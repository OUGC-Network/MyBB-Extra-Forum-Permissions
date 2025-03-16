<?php
/**
 * Extra Forum Permission Pack
 * Copyright 2011 Aries-Belgium
 *
 * $Id$
 */

namespace ExtraForumPermissions\Hooks\Admin;

use MyBB;

use function ExtraForumPermissions\Core\load_language;

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

function admin_formcontainer_end(array &$current_hook_arguments): array
{
    global $lang;
    global $run_module;

    static $done = false;

    if (
        $done ||
        $run_module !== 'forum' ||
        !isset($current_hook_arguments['this']->_title) ||
        !isset($lang->additional_forum_options) ||
        (
            $current_hook_arguments['this']->_title !== $lang->additional_forum_options &&
            $current_hook_arguments['this']->_title !== "<div class=\"float_right\" style=\"font-weight: normal;\"><a href=\"#\" onclick=\"$('#additional_options_link').toggle(); $('#additional_options').fadeToggle('fast'); return false;\">{$lang->hide_additional_options}</a></div>" . $lang->additional_forum_options
        )) {
        return $current_hook_arguments;
    }

    $done = true;

    global $lang, $form;
    global $forum_data;

    load_language();

    $data_fields = FIELDS_DATA['forums'];

    $form_fields = [];

    foreach ($data_fields as $data_field_key => $data_field_data) {
        $data_field_data['form_type'] = $data_field_data['form_type'] ?? ($data_field_data['formType'] ?? null);

        if (empty($data_field_data['form_type'])) {
            continue;
        }

        $setting_language_string = $data_field_key;

        $form_options = [];

        if (isset($data_field_data['formOptions'])) {
            $data_field_data['form_options'] = array_merge(
                $data_field_data['formOptions'],
                $data_field_data['form_options'] ?? []
            );
        }

        if (isset($data_field_data['form_options']['min'])) {
            $form_options['min'] = $data_field_data['form_options']['min'];
        } else {
            $form_options['min'] = 0;
        }

        if (isset($data_field_data['form_options']['step'])) {
            $form_options['step'] = $data_field_data['form_options']['step'];
        } else {
            $form_options['step'] = 1;
        }

        if (isset($data_field_data['form_options']['max'])) {
            $form_options['max'] = $data_field_data['form_options']['max'];
        }

        switch ($data_field_data['form_type']) {
            case 'numeric':
                if (in_array($data_field_data['type'], ['DECIMAL', 'FLOAT'])) {
                    $value = (float)$forum_data[$data_field_key];
                } else {
                    $value = (int)$forum_data[$data_field_key];
                }

                $form_fields[] = $lang->{$setting_language_string} . $form->generate_numeric_field(
                        $data_field_key,
                        $value,
                        $form_options
                    );
                break;
            case 'select':
                if (in_array($data_field_data['type'], ['DECIMAL', 'FLOAT'])) {
                    $value = (float)$forum_data[$data_field_key];
                } else {
                    $value = (int)$forum_data[$data_field_key];
                }

                $select_options = [];

                if (isset($data_field_data['select_data'])) {
                    $select_options = array_map(function (string &$option) use ($lang, $data_field_key): string {
                        $option = $lang->{"{$data_field_key}_{$option}"};

                        return $option;
                    }, $data_field_data['select_data']);
                }

                $form_fields[] = $lang->{$setting_language_string} . $form->generate_select_box(
                        $data_field_key,
                        $select_options,
                        [$value],
                        $form_options
                    );
                break;
        }
    }

    if (empty($form_fields)) {
        return $current_hook_arguments;
    }

    $current_hook_arguments['this']->output_row(
        $lang->extra_forum_tab,
        '',
        "<div class=\"forum_settings_bit\">" . implode(
            "</div><div class=\"forum_settings_bit\">",
            $form_fields
        ) . '</div>'
    );

    return $current_hook_arguments;
}

function admin_forum_management_edit_commit(): bool
{
    global $db, $mybb, $fid;

    $data_fields = FIELDS_DATA['forums'];

    $updated_forum = [];

    foreach ($data_fields as $data_field_key => $data_field_data) {
        if (in_array($data_field_data['type'], ['INT', 'SMALLINT', 'TINYINT'])) {
            $updated_forum[$data_field_key] = $mybb->get_input($data_field_key, MyBB::INPUT_INT);
        } elseif (in_array($data_field_data['type'], ['FLOAT', 'DECIMAL'])) {
            $updated_forum[$data_field_key] = $mybb->get_input($data_field_key, MyBB::INPUT_FLOAT);
        } else {
            $updated_forum[$data_field_key] = $db->escape_string($mybb->get_input($data_field_key));
        }
    }

    $db->update_query('forums', $updated_forum, "fid='{$fid}'");

    $mybb->cache->update_forums();

    return true;
}