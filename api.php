<?php

/**
 * Ensures that the module init file can't be accessed directly, only within the application.
 */
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: API
Description: API addon
Version: 1.2.0
Requires at least: 2.3.*
*/
define('API_MODULE_NAME', 'api');
hooks()->add_action('admin_init', 'api_permissions');
hooks()->add_action('admin_init', 'api_init_menu_items');
hooks()->add_filter('module_api_action_links', 'module_api_action_links');


function module_api_action_links($actions)
{
    //$actions[] = '<a href="' . admin_url('api/settings') . '">' . _l('api_settings') . '</a>';
    $actions[] = '<a href="https://www.purin.at" target="_blank">' . _l('help') . '</a>';

    return $actions;
}

function api_init_menu_items(){
    if (has_permission('api', '', 'view')) {
            
        $CI = &get_instance();

        $CI->app_menu->add_sidebar_menu_item('API', [
            'name'     => _l('api_name'), 
            'collapse' => true,
            'position' => 50,
            'icon'     => 'fa fa-puzzle-piece', 
        ]);

        $CI->app_menu->add_sidebar_children_item('API', [
            'slug'     => 'api_settings', 
            'name'     => _l('api_settings'), 
            'href'     => admin_url('api/settings'),
            'position' => 5,
        ]);

    }
}

/**
* Register activation module hook
*/
register_activation_hook(API_MODULE_NAME, 'api_module_activation_hook');

function api_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(API_MODULE_NAME, [API_MODULE_NAME]);

/**
 * Permissions
 */

function api_permissions()
{
    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];
    register_staff_capabilities('api', $capabilities, _l('api'));
}