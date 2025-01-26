<?php
/**
 * Extra Forum Permission Pack
 * Copyright 2011 Aries-Belgium
 *
 * $Id$
 */

namespace ExtraForumPermissions\Hooks\Shared;

use PostDataHandler;

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

    _dump(
        $forum_permissions,
        $subject_length,
        $dataHandler->errors['subject_too_long'],
        $dataHandler->get_errors()
    );

    return $dataHandler;
}

function datahandler_post_validate_post(PostDataHandler &$dataHandler): PostDataHandler
{
    return datahandler_post_validate_thread($dataHandler);
}