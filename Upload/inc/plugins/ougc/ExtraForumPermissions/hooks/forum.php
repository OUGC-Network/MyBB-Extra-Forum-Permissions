<?php
/**
 * Extra Forum Permission Pack
 * Copyright 2011 Aries-Belgium
 *
 * $Id$
 */

namespace ExtraForumPermissions\Hooks\Forum;

use MyBB;
use PostParser;

use const ExtraForumPermissions\Core\REGULAR_EXPRESSIONS_URL;

function global_start(): bool
{
    global $templatelist;

    if (isset($templatelist)) {
        $templatelist .= ',';
    } else {
        $templatelist = '';
    }

    $templatelist .= 'extraforumpermissions_my_code_url_hidden';

    return true;
}

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
    global $mybb;
    global $extra_moderate_own_threads;

    $extra_moderate_own_threads = true;

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
    global $extra_rate_thread_script, $extra_moderate_own_threads;

    if (
        !empty($mybb->user['uid']) &&
        !empty($thread['uid']) &&
        (int)$mybb->user['uid'] === (int)$thread['uid']
    ) {
        if (!empty($extra_rate_thread_script)) {
            $thread['uid'] = 0;
        }

        if (!empty($hook_arguments['uid']) && (int)$hook_arguments['uid'] !== (int)$mybb->user['uid']) {
            return $hook_arguments;
        }

        if (!empty($extra_moderate_own_threads)) {
            $forum_permissions = forum_permissions($hook_arguments['fid'] ?? 0, $hook_arguments['uid'] ?? 0);

            if (empty($forum_permissions['can_moderate_own_threads'])) {
                return $hook_arguments;
            }

            switch ($hook_arguments['action']) {
                case 'caneditposts':
                case 'cansoftdeleteposts':
                case 'canrestoreposts':
                case 'canapproveunapproveposts':
                case 'canpostclosedthreads':
                case 'canviewdeleted':
                case 'canviewunapprove':
                    $hook_arguments['is_moderator'] = true;
                    break;
            }
        }
    }

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

function showthread_start(): bool
{
    global $mybb;

    if ($mybb->input['action'] !== 'thread') {
        return false;
    }

    global $extra_moderate_own_threads;

    $extra_moderate_own_threads = true;

    return true;
}

function attachment_start(): bool
{
    global $extra_moderate_own_threads;

    $extra_moderate_own_threads = true;

    return true;
}

function xmlhttp_edit_post_start(): bool
{
    global $extra_moderate_own_threads;

    $extra_moderate_own_threads = true;

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
    global $forumpermissions;

    $extra_maximum_subject_length = (int)$forumpermissions['extra_subject_length'];

    return true;
}

function parse_message_me_mycode(string &$message): string
{
    global $parser;
    global $forumpermissions;

    if (!isset($forumpermissions['can_view_links']) ||
        !empty($forumpermissions['can_view_links']) ||
        !($parser instanceof PostParser)) {
        return $message;
    }

    foreach (REGULAR_EXPRESSIONS_URL as $regular_expression => $callback) {
        $message = preg_replace_callback($regular_expression, "\\ExtraForumPermissions\\Core\\{$callback}", $message);
    }

    return $message;
}