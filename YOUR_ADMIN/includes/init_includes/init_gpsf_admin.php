<?php
// -----
// An initialization script to install the Google Product Search Feeder II.
// Copyright 2023-2024, https://vinosdefrutastropicales.com
//
// Last updated: v1.0.1
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

define('GPSF_CURRENT_VERSION', '1.0.1-beta1');

// -----
// Nothing to do if an admin is not currently logged-in or if the plugin's currently installed
// and at the current version.
//
if (empty($_SESSION['admin_id']) || (defined('GPSF_VERSION') && GPSF_VERSION === GPSF_CURRENT_VERSION)) {
    return;
}

$configurationGroupTitle = 'Google Product Search Feeder II';
$configuration = $db->Execute("SELECT configuration_group_id FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_title = '$configurationGroupTitle' LIMIT 1");
if ($configuration->EOF) {
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION_GROUP . " 
            (configuration_group_title, configuration_group_description, sort_order, visible) 
         VALUES 
            ('$configurationGroupTitle', 'Set Google Product Search Feeder II Options', 1, 1)"
    );
    $cgi = $db->Insert_ID(); 
    $db->Execute("UPDATE " . TABLE_CONFIGURATION_GROUP . " SET sort_order = $cgi WHERE configuration_group_id = $cgi LIMIT 1");
} else {
    $cgi = $configuration->fields['configuration_group_id'];
}

if (!defined('GPSF_VERSION')) {
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function) 
         VALUES
            ('Version', 'GPSF_VERSION', '0.0.0', 'Version Installed:', $cgi, 0, now(), NULL, 'zen_cfg_read_only('),

            ('Enable?', 'GPSF_ENABLED', 'false', '<br>Enable the generation of the feed?', $cgi, 1, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Security Key', 'GPSF_ACCESS_KEY', '', '<br>Enter a random string of numbers and characters to ensure only the authorized users can access the feed.<br>', $cgi, 14, now(), NULL, NULL),

            ('Max Execution Time', 'GPSF_MAX_EXECUTION_TIME', '300', '<br>Override your PHP configuration by entering a max execution time in seconds for the tool. Leave blank to use your site\'s default.<br>', $cgi, 20, now(), NULL, NULL),

            ('Memory Limit', 'GPSF_MEMORY_LIMIT', '', '<br>Override your PHP configuration by entering a memory limit for the tool (e.g. 128M).  Leave blank (the default) to use your site\'s default.<br>', $cgi, 21, now(), NULL, NULL),

            ('Maximum Products in Feed', 'GPSF_MAX_PRODUCTS', '0', '<br>Set to 0 (the default) for all products.<br>', $cgi, 30, now(), NULL, NULL),

            ('Starting Offset for Partial Feed', 'GPSF_START_PRODUCTS', '0', '<br>For a partial feed, identify the offset at which the feed starts.  Set to 0 (the default) to start at the beginning.<br>', $cgi, 32, now(), NULL, NULL),

            ('Output Directory', 'GPSF_DIRECTORY', 'feed/google/', '<br>Set the name of your feed\'s output directory.  Default: <code>feed/google</code><br>', $cgi, 50, now(), NULL, NULL),

            ('Feed File Prefix', 'GPSF_OUTPUT_FILENAME', 'domain', '<br>Identify the first characters used for the filename of the feed\'s output <code>.xml</code> file.  The default (<em>domain</em>) results in feed files named <code>domain_products_*.xml</code>.<br>', $cgi, 52, now(), NULL, NULL),

            ('Compress Feed File', 'GPSF_COMPRESS', 'false', '<br>Compress the feed\'s output .xml file?  Requires the PHP <code>gzip</code> extension to be installed.  Default: <code>false</code>', $cgi, 54, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Feed Currency', 'GPSF_CURRENCY', 'USD', '<br>Choose the currency to be used for the feed.<br>', $cgi, 100, now(), NULL, 'gpsf_cfg_pull_down_currencies('),

            ('Default Feed Language ID', 'GPSF_LANGUAGE', '1', '<br>Choose the default language used for the feed.<br>', $cgi, 102, now(), NULL, 'gpsf_cfg_pull_down_languages_list('),

            ('Skip Duplicate Titles', 'GPSF_SKIP_DUPLICATE_TITLES', 'true', '<br>Skip duplicate titles, i.e. product\'s names. Required if submitting to Google US. Default: <code>true</code>.', $cgi, 200, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Enable Advanced XML Sanitization', 'GPSF_XML_SANITIZATION', 'false', '<br>If weird characters are causing your feed to not validate and you have already ensured your Zen Cart has been properly updated to use the UTF-8 charset, try enabling this option.  If this option is already enabled, try disabling it.', $cgi, 202, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Specific Categories List', 'GPSF_POS_CATEGORIES', '', '<br>Enter a comma-separated list of <code>categories_id</code> values; only products in these categories will be included in the feed.  Leave this setting blank if you have no specific categories.<br>', $cgi, 204, now(), NULL, NULL),

            ('Excluded Categories List', 'GPSF_NEG_CATEGORIES', '', '<br>Enter a comma-separated list of <code>categories_id</code> values.  Any product in one of these categories is excluded from the feed.  Leave this setting blank if you have no categories to exclude.<br>', $cgi, 206, now(), NULL, NULL),

            ('Specific Manufacturers List', 'GPSF_POS_MANUFACTURERS', '', '<br>Enter a comma-separated list of <code>manufacturers_id</code> values; only these manufacturers\' products will be included in the feed.  Leave this setting blank if you have no specific manufacturers.<br>', $cgi, 208, now(), NULL, NULL),

            ('Excluded Manufacturers List', 'GPSF_NEG_MANUFACTURERS', '', '<br>Enter a comma-separated list of <code>manufacturers_id</code> values; any products for these manufacturers will be excluded from the feed.  Leave this setting blank if you have no manufacturers to exclude.<br>', $cgi, 210, now(), NULL, NULL),

            ('Expiration Date Base', 'GPSF_EXPIRATION_BASE', 'now', '<br>Expiration Date Base:<ul><li>now - add Adjust to current date;</li><li>product - add Adjust to product date (max(date_added, last_modified, date_available))</li></ul>', $cgi, 300, now(), NULL, 'zen_cfg_select_option([\'now\', \'product\'],'),

            ('Expiration Date Adjust', 'GPSF_EXPIRATION_DAYS', '', '<br>Expiration date adjustment in days.  Leave blank for Google to auto-set (the default).<br>', $cgi, 302, now(), NULL, NULL),

            ('ID Source (g:id)', 'GPSF_OFFER_ID', 'id', '<br>Choose the unique identifier to use for each product.  If you choose <code>model</code>, any product with an empty <code>products_model</code> will be skipped for the generated feed.', $cgi, 400, now(), NULL, 'zen_cfg_select_option([\'id\', \'model\'],'),

            ('Using Minimum Order Quantity?', 'GPSF_INCLUDE_MIN_QUANTITY', 'false', '<br>If your site has products with a <em>Product Qty Minimum</em> other than <b>1</b>, should a product\'s minimum order-quantity be considered when determining if a product is out-of-stock?  Default: <b>false</b>.', $cgi, 402, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Include Out of Stock', 'GPSF_INCLUDE_OUT_OF_STOCK', 'true', '<br>Include out of stock items in the feed?  Default: <code>true</code>', $cgi, 404, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Default Product Condition', 'GPSF_CONDITION', 'new', '<br>Choose your products\' default condition. Default: <em>new</em>.', $cgi, 406, now(), NULL, 'zen_cfg_select_option([\'new\', \'used\', \'refurbished\'],'),

            ('Product Type', 'GPSF_PRODUCT_TYPE', 'top', '<br>Use top-level, bottom-level or full-category path, or your default setting as product_type?', $cgi, 408, now(), NULL, 'zen_cfg_select_option([\'default\', \'top\', \'bottom\', \'full\'],'),

            ('Default Product Type', 'GPSF_DEFAULT_PRODUCT_TYPE', '', '<br>If you have set <em>Product Type</em> to <code>default</code>, identify the default product type.<br>', $cgi, 410, now(), NULL, NULL),

            ('Include Product Weight', 'GPSF_WEIGHT', 'true', '<br>Include a product\'s weight in the feed? Default: <em>true</em>.', $cgi, 412, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Weight Units', 'GPSF_UNITS', 'lb', '<br>Choose a unit of weight measure, either pounds (the default) or kilograms.', $cgi, 414, now(), NULL, 'zen_cfg_select_option([\'lb\', \'kg\'],'),

            ('Use Meta Title', 'GPSF_META_TITLE', 'false', '<br>Use a product\'s meta title (if not empty) as the product\'s feed title?  If set to <em>false</em> (the default), the <code>products_name</code> is used instead.', $cgi, 416, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Use cPath in URL', 'GPSF_USE_CPATH', 'false', '<br>Use a product\s &quot;cPath&quot; in each product\'s <code>g:link</code> feed attribute? Default: <em>false</em>', $cgi, 418, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Convert Ampersands in Feed Links?', 'GPSF_CONVERT_AMPERSANDS', 'false', '<br>Convert ampersands in feed links to <code>%26</code> (<em>true</em>) or leave as-is (<em>false</em>)?<br><br>Default: <b>false</b>', $cgi, 419, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Google Product Category Default', 'GPSF_DEFAULT_PRODUCT_CATEGORY', '', '<br>Enter a default Google product category from the <a href=\"https://www.google.com/support/merchants/bin/answer.py?answer=160081\" target=\"_blank\" rel=\"noreferrer\">Google Category Taxonomy</a> or leave blank. You can override this default setting by creating a Google Product Category attribute as per the documentation.<br>', $cgi, 420, now(), NULL, NULL),

            ('Display Tax', 'GPSF_TAX_DISPLAY', 'false', '<br>Display tax per product? (US only)? Default: <em>false</em>.', $cgi, 500, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Tax Country', 'GPSF_TAX_COUNTRY', 'US', '<br>The country an item is taxed in (2-letter ISO CODE).<br>', $cgi, 502, now(), NULL, NULL),

            ('Tax Region', 'GPSF_TAX_REGION', '', '<br>The geographic region that a tax rate applies to, e.g., in the US, the two-letter state abbreviation, ZIP code, or ZIP code range using * wildcard (examples: CA, 946*).<br>', $cgi, 504, now(), NULL, NULL),

            ('Tax on Shipping', 'GPSF_TAX_SHIPPING', 'n', '<br>Do you charge tax on shipping, y for yes or n for no (the default).', $cgi, 506, now(), NULL, 'zen_cfg_select_option([\'y\', \'n\'],'),

            ('Select Shipping Method', 'GPSF_SHIPPING_METHOD', 'none', '<br>Select a shipping method from the drop-down list that is used in your store, or leave as <code>none</code> (the default).', $cgi, 702, now(), NULL, 'zen_cfg_select_option([\'flat rate\', \'per item\', \'per weight unit\', \'table rate\', \'zones\', \'none\'],'),

            ('Shipping Zone ID', 'GPSF_RATE_ZONE', '', '<br>Enter the <em>zone id</em> to use if the selected shipping method is <code>zones</code> or if you have an extension that supplies zone-based shipping rates.<br>', $cgi, 704, now(), NULL, NULL),

            ('Shipping Country', 'GPSF_SHIPPING_COUNTRY', '223', '<br>Select the destination country for the shipping rates.  Default: 223 (USA).<br>', $cgi, 706, now(), NULL, 'gpsf_cfg_pull_down_country_iso3_list('),

            ('Shipping Region', 'GPSF_SHIPPING_REGION', '', '<br>Enter the destination region within the selected country (state code, or zip with wildcard *).<br>', $cgi, 708, now(), NULL, NULL),

            ('Shipping Service', 'GPSF_SHIPPING_SERVICE', '', '<br>Enter the shipping service type (e.g. Ground).<br>', $cgi, 710, now(), NULL, NULL),

            ('Shipping Label Source', 'GPSF_SHIPPING_LABEL', 'products', '<br>Use the products_id or categories_id as the shipping_label field in Google (allows the webmaster to target the value and setup custom shipping rates per product or category within Google Merchant Center).', $cgi, 716, now(), NULL, 'zen_cfg_select_option([\'products\', \'categories\'],'),

            ('Alternate Image URL', 'GPSF_ALTERNATE_IMAGE_URL', '', '<br>Add an alternate URL if your images are hosted offsite (e.g. https://www.domain.com/images/).  Your defined image will be appended to the end of this URL, so don\'t forget the trailing slash!<br>', $cgi, 800, now(), NULL, NULL),

            ('Use Image Handler?', 'GPSF_IMAGE_HANDLER', 'false', '<br>Resize images using <em>Image Handler</em> if installed? <b>Note:</b> Setting to true might affect the feed\'s performance and cause timeouts!', $cgi, 802, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Include Additional Images', 'GPSF_INCLUDE_ADDITIONAL_IMAGES', 'false', '<br>Include additional images in the feed?  <b>Note:</b> Setting to true might affect the feed\'s performance and cause timeouts!', $cgi, 804, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Debug', 'GPSF_DEBUG', 'false', '<br>If set to <code>true</code>, the feed will output messages indicating which products have not been included in the feed due to errors.', $cgi, 5000, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Debug: Maximum Skipped Products', 'GPSF_DEBUG_MAX_SKIPPED', '1000', '<br>If Debug is enabled, indicate the maximum number of skipped products before the feed terminates. Leave this field blank to continue the feed regardless the number of skipped products. Default: 1000.<br>', $cgi, 5002, now(), NULL, NULL)"
    );

    // -----
    // Register the plugin's configuration and tools pages for the admin menus.
    //
    zen_register_admin_page('configGpsf', 'BOX_CONFIGURATION_GPSF', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y');
    zen_register_admin_page('toolGpsf', 'BOX_GPSF', 'FILENAME_GPSF_ADMIN', '', 'tools', 'Y');

    // -----
    // Let the logged-in admin know that the plugin's been installed.
    //
    define('GPSF_VERSION', '0.0.0');
}

// -----
// Version-specific database adjustments.
//
switch (true) {
    case version_compare(GPSF_VERSION, '1.0.0', '<'):
        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . "
               WHERE configuration_key IN ('GPSF_USERNAME', 'GPSF_PASSWORD', 'GPSF_SERVER', 'GPSF_PASV', 'GPSF_UPLOADED_DATE', 'GPSF_ADDRESS', 'GPSF_DESCRIPTION')"
        );
    default:                                                    //-Fall through from above processing ...
        break;
}

$db->Execute(
    "UPDATE " . TABLE_CONFIGURATION . "
        SET configuration_value = '" . GPSF_CURRENT_VERSION . "'
      WHERE configuration_key = 'GPSF_VERSION'
      LIMIT 1"
);
