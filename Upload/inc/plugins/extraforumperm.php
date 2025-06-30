<?php
/**
 * Extra Forum Permission Pack
 * Copyright 2011 Aries-Belgium
 *
 * $Id$
 */

use function ExtraForumPermissions\Admin\plugin_activation;
use function ExtraForumPermissions\Admin\plugin_deactivation;
use function ExtraForumPermissions\Admin\plugin_information;
use function ExtraForumPermissions\Admin\plugin_is_installed;
use function ExtraForumPermissions\Admin\plugin_uninstallation;
use function ExtraForumPermissions\Core\add_hooks;
use function ExtraForumPermissions\Core\load_language;

use const ExtraForumPermissions\Core\FIELDS_DATA;
use const ExtraForumPermissions\ROOT;

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

define('ExtraForumPermissions\ROOT', MYBB_ROOT . 'inc/plugins/ougc/ExtraForumPermissions');

define('ExtraForumPermissions\DEBUG', false);

defined('PLUGINLIBRARY') || define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

require_once ROOT . '/core.php';

if (defined('IN_ADMINCP')) {
    require_once ROOT . '/admin.php';

    require_once ROOT . '/hooks/admin.php';

    add_hooks('ExtraForumPermissions\Hooks\Admin');
} else {
    require_once ROOT . '/hooks/forum.php';

    add_hooks('ExtraForumPermissions\Hooks\Forum');
}

require_once ROOT . '/hooks/shared.php';

add_hooks('ExtraForumPermissions\Hooks\Shared');

global $plugins;

$plugins->add_hook('admin_forum_management_permission_groups', 'extraforumperm_custom_permissions');
$plugins->add_hook('admin_user_groups_edit_graph_tabs', 'extraforumperm_usergroup_permissions_tab');
$plugins->add_hook('admin_user_groups_edit_graph', 'extraforumperm_usergroup_permissions');
$plugins->add_hook('admin_user_groups_edit_commit', 'extraforumperm_usergroup_permissions_save');

// canstickyownthreads, cancloseownthreads
$plugins->add_hook('showthread_end', 'extraforumperm_showthreadmoderation');
$plugins->add_hook('newreply_end', 'extraforumperm_newreplymoderation');
$plugins->add_hook('newthread_end', 'extraforumperm_newthreadmoderation');
$plugins->add_hook('moderation_start', 'extraforumperm_moderation');
$plugins->add_hook('newreply_do_newreply_end', 'extraforumperm_save_modoptions');
$plugins->add_hook('newthread_do_newthread_end', 'extraforumperm_save_modoptions');
// can_post_links_in_threads, can_post_links, canpostimages, canpostvideos
$plugins->add_hook('datahandler_post_validate_thread', 'extraforumperm_validatepost');
$plugins->add_hook('datahandler_post_validate_post', 'extraforumperm_validatepost');

/**
 * Info function for MyBB plugin system
 */
function extraforumperm_info(): array
{
    return plugin_information();
}

/**
 * The is_installed function for the plugin system
 */
function extraforumperm_is_installed(): bool
{
    return plugin_is_installed();
}

/**
 * The uninstall function for the plugin system
 */
function extraforumperm_uninstall(): bool
{
    return plugin_uninstallation();
}

/**
 * The activate function for the plugin system
 */
function extraforumperm_activate(): bool
{
    return plugin_activation();
}

/**
 * The deactivate function for the plugin system
 */
function extraforumperm_deactivate()
{
    return plugin_deactivation();
}

/**
 * Implementation of the admin_forum_management_permission_groups hook
 *
 * Add the extra permissions to the custom permissions form
 */
function extraforumperm_custom_permissions(array &$groups): array
{
    global $hidefields;
    global $extra_forum_permissions;

    $extra_forum_permissions = true;

    load_language();

    foreach (FIELDS_DATA['forumpermissions'] as $field_name => $field_definition) {
        if (!empty($field_definition['form_options']) && !empty($field_definition['form_options']['disabled_for_guest_group'])) {
            global $usergroup;

            if ((int)$usergroup['gid'] === 1) {
                $hidefields[] = $field_name;
            }
        }

        $groups[$field_name] = 'extra';
    }

    return $groups;
}

