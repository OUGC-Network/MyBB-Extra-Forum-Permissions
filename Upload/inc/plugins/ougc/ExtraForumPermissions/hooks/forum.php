<?php
/**
 * Extra Forum Permission Pack
 * Copyright 2011 Aries-Belgium
 *
 * $Id$
 */

namespace ExtraForumPermissions\Hooks\Forum;

function global_end(): bool
{
    if (constant('THIS_SCRIPT') === 'ratethread.php') {
        global $extra_rate_thread_script;

        $extra_rate_thread_script = true;
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