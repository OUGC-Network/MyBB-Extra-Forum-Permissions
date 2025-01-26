<?php
/**
 * Extra Forum Permission Pack
 * Copyright 2011 Aries-Belgium
 *
 * $Id$
 */

namespace ExtraForumPermissions\Hooks\Forum;

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