function extraforumperm_usergroup_permissions_tab(&$tabs)
{
    global $lang;

    load_language();

    $tabs['extra'] = $lang->group_extra;
}

/**
 * Implementation of the admin_formcontainer_end hook
 *
 * Add the extra permissions to the end of the usergroups table
 */
function extraforumperm_usergroup_permissions()
{
    global $mybb, $lang, $form;

    load_language();

    print '<div id="tab_extra">';
    $form_container = new FormContainer($lang->group_extra);

    $extra_options = [];

    foreach (FIELDS_DATA['usergroups'] as $field_name => $field_definition) {
        if (empty($field_definition['form_type'])) {
            continue;
        }

        $lang_field = 'extra_field_' . $field_name;

        switch ($field_definition['form_type']) {
            case 'numeric':
                $extra_options[] = "{$lang->{$lang_field}}<br /><small class=\"input\">{$lang->{"{$lang_field}_desc"}}</small><br />" . $form->generate_numeric_field(
                        $field_name,
                        $mybb->get_input($field_name, MyBB::INPUT_INT),
                        $field_definition['form_options']
                    );
                break;
            case 'check_box':
                $extra_options[] = $form->generate_check_box(
                    $field_name,
                    1,
                    $lang->{$lang_field},
                    ['checked' => $mybb->get_input($field_name, MyBB::INPUT_INT)]
                );
                break;
        }
    }

    $form_container->output_row(
        $lang->extraforumperm,
        '',
        "<div class=\"group_settings_bit\">" . implode(
            "</div><div class=\"group_settings_bit\">",
            $extra_options
        ) . '</div>'
    );

    $form_container->end();

    print '</div>';
}

function extraforumperm_usergroup_permissions_save()
{
    global $mybb, $updated_group;

    foreach (FIELDS_DATA['usergroups'] as $field_name => $field_definition) {
        $updated_group[$field_name] = $mybb->get_input($field_name, MyBB::INPUT_INT);
    }
}

/**
 * Implementation of the showthread_end hook
 *
 * Show the thread moderation for several extra permissions
 */
function extraforumperm_showthreadmoderation()
{
    global $mybb, $lang, $thread, $templates, $forumpermissions, $moderationoptions, $inlinecount, $inlinecookie, $gobutton, $tid, $quickreply;

    // if $moderationoptions is not empty the current user already has
    // moderation rights to this thread
    if (!empty($moderationoptions)) {
        return;
    }

    // if the user doesn't have any permission where we need the moderation
    // tool for, just exit the function
    if (
        $forumpermissions['canstickyownthreads'] == 0 &&
        $forumpermissions['cancloseownthreads'] == 0
    ) {
        return;
    }

    $standardthreadtools = '';

    $closeoption = '';

    if ($forumpermissions['cancloseownthreads'] == 1 && $thread['uid'] == $mybb->user['uid']) {
        if ($quickreply) {
            if ($thread['closed'] == 1) {
                $closelinkch = ' checked="checked"';
            }

            $closeoption .= eval($templates->render('showthread_quickreply_options_close'));
        }

        $standardthreadtools .= eval($templates->render('showthread_moderationoptions_openclose'));
    }

    if ($forumpermissions['canstickyownthreads'] == 1 && $thread['uid'] == $mybb->user['uid']) {
        if ($quickreply) {
            if ($thread['sticky']) {
                $stickch = ' checked="checked"';
            }

            $closeoption .= eval($templates->render('showthread_quickreply_options_stick'));
        }

        $standardthreadtools .= eval($templates->render('showthread_moderationoptions_stickunstick'));
    }

    if ($closeoption) {
        $quickreply = str_replace('<!--EXTRAPERMISSIONS-->', $closeoption, $quickreply);
    }

    if ($standardthreadtools) {
        $_i = $lang->delayed_moderation;

        $inlinemod = $lang->delayed_moderation = '';

        $customthreadtools = '<input type="hidden" name="extraforumperm" value="1" />';

        $moderationoptions = eval($templates->render('showthread_moderationoptions'));

        $lang->delayed_moderation = $_i;
    }
}

/**
 * Implementation of the newreply_end hook
 *
 * Show the mod options to the user if he has the right
 * permissions
 */
