<?php
/**
 * Extra Forum Permission Pack
 * Copyright 2011 Aries-Belgium
 *
 * $Id$
 */

namespace ExtraForumPermissions\Hooks\Forum;

use MyBB;

function global_end(): bool
{
    if (defined('THIS_SCRIPT') && constant('THIS_SCRIPT') === 'ratethread.php') {
        global $extra_rate_thread_script;

        $extra_rate_thread_script = true;
    }

    if (defined('THIS_SCRIPT') && in_array(constant('THIS_SCRIPT'), ['newreply.php', 'newthread.php'])) {
        editpost_start(THIS_SCRIPT);
    }

    return true;
}

function editpost_start(string $script_name): bool
{
    global $mybb, $plugins;

    $forum_id = $mybb->get_input('fid', MyBB::INPUT_INT);

    $thread_id = $mybb->get_input('tid', MyBB::INPUT_INT);

    $post_id = $mybb->get_input('pid', MyBB::INPUT_INT);

    if (empty($mybb->user['uid'])) {
        return false;
    }

    $post_data = get_post($post_id);

    $thread_data = get_thread($thread_id ?? ($post_data['tid'] ?? 0));

    $forum_data = get_forum($forum_id ?? ($thread_data['fid'] ?? ($post_data['fid'] ?? 0)));

    $forum_id = (int)($forum_data['fid'] ?? ($thread_data['fid'] ?? ($post_data['fid'] ?? 0)));

    $user_id = (int)($thread_data['uid'] ?? ($post_data['uid'] ?? ($mybb->user['uid'] ?? 0)));

    if ($forum_id && $user_id) {
        $forum_permissions = forum_permissions($forum_id, $user_id);

        if (!empty($forum_permissions['extra_maximum_attachments'])) {
            $mybb->settings['maxattachments'] = (int)$forum_permissions['extra_maximum_attachments'];
        }
    }

    return true;
}

function is_moderator90(array $hook_arguments): array
{
    global $mybb;
    global $thread;
    global $extra_rate_thread_script;

    if (
        empty($mybb->user['uid']) ||
        empty($thread['uid']) ||
        $mybb->user['uid'] != $thread['uid']) {
        unset($extra_rate_thread_script);

        return $hook_arguments;
    }

    $thread['uid'] = 0;

    return $hook_arguments;
}

/**
 * Implementation of the ratethread_start hook
 *
 * If the current user is the topicstarter check the can_rate_own_threads permission
 */
function ratethread_start09(): bool
{
    global $mybb, $thread, $forumpermissions;

    $thread['uid'] = get_thread($thread['tid'])['uid'];

    if (empty($forumpermissions['can_rate_own_threads']) && (int)$thread['uid'] === (int)$mybb->user['uid']) {
        global $lang;

        error($lang->error_cannotrateownthread);
    }

    return true;
}

function newthread_end(): bool
{
    return editpost_end();
}

function newreply_end(): bool
{
    return editpost_end();
}

function editpost_end(): bool
{
    global $plugins;
    global $extra_maximum_subject_length;

    if ($plugins->current_hook === 'editpost_end') {
        global $post;

        $forumpermissions = forum_permissions($post['fid'] ?? 0, $post['uid'] ?? 0);
    } else {
        global $forumpermissions;
    }


    $extra_maximum_subject_length = (int)$forumpermissions['extra_subject_length'];

    return true;
}