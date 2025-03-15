<?php
/**
 * Extra Forum Permission Pack
 * Copyright 2011 Aries-Belgium
 *
 * $Id$
 */

namespace ExtraForumPermissions\Core;

use const ExtraForumPermissions\DEBUG;
use const ExtraForumPermissions\ROOT;

const FIELDS_DATA = [
    'forumpermissions' => [
        'can_rate_own_threads' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
            'form_type' => 'check_box',
            'form_options' => [
                'disabled_for_guest_group' => true
            ]
        ],
        'can_moderate_own_threads' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0,
            'form_type' => 'check_box',
        ],
        'canstickyownthreads' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0,
            'form_type' => 'check_box',
        ],
        'cancloseownthreads' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0,
            'form_type' => 'check_box',
        ],
        'can_post_links_in_threads' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
            'form_type' => 'check_box',
        ],
        'can_post_links' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
            'form_type' => 'check_box',
        ],
        'can_view_links' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
            'form_type' => 'check_box',
        ],
        'canpostimages' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
            'form_type' => 'check_box',
        ],
        'canpostvideos' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
            'form_type' => 'check_box',
        ],
        'extra_subject_length' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 85,
            'form_type' => 'numeric',
            'form_options' => [
                'min' => 1,
                'max' => 255
            ]
        ],
        'extra_maximum_attachments' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0,
            'form_type' => 'numeric',
            'form_options' => [
                'min' => 0,
                'max' => 255
            ]
        ],
        'extra_maximum_threads_per_day' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0,
            'form_type' => 'numeric',
            'form_options' => [
                'min' => 0,
                'max' => 255
            ]
        ],
    ],
    'forums' => [
        'extra_maximum_threads_per_day' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0,
            'form_type' => 'numeric',
            'form_options' => [
                'min' => 0,
                'max' => 255
            ]
        ],
    ],
    'usergroups' => [
        'can_rate_own_threads' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
            'form_type' => 'check_box',
        ],
        'can_moderate_own_threads' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0,
            'form_type' => 'check_box',
        ],
        'canstickyownthreads' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0,
            'form_type' => 'check_box',
        ],
        'cancloseownthreads' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0,
            'form_type' => 'check_box',
        ],
        'can_post_links_in_threads' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
            'form_type' => 'check_box',
        ],
        'can_post_links' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
            'form_type' => 'check_box',
        ],
        'can_view_links' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
            'form_type' => 'check_box',
        ],
        'canpostimages' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
            'form_type' => 'check_box',
        ],
        'canpostvideos' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
            'form_type' => 'check_box',
        ],
        'extra_subject_length' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 85,
            'form_type' => 'numeric',
            'form_options' => [
                'min' => 1,
                'max' => 255
            ]
        ],
        'extra_maximum_attachments' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0,
            'form_type' => 'numeric',
            'form_options' => [
                'min' => 0,
                'max' => 255
            ]
        ],
        'extra_maximum_threads_per_day' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0,
            'form_type' => 'numeric',
            'form_options' => [
                'min' => 0,
                'max' => 255
            ]
        ],
    ]
];

const FIELDS_DATA_CORE = [
    'threads' => [
        'subject' => [
            'type' => 'VARCHAR',
            'size' => 255,
            'default' => '',
        ],
    ],
    'posts' => [
        'subject' => [
            'type' => 'VARCHAR',
            'size' => 255,
            'default' => '',
        ],
    ],
    'forums' => [
        'lastpostsubject' => [
            'type' => 'VARCHAR',
            'size' => 255,
            'default' => '',
        ],
    ],
];

const REGULAR_EXPRESSIONS_URL = [
    "#\[url\]((?!javascript)[a-z]+?://)([^\r\n\"<]+?)\[/url\]#si" => 'parser_url_callback1',
    "#\[url\]((?!javascript:)[^\r\n\"<]+?)\[/url\]#i" => 'parser_url_callback2',
    "#\[url=((?!javascript)[a-z]+?://)([^\r\n\"<]+?)\](.+?)\[/url\]#si" => 'parser_url_callback1',
    "#\[url=((?!javascript:)[^\r\n\"<]+?)\](.+?)\[/url\]#si" => 'parser_url_callback2',
    "~
				<a\\s[^>]*>.*?</a>|								# match and return existing links
				(?<=^|[\s\(\)\[\>])								# character preceding the link
				(?P<prefix>
					(?:http|https|ftp|news|irc|ircs|irc6)://|	# scheme, or
					(?:www|ftp)\.								# common subdomain
				)
				(?P<link>
					(?:[^\/\"\s\<\[\.]+\.)*[\w]+				# host
					(?::[0-9]+)?								# port
					(?:/(?:[^\"\s<\[&]|\[\]|&(?:amp|lt|gt);)*)?	# path, query, fragment; exclude unencoded characters
					[\w\/\)]
				)
				(?![^<>]*?>)									# not followed by unopened > (within HTML tags)
			~iusx" => 'parser_url_callback_auto',
];

function load_language()
{
    global $lang;

    isset($lang->extraforumperm) || $lang->load('extraforumperm');
}

function add_hooks(string $namespace)
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

    foreach ($definedUserFunctions as $callable) {
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;

        if (substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase . '\\') {
            $hookName = substr_replace($callable, '', 0, $namespaceWithPrefixLength);

            $priority = substr($callable, -2);

            if (is_numeric(substr($hookName, -2))) {
                $hookName = substr($hookName, 0, -2);
            } else {
                $priority = 10;
            }

            $plugins->add_hook($hookName, $callable, $priority);
        }
    }
}

function get_template_name(string $template_name = ''): string
{
    $template_prefix = '';

    if ($template_name) {
        $template_prefix = '_';
    }

    return "extraforumpermissions{$template_prefix}{$template_name}";
}

function get_template(string $template_name = '', bool $enable_html_comments = true): string
{
    global $templates;

    if (DEBUG) {
        $file_path = ROOT . "/templates/{$template_name}.html";

        $template_contents = file_get_contents($file_path);

        $templates->cache[get_template_name($template_name)] = $template_contents;
    } elseif (my_strpos($template_name, '/') !== false) {
        $template_name = substr($template_name, strpos($template_name, '/') + 1);
    }

    return $templates->render(get_template_name($template_name), true, $enable_html_comments);
}

function parser_url_callback1($matches): string
{
    return parse_url();
}

function parser_url_callback2($matches): string
{
    return parse_url();
}

function parser_url_callback_auto($matches = array()): string
{
    if (count($matches) == 1) {
        return $matches[0];
    }

    return parse_url();
}

function parse_url(): string
{
    global $lang, $templates;

    load_language();

    return eval(get_template('my_code_url_hidden', false));
}