function extraforumperm_newreplymoderation()
{
    global $mybb, $templates, $lang, $forumpermissions, $thread, $modoptions, $bgcolor;

    if (isset($mybb->input['processed'])) {
        $moderation_options = $mybb->get_input('modoptions', MyBB::INPUT_ARRAY);

        $closed = isset($moderation_options['closethread']) ? 1 : 0;
        $stuck = isset($moderation_options['stickthread']) ? 1 : 0;
    } else {
        $closed = $thread['closed'];
        $stuck = $thread['sticky'];
    }

    if ($closed) {
        $closecheck = ' checked="checked"';
    } else {
        $closecheck = '';
    }

    if ($stuck) {
        $stickycheck = ' checked="checked"';
    } else {
        $stickycheck = '';
    }

    if ($forumpermissions['canstickyownthreads'] == 0 || $mybb->user['uid'] != $thread['uid']) {
        $stickycheck .= ' disabled="disabled"';
    }

    if ($forumpermissions['cancloseownthreads'] == 0 || $mybb->user['uid'] != $thread['uid']) {
        $closecheck .= ' disabled="disabled"';
    }

    if (empty($modoptions) && ($forumpermissions['canstickyownthreads'] || $forumpermissions['cancloseownthreads'])) {
        eval("\$modoptions = \"" . $templates->get('newreply_modoptions') . "\";");
    }
}

/**
 * Implementation of the newreply_end hook
 *
 * Show the mod options to the user if he has the right
 * permissions
 */
function extraforumperm_newthreadmoderation()
{
    global $mybb, $templates, $lang, $forumpermissions, $modoptions, $bgcolor;

    if (isset($mybb->input['processed'])) {
        $moderation_options = $mybb->get_input('modoptions', MyBB::INPUT_ARRAY);

        $closed = isset($moderation_options['closethread']) ? 1 : 0;
        $stuck = isset($moderation_options['stickthread']) ? 1 : 0;
    } else {
        $closed = 0;
        $stuck = 0;
    }

    if ($closed) {
        $closecheck = ' checked="checked"';
    } else {
        $closecheck = '';
    }

    if ($stuck) {
        $stickycheck = ' checked="checked"';
    } else {
        $stickycheck = '';
    }

    if ($forumpermissions['canstickyownthreads'] == 0) {
        $stickycheck .= ' disabled="disabled"';
    }

    if ($forumpermissions['cancloseownthreads'] == 0) {
        $closecheck .= ' disabled="disabled"';
    }

    if (empty($modoptions) && ($forumpermissions['canstickyownthreads'] || $forumpermissions['cancloseownthreads'])) {
        eval("\$modoptions = \"" . $templates->get('newreply_modoptions') . "\";");
    }
}

/**
 * Implementation of the moderation_start hook
 *
 * Enable regular users to have some moderation actions
 * on there own threads
 */
