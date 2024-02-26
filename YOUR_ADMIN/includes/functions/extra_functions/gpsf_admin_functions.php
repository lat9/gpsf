<?php
// -----
// Google Product Search Feeder II, admin tool.
// Copyright 2023-2024, https://vinosdefrutastropicales.com
//
// Last updated: v1.0.1
//
/**
 * Based on:
 *
 * @package google product search feeder
 * @copyright Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: google_product_search_functions.php 5 2011-11-17 11:19:31Z numinix $
 */
function gpsf_cfg_pull_down_currencies($currencies_id, $key = '')
{
    global $db;

    $name = (($key !== '') ? "configuration[$key]" : 'configuration_value');
    $currencies = $db->Execute(
        'SELECT code
           FROM '. TABLE_CURRENCIES . '
          ORDER BY code ASC'
    );
    $currencies_array = [];
    foreach ($currencies as $next_currency) {
        $currencies_array[] = [
            'id' => $next_currency['code'],
            'text' => $next_currency['code'],
        ];
    }
    return zen_draw_pull_down_menu($name, $currencies_array, $currencies_id);
}

function gpsf_cfg_pull_down_country_iso3_list($countries_id, $key = '')
{
    global $db;

    $name = (($key !== '') ? "configuration[$key]" : 'configuration_value');
    $countries = $db->Execute(
        'SELECT countries_id, countries_iso_code_3
           FROM ' . TABLE_COUNTRIES . '
          ORDER BY countries_iso_code_3 ASC'
    );
    $countries_array = [];
    foreach ($countries as $next_country) {
        $countries_array[] = [
            'id' => $countries->fields['countries_id'],
             'text' => $countries->fields['countries_iso_code_3'],
        ];
    }
    return zen_draw_pull_down_menu($name, $countries_array, $countries_id);
}
