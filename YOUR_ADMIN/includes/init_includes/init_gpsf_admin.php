<?php
// -----
// An initialization script to install the Google Product Search Feeder II.
// Copyright 2023, https://vinosdefrutastropicales.com
//
// Last updated: v1.0.0
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

define('GPSF_CURRENT_VERSION', '1.0.0-beta1');

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
            ('$configurationGroupTitle', 'Set Google Product Search Feeder II Options', '1', '1')"
    );
    $cgi = $db->Insert_ID(); 
    $db->Execute("UPDATE " . TABLE_CONFIGURATION_GROUP . " SET sort_order = $cgi WHERE configuration_group_id = $cgi");
} else {
    $cgi = $configuration->fields['configuration_group_id'];
}

if (!defined('GPSF_VERSION')) {
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function) 
         VALUES
            ('Version', 'GPSF_VERSION', '0.0.0', 'Version Installed:', $cgi, 0, now(), NULL, 'zen_cfg_read_only('),

            ('Enable?', 'GPSF_ENABLED', 'false', 'Enable the generation of the feed?', $cgi, 0, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Google Merchant Center FTP Username', 'GPSF_USERNAME', '', 'Enter your Google Merchant Center FTP username', $cgi, 1, now(), NULL, NULL),

            ('Google Merchant Center FTP Password', 'GPSF_PASSWORD', '', 'Enter your Google Merchant Center FTP password', $cgi, 2, now(), NULL, NULL),

            ('Google Merchant Center Server', 'GPSF_SERVER', 'uploads.google.com', 'Enter google-feed server<br>default: uploads.google.com', $cgi, 3, now(), NULL, NULL),

            ('Google Merchant Center PASV', 'GPSF_PASV', 'false', 'Turn PASV mode on or off for FTP upload?', $cgi, 4, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Security Key', 'GPSF_ACCESS_KEY', '', 'Enter a random string of numbers and characters to ensure only the admin accesses the file', $cgi, 5, now(), NULL, NULL),

            ('Store Address', 'GPSF_ADDRESS', '', 'Enter your website address', $cgi, 6, now(), NULL, NULL),

            ('Store Description', 'GPSF_DESCRIPTION', '', 'Enter a short description of your store', $cgi, 7, now(), NULL, NULL),

            ('Output File Name', 'GPSF_OUTPUT_FILENAME', 'domain', 'Set the name of your feed output file', $cgi, 8, now(), NULL, NULL),

            ('Compress Feed File', 'GPSF_COMPRESS', 'false', 'Compress Google Merchant Center file', $cgi, 9, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Last Upload Date', 'GPSF_UPLOADED_DATE', '0001-01-01 00:00:00', 'Date and time of the last upload', $cgi, 10, now(), NULL, NULL),

            ('Output Directory', 'GPSF_DIRECTORY', 'feed/google/', 'Set the name of your feed\'s output directory', $cgi, 11, now(), NULL, NULL),

            ('Enable Advanced XML Sanitization', 'GPSF_XML_SANITIZATION', 'false', 'If weird characters are causing your feed to not validate and you have already ensured your Zen Cart has been properly updated to use the UTF-8 charset, try enabling this option.  If this option is already enabled, try disabling it.', $cgi, 12, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Max Execution Time', 'GPSF_MAX_EXECUTION_TIME', '300', 'Override your PHP configuration by entering a max execution time in seconds for the tool (leave blank to disable):', $cgi, 13, now(), NULL, NULL),

            ('Memory Limit', 'GPSF_MEMORY_LIMIT', '128M', 'Override your PHP configuration by entering a memory limit for the tool (i.e. 128M or leave blank to disable):', $cgi, 14, now(), NULL, NULL),

            ('Maximum Products in Feed', 'GPSF_MAX_PRODUCTS', '0', 'Set to 0 for all products.', $cgi, 20, now(), NULL, NULL),

            ('Starting Offset for Partial Feed', 'GPSF_START_PRODUCTS', '0', 'For a partial feed, identify the offset at which the feed starts.  Set to 0 (the default) to start at the beginning.', $cgi, 21, now(), NULL, NULL),

            ('Specific Categories List', 'GPSF_POS_CATEGORIES', '', '<br>Enter a comma-separated list of <code>categories_id</code> values; only products in these categories will be included in the feed.  Leave this setting blank if you have no specific categories.', $cgi, 22, now(), NULL, NULL),

            ('Excluded Categories List', 'GPSF_NEG_CATEGORIES', '', '<br>Enter a comma-separated list of <code>categories_id</code> values.  Any product in one of these categories is excluded from the feed.  Leave this setting blank if you have no categories to exclude.', $cgi, 23, now(), NULL, NULL),

            ('Specific Manufacturers List', 'GPSF_POS_MANUFACTURERS', '', '<br>Enter a comma-separated list of <code>manufacturers_id</code> values; only these manufacturers\' products will be included in the feed.  Leave this setting blank if you have no specific manufacturers.', $cgi, 24, now(), NULL, NULL),

            ('Excluded Manufacturers List', 'GPSF_NEG_MANUFACTURERS', '', '<br>Enter a comma-separated list of <code>manufacturers_id</code> values; any products for these manufacturers will be excluded from the feed.  Leave this setting blank if you have no manufacturers to exclude.', $cgi, 25, now(), NULL, NULL),

            ('Expiration Date Base', 'GPSF_EXPIRATION_BASE', 'now', 'Expiration Date Base:<ul><li>now - add Adjust to current date;</li><li>product - add Adjust to product date (max(date_added, last_modified, date_available))</li></ul>', $cgi, 30, now(), NULL, 'zen_cfg_select_option([\'now\', \'product\'],'),

            ('Expiration Date Adjust', 'GPSF_EXPIRATION_DAYS', '30', 'Expiration Date Adjust in Days.  Note: Leave blank for Google to auto-set', $cgi, 31, now(), NULL, NULL),

            ('Feed Currency', 'GPSF_CURRENCY', 'USD', 'Choose the currency to be used for the feed.', $cgi, 41, now(), NULL, 'gpsf_cfg_pull_down_currencies('),

            ('ID Source (g:id)', 'GPSF_OFFER_ID', 'model', 'Choose the unique identifier to use for each product.  The value will default to the <code>products_id</code> if you choose a value other than <code>id</code> and the associated value is empty for a product. If you choose <b>UPC</b>, <b>ISBN</b> or <b>EAN</b>, ensure that your site has provided this information for the feed!', $cgi, 42, now(), NULL, 'zen_cfg_select_option([\'id\', \'model\', \'UPC\', \'ISBN\', \'EAN\'],'),

            ('Shipping Options', 'GPSF_SHIPPING', '', 'The shipping options available for an item', $cgi, 46, now(), NULL, NULL),

            ('Default Product Condition', 'GPSF_CONDITION', 'new', 'Choose your products\' default condition.', $cgi, 47, now(), NULL, 'zen_cfg_select_option([\'new\', \'used\', \'refurbished\'],'),

            ('Default Product Type', 'GPSF_DEFAULT_PRODUCT_TYPE', '', 'Enter your product type if using default', $cgi, 49, now(), NULL, NULL),

            ('Product Type', 'GPSF_PRODUCT_TYPE', 'top', 'Use top-level, bottom-level or full-category path, or your default setting as product_type?', $cgi, 50, now(), NULL, 'zen_cfg_select_option([\'default\', \'top\', \'bottom\', \'full\'],'),

            ('Default Feed Language', 'GPSF_LANGUAGE', '1', 'Choose the default language used for the feed.', $cgi, 52, now(), NULL, 'gpsf_cfg_pull_down_languages_list('),

            ('Include Product Weight', 'GPSF_WEIGHT', 'true', 'Include a product\'s weight in the feed?', $cgi, 53, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Weight Units', 'GPSF_UNITS', 'lb', 'Choose a unit of weight measure, either pounds OR kilograms.', $cgi, 54, now(), NULL, 'zen_cfg_select_option([\'lb\', \'kg\'],'),

            ('Use Meta Title', 'GPSF_META_TITLE', 'false', 'Use a product\'s meta title (if not empty) as the product\'s feed title?  If set to <em>false</em>, the <code>products_name</code> is used instead.', $cgi, 57, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Use cPath in URL', 'GPSF_USE_CPATH', 'false', 'Use a product\s &quot;cPath&quot; in the <code>g:link</code> feed attribute?', $cgi, 59, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Google Product Category Default', 'GPSF_DEFAULT_PRODUCT_CATEGORY', '', 'Enter a default Google product category from the <a href=\"https://www.google.com/support/merchants/bin/answer.py?answer=160081\" target=\"_blank\" rel=\"noreferrer\">Google Category Taxonomy</a> or leave blank (note: you can override this default setting by creating a Google Product Category attribute as per the documentation):', $cgi, 60, now(), NULL, NULL),

            ('Display Tax', 'GPSF_TAX_DISPLAY', 'false', 'Display tax per product? (US only)', $cgi, 70, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Tax Country', 'GPSF_TAX_COUNTRY', 'US', 'The country an item is taxed in (2-letter ISO CODE)', $cgi, 71, now(), NULL, NULL),

            ('Tax Region', 'GPSF_TAX_REGION', '', 'The geographic region that a tax rate applies to, e.g., in the US, the two-letter state abbreviation, ZIP code, or ZIP code range using * wildcard (examples: CA, 946*)', $cgi, 72, now(), NULL, NULL),

            ('Tax on Shipping', 'GPSF_TAX_SHIPPING', 'n', 'Do you charge tax on shipping, y for yes or n for no - the default value is n', $cgi, 74, now(), NULL, 'zen_cfg_select_option([\'y\', \'n\'],'),

            ('Payments Accepted', 'GPSF_PAYMENT_METHODS', '', 'What payment methods do you accept? Enter them separated by commas.', $cgi, 80, now(), NULL, NULL),

            ('Payment Notes', 'GPSF_PAYMENT_NOTES', '', 'Add payment notes (use this for showing you accept Google Checkout).', $cgi, 81, now(), NULL, NULL),

            ('Select Shipping Method', 'GPSF_SHIPPING_METHOD', 'none', 'Select a shipping method from the drop-down list that is used in your store, or leave as none', $cgi, 90, now(), NULL, 'zen_cfg_select_option([\'flat rate\', \'per item\', \'per weight unit\', \'table rate\', \'zones\', \'none\'],'),

            ('Table Zone ID', 'GPSF_RATE_ZONE', '', 'Enter the table rate ID if using a shipping method that uses table rates:', $cgi, 91, now(), NULL, NULL),

            ('Shipping Country', 'GPSF_SHIPPING_COUNTRY', '88', 'Select the destination country for the shipping rates:', $cgi, 92, now(), NULL, 'gpsf_cfg_pull_down_country_iso3_list('),

            ('Shipping Region', 'GPSF_SHIPPING_REGION', '', 'Enter the destination region within the selected country (state code, or zip with wildcard *):', $cgi, 93, now(), NULL, NULL),

            ('Shipping Service', 'GPSF_SHIPPING_SERVICE', '', 'Enter the shipping service type (i.e. Ground):', $cgi, 94, now(), NULL, NULL),

            ('Pickup', 'GPSF_PICKUP', 'do not display', 'Local pickup available?', $cgi, 95, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\', \'do not display\'],'),

            ('Alternate Image URL', 'GPSF_ALTERNATE_IMAGE_URL', '', 'Add an alternate URL if your images are hosted offsite (e.g. https://www.domain.com/images/).  Your defined image will be appended to the end of this URL.', $cgi, 100, now(), NULL, NULL),

            ('Use Image Handler?', 'GPSF_IMAGE_HANDLER', 'false', 'Resize images using <em>Image Handler</em> if installed?', $cgi, 101, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Skip Duplicate Titles', 'GPSF_SKIP_DUPLICATE_TITLES', 'true', 'Skip duplicate titles, i.e. product\'s names. Required if submitting to Google US.', $cgi, 215, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Include Min quantity on product', 'GPSF_INCLUDE_MIN_QUANITY', 'false', 'Include product min quantity in the feed?', $cgi, 216, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Include Out of Stock', 'GPSF_INCLUDE_OUT_OF_STOCK', 'true', 'Include out of stock items in the feed?', $cgi, 216, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Include Additional Images', 'GPSF_INCLUDE_ADDITIONAL_IMAGES', 'false', 'Include additional images in the feed (setting to true may affect performance and cause timeouts)?', $cgi, 218, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],'),

            ('Shipping Label Source', 'GPSF_SHIPPING_LABEL', 'products', 'Use the products_id or categories_id as the shipping_label field in Google (allows the webmaster to target the value and setup custom shipping rates per product or category within Google Merchant Center).', $cgi, 560, now(), NULL, 'zen_cfg_select_option([\'products\', \'categories\'],'),

            ('Debug', 'GPSF_DEBUG', 'false', 'If set to <code>true</code>, the feed will output messages indicating which products have not been included in the feed due to errors.', $cgi, 1000, now(), NULL, 'zen_cfg_select_option([\'true\', \'false\'],')"
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

if (GPSF_VERSION !== GPSF_CURRENT_VERSION) {
    $db->Execute(
        "UPDATE " . TABLE_CONFIGURATION . "
            SET configuration_value = '" . GPSF_CURRENT_VERSION . "'
          WHERE configuration_key = 'GPSF_VERSION'
          LIMIT 1"
    );
}