function extraforumperm_moderation()
{
    global $mybb, $moderation, $plugins, $templates, $parser, $lang;

    if ($mybb->get_input('extraforumperm', MyBB::INPUT_INT)) {
        // @see moderation.php: 37 -> 73 ------------------------------------
        $tid = $mybb->get_input('tid', MyBB::INPUT_INT);
        $pid = $mybb->get_input('pid', MyBB::INPUT_INT);
        $fid = $mybb->get_input('fid', MyBB::INPUT_INT);

        if ($pid) {
            $post = get_post($pid);
            if (!$post) {
                error($lang->error_invalidpost, $lang->error);
            }
            $tid = $post['tid'];
        }

        if ($tid) {
            $thread = get_thread($tid);
            if (!$thread) {
                error($lang->error_invalidthread, $lang->error);
            }
            $fid = $thread['fid'];
        }

        if ($fid) {
            $modlogdata['fid'] = $fid;
            $forum = get_forum($fid);

            // Make navigation
            build_forum_breadcrumb($fid);

            // Get our permissions all nice and setup
            $permissions = forum_permissions($fid);
        }

        if (isset($thread)) {
            $thread['subject'] = htmlspecialchars_uni($parser->parse_badwords($thread['subject']));
            add_breadcrumb($thread['subject'], get_thread_link($thread['tid']));
            $modlogdata['tid'] = $thread['tid'];
        }

        if (isset($forum)) {
            // Check if this forum is password protected and we have a valid password
            check_forum_password($forum['fid']);
        }

        $mybb->user['username'] = htmlspecialchars_uni($mybb->user['username']);

        if ($mybb->user['uid']) {
            eval("\$loginbox = \"" . $templates->get('changeuserbox') . "\";");
        } else {
            eval("\$loginbox = \"" . $templates->get('loginbox') . "\";");
        }
        // ------------------------------------------------------------------

        switch ($mybb->get_input('action')) {
            // canstickyownthread
            // Stick or unstick that post to the top bab!
            case 'stick':
                // Verify incoming POST request
                verify_post_check($mybb->get_input('my_post_key'));

                if (
                    !is_moderator($fid, 'canstickunstickthreads') &&
                    !($permissions['canstickyownthreads'] && $mybb->user['uid'] == $thread['uid'])
                ) {
                    error_no_permission();
                }

                if ($thread['visible'] == -1) {
                    error($lang->error_thread_deleted, $lang->error);
                }

                $plugins->run_hooks('moderation_stick');

                if ($thread['sticky'] == 1) {
                    $stuckunstuck = $lang->unstuck;
                    $redirect = $lang->redirect_unstickthread;
                    $moderation->unstick_threads($tid);
                } else {
                    $stuckunstuck = $lang->stuck;
                    $redirect = $lang->redirect_stickthread;
                    $moderation->stick_threads($tid);
                }

                $lang->mod_process = $lang->sprintf($lang->mod_process, $stuckunstuck);

                log_moderator_action($modlogdata, $lang->mod_process);

                moderation_redirect(get_thread_link($thread['tid']), $redirect);
                break;
            // cancloseownthread
            // Open or close a thread
            case 'openclosethread':
                // Verify incoming POST request
                verify_post_check($mybb->get_input('my_post_key'));

                if (
                    !is_moderator($fid, 'canopenclosethreads') &&
                    !($permissions['cancloseownthreads'] && $mybb->user['uid'] == $thread['uid'])
                ) {
                    error_no_permission();
                }

                if ($thread['visible'] == -1) {
                    error($lang->error_thread_deleted, $lang->error);
                }

                if ($thread['closed'] == 1) {
                    $openclose = $lang->opened;
                    $redirect = $lang->redirect_openthread;
                    $moderation->open_threads($tid);
                } else {
                    $openclose = $lang->closed;
                    $redirect = $lang->redirect_closethread;
                    $moderation->close_threads($tid);
                }

                $lang->mod_process = $lang->sprintf($lang->mod_process, $openclose);

                log_moderator_action($modlogdata, $lang->mod_process);

                moderation_redirect(get_thread_link($thread['tid']), $redirect);
                break;
        }
    }
}

/**
 * Implementation of the newreply_do_newreply_end hook
 *
 * Check if the mod options are check when doing a new reply
 * and save the mod options.
 */
function extraforumperm_save_modoptions()
{
    global $forumpermissions, $post, $thread, $new_thread, $thread_info, $lang, $db, $mybb;

    if (is_moderator($post['fid'] ?? 0, '', $post['uid'] ?? 0)) {
        // the options are already done for moderators
        return;
    }

    $lang->load('datahandler_post', true);

    // small hack for the newthread action
    if (isset($thread_info) && is_array($thread_info)) {
        $thread = array_merge($new_thread, $thread_info);
        $post['modoptions'] = $thread['modoptions'];
    }

    $modoptions = $post['modoptions'];
    $modlogdata['fid'] = $thread['fid'];
    $modlogdata['tid'] = $thread['tid'];

    $forumpermissions = forum_permissions($thread['fid']);

    $update = [];
    if ($forumpermissions['cancloseownthreads'] && $mybb->user['uid'] == $thread['uid']) {
        // Close the thread.
        if ($modoptions['closethread'] == 1 && $thread['closed'] != 1) {
            $update['closed'] = 1;
            log_moderator_action($modlogdata, $lang->thread_closed);
        }

        // Open the thread.
        if ($modoptions['closethread'] != 1 && $thread['closed'] == 1) {
            $update['closed'] = 0;
            log_moderator_action($modlogdata, $lang->thread_opened);
        }
    }

    if ($forumpermissions['canstickyownthreads'] && $mybb->user['uid'] == $thread['uid']) {
        // Stick the thread.
        if ($modoptions['stickthread'] == 1 && $thread['sticky'] != 1) {
            $update['sticky'] = 1;
            log_moderator_action($modlogdata, $lang->thread_stuck);
        }

        // Unstick the thread.
        if ($modoptions['stickthread'] != 1 && $thread['sticky']) {
            $update['sticky'] = 0;
            log_moderator_action($modlogdata, $lang->thread_unstuck);
        }
    }

    // Execute moderation options.
    if ($update) {
        $db->update_query('threads', $update, 'tid=\'' . (int)$thread['tid'] . '\'');
    }
}

