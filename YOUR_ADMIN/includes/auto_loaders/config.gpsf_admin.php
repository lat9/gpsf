<?php
// -----
// Google Product Search Feeder II, admin auto-loader.
// Copyright 2023, https://vinosdefrutastropicales.com
//
// Last updated: v1.0.0
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

$autoLoadConfig[999][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_gpsf_admin.php'
];
