<?php
/**
 * Extra Forum Permission Pack
 * Copyright 2011 Aries-Belgium
 *
 * $Id$
 */

namespace ExtraForumPermissions\Hooks\Shared;

use PostDataHandler;

use function ExtraForumPermissions\Core\load_language;

use const ExtraForumPermissions\Core\FORM_VALUE_TYPE_DAYS;
use const ExtraForumPermissions\Core\FORM_VALUE_TYPE_HOURS;

function datahandler_post_validate_thread(PostDataHandler &$dataHandler): PostDataHandler
{
    if (!isset($dataHandler->errors['subject_too_long'])) {
        return $dataHandler;
    }

    unset($dataHandler->errors['subject_too_long']);

    $post_data = &$dataHandler->data;

    $subject = &$post_data['subject'];

    $subject = trim_blank_chrs($subject);

    $subject_length = my_strlen($subject);

    if ($dataHandler->method === 'update' && !empty($post_data['pid'])) {
        if ($subject_length === 0 && $dataHandler->first_post) {
            $dataHandler->set_error('firstpost_no_subject');

            $return = true;
        } elseif ($subject_length === 0) {
            $thread_data = get_thread($post_data['tid']);

            $subject = "RE: {$thread_data['subject']}";
        }
    } elseif ($dataHandler->action === 'post') {
        if ($subject_length === 0) {
            $thread_data = get_thread($post_data['tid']);

            $subject = "RE: {$thread_data['subject']}";
        }
    } elseif ($subject_length == 0) {
        $dataHandler->set_error('missing_subject');

        $return = true;
    }

    if (!empty($return)) {
        return $dataHandler;
    }

    if ($dataHandler->action === 'post') {
        $reply_text_position = my_strpos($subject, 'RE: ');

        if ($reply_text_position !== false && $reply_text_position === 0) {
            $subject_length = $subject_length - 4;
        }
    }

    $forum_permissions = forum_permissions($post_data['fid'] ?? 0, $post_data['uid'] ?? 0);

    if ($subject_length > $forum_permissions['extra_subject_length']) {
        $dataHandler->set_error('subject_too_long', my_strlen($subject));
    }

    return $dataHandler;
}

function datahandler_post_validate_post(PostDataHandler &$dataHandler): PostDataHandler
{
    return datahandler_post_validate_thread($dataHandler);
}

function datahandler_post_validate_thread10(PostDataHandler &$dataHandler): PostDataHandler
{
    global $mybb, $cache;

    $thread = &$dataHandler->data;

    if (!empty($thread['savedraft'])) {
        return $dataHandler;
    }

    $forum_id = (int)$thread['fid'];

    $forum_data = cache_forums()[$forum_id];

    $is_forum_permission = true;

    $maximum_threads_forum = (int)$forum_data['extra_maximum_threads'];

    if ($maximum_threads_forum === 0) {
        $maximum_threads_forum = null;
    }

    if ($maximum_threads_forum === null) {
        $forum_permissions = (array)$cache->read('forumpermissions');

        foreach (array_merge([$mybb->user['usergroup']], explode(',', $mybb->user['additionalgroups'])) as $group_id) {
            if (isset($forum_permissions[$forum_id][$group_id])) {
                $group_permissions = $forum_permissions[$forum_id][$group_id];

                if (empty($group_permissions['canpostthreads'])) {
                    continue;
                }

                $extra_maximum_threads = (int)$group_permissions['extra_maximum_threads'];

                if ($extra_maximum_threads === 0) {
                    $maximum_threads_forum = 0;
                }

                if ($extra_maximum_threads !== 0 && $maximum_threads_forum !== 0) {
                    $maximum_threads_forum = max(
                        $extra_maximum_threads,
                        $maximum_threads_forum
                    );
                }
            }
        }
    }

    if ($maximum_threads_forum === null) {
        $is_forum_permission = false;

        $maximum_threads_forum = (int)$mybb->usergroup['extra_maximum_threads'];
    }

    if ($maximum_threads_forum > 0) {
        global $db, $lang;

        load_language();

        switch ($forum_data['extra_maximum_threads_type']) {
            case FORM_VALUE_TYPE_HOURS;
                $day_cut = TIME_NOW - 60 * 60 * $forum_data['extra_maximum_threads_type_amount'];

                $time_cut_language_variable = $lang->error_extra_maximum_threads_forum_hour;
                break;
            case FORM_VALUE_TYPE_DAYS;
                $day_cut = TIME_NOW - 60 * 60 * 24 * $forum_data['extra_maximum_threads_type_amount'];

                $time_cut_language_variable = $lang->error_extra_maximum_threads_forum_day;
                break;
            default;
                $day_cut = TIME_NOW - 60 * 60 * 24 * 7 * $forum_data['extra_maximum_threads_type_amount'];

                $time_cut_language_variable = $lang->error_extra_maximum_threads_forum_week;
                break;
        }

        $current_user_id = (int)$mybb->user['uid'];

        $query = $db->simple_select(
            'threads',
            'COUNT(tid) AS threads_today',
            "uid='{$current_user_id}' AND visible='1' AND dateline>'{$day_cut}' AND fid='{$forum_id}'",
            [
                'limit' => 1
            ]
        );

        $threads_today = $db->fetch_field($query, 'threads_today');

        if ($threads_today >= $maximum_threads_forum) {
            if ($is_forum_permission) {
                $language_string = $lang->error_extra_maximum_threads_forum;
            } else {
                $language_string = $lang->error_extra_maximum_threads_group;
            }

            $language_string .= $lang->error_extra_maximum_threads_forum_note;

            if ($is_forum_permission) {
                $language_string .= $lang->sprintf(
                    $lang->error_extra_maximum_threads_forum_description,
                    my_number_format($maximum_threads_forum),
                    $forum_data['extra_maximum_threads_type_amount'],
                    $time_cut_language_variable
                );
            } else {
                $language_string .= $lang->sprintf(
                    $lang->error_extra_maximum_threads_group_description,
                    my_number_format($maximum_threads_forum),
                    $forum_data['extra_maximum_threads_type_amount'],
                    $time_cut_language_variable
                );
            }

            $language_string .= $lang->error_extra_maximum_threads_draft_notice;

            //error($language_string);
            $dataHandler->set_error($language_string);
        }
    }

    return $dataHandler;
}