/**
 * Implementation of the datahandler_post_validate_thread and datahandler_post_validate_post hook
 *
 * When there are links in the new post, throw an error
 */
function extraforumperm_validatepost(&$datahandler)
{
    global $plugins, $lang;

    load_language();

    $forumpermissions = forum_permissions($datahandler->data['fid']);

    $message = ' ' . $datahandler->data['message'];

    // ignore text between php and code tags because MyBB doesn't parse
    // the content of those tags
    $message = preg_replace("#\[(code|php)\](.*?)\[/\\1\](\r\n?|\n?)#si", '', $message);

    $is_thread = $plugins->current_hook === 'datahandler_post_validate_thread';

    $is_post = !$is_thread;

    if ($is_thread && empty($forumpermissions['can_post_links_in_threads']) ||
        $is_post && empty($forumpermissions['can_post_links'])) {
        $http_links = "#([\>\s\(\)])(http|https|ftp|news){1}://([^\/\"\s\<\[\.]+\.([^\/\"\s\<\[\.]+\.)*[\w]+(:[0-9]+)?(/[^\"\s<\[]*)?)#i";
        $www_links = "#([\>\s\(\)])(www|ftp)\.(([^\/\"\s\<\[\.]+\.)*[\w]+(:[0-9]+)?(/[^\"\s<\[]*)?)#i";
        $url_tags_simple = "#\[url\]([a-z]+?://)([^\r\n\"<]+?)\[/url\]#si";
        $url_tags_simple2 = "#\[url\]([^\r\n\"<]+?)\[/url\]#i";
        $url_tags_complex = "#\[url=([a-z]+?://)([^\r\n\"<]+?)\](.+?)\[/url\]#si";
        $url_tags_complex2 = "#\[url=([^\r\n\"<&\(\)]+?)\](.+?)\[/url\]#si";

        if (
            preg_match($http_links, $message) ||
            preg_match($www_links, $message) ||
            preg_match($url_tags_simple, $message) ||
            preg_match($url_tags_simple2, $message) ||
            preg_match($url_tags_complex, $message) ||
            preg_match($url_tags_complex2, $message)
        ) {
            $datahandler->is_validated = false;
            $datahandler->set_error($lang->error_canpostlinks);
        }
    }

    if (!$forumpermissions['canpostimages']) {
        $img_tags_1 = "#\[img\](\r\n?|\n?)(https?://([^<>\"']+?))\[/img\]#is";
        $img_tags_2 = "#\[img=([0-9]{1,3})x([0-9]{1,3})\](\r\n?|\n?)(https?://([^<>\"']+?))\[/img\]#is";
        $img_tags_3 = "#\[img align=([a-z]+)\](\r\n?|\n?)(https?://([^<>\"']+?))\[/img\]#is";
        $img_tags_4 = "#\[img=([0-9]{1,3})x([0-9]{1,3}) align=([a-z]+)\](\r\n?|\n?)(https?://([^<>\"']+?))\[/img\]#is";

        if (
            preg_match($img_tags_1, $message) ||
            preg_match($img_tags_2, $message) ||
            preg_match($img_tags_3, $message) ||
            preg_match($img_tags_4, $message)
        ) {
            $datahandler->is_validated = false;
            $datahandler->set_error($lang->error_canpostimages);
        }
    }

    if (!$forumpermissions['canpostvideos']) {
        $video_tag = '#\[video=(.*?)\](.*?)\[/video\]#i';

        if (preg_match($video_tag, $message)) {
            $datahandler->is_validated = false;
            $datahandler->set_error($lang->error_canpostvideos);
        }
    }
}

global $groupzerogreater, $grouppermbyswitch;

$groupzerogreater[] = 'extra_maximum_threads';

$grouppermbyswitch['extra_maximum_threads'] = 'canpostthreads';