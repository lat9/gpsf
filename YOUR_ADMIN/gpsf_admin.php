<?php
// -----
// Google Product Search Feeder II, admin tool.
// Copyright 2023, https://vinosdefrutastropicales.com
//
// Last updated: v1.0.0
//
/**
 * Based on:
 *
 * @package google product search feeder
 * @copyright Copyright 2007 Numinix Technology http://www.numinix.com
 * @copyright Portions Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: google_product_search.php 20 2012-09-21 21:22:20Z numinix $
 */
require 'includes/application_top.php';

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    if (is_file(DIR_FS_CATALOG . GPSF_DIRECTORY . $_GET['file'])) {
        unlink(DIR_FS_CATALOG . GPSF_DIRECTORY . $_GET['file']);
    }
    zen_redirect(zen_href_link(FILENAME_GPSF_ADMIN));
}

$available_languages = $db->Execute(
    "SELECT code, languages_id
       FROM " . TABLE_LANGUAGES . "
      ORDER BY code ASC"
);
$language_options = [];
foreach ($available_languages as $next_language) {
    $language_options[] = [
        'id' => $next_language['languages_id'],
        'text' => $next_language['code'],
    ];
}

$available_currencies = $db->Execute(
    "SELECT code
       FROM " . TABLE_CURRENCIES . "
      ORDER BY code ASC"
);
$currency_options = [];
foreach ($available_currencies as $next_currency) {
    $currency_options[] = [
        'id' => $next_currency['code'],
        'text' => $next_currency['code'],
    ];
}

// -----
// Get a basic count of the number of products that "could be" in the feed, to
// give the admin guidance as to how many products can conceivably be processed
// per feed-file.
//
$products_count = $db->Execute(
    "SELECT COUNT(*) as `total`
       FROM " . TABLE_PRODUCTS . " p
      WHERE p.products_status = 1
        AND p.products_type != 3
        AND p.product_is_call != 1
        AND p.product_is_free != 1
        AND p.products_image IS NOT NULL
        AND p.products_image != ''
        AND p.products_image != '" . PRODUCTS_IMAGE_NO_IMAGE . "'"
);
$maximum_products_in_feed = $products_count->fields['total'];
unset($products_count);

// -----
// If not unset, the variable $key will be filled in for an empty GPSF_ACCESS_KEY!
//
unset($key);
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta charset="<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" href="includes/stylesheet.css">
<link rel="stylesheet" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
<script src="includes/menu.js"></script>
<script src="includes/general.js"></script>
<script>
function init()
{
    cssjsmenu('navbar');
    if (document.getElementById) {
        var kill = document.getElementById('hoverJS');
        kill.disabled = true;
    }
}
</script>
</head>
<body onload="init();">
    <?php require DIR_WS_INCLUDES . 'header.php'; ?>
<?php
$gpsf_main_controller = HTTP_SERVER . DIR_WS_CATALOG . FILENAME_GPSF_MAIN_CONTROLLER;
?>
    <h1 class="pageHeading"><?php echo sprintf(HEADING_TITLE, GPSF_VERSION); ?></h1>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div>
                    <div class="col-md-8 text-right"><?php echo GPSF_MAX_MEMORY_TEXT; ?></div>
                    <div class="col-md-4"><?php echo (int)GPSF_MEMORY_LIMIT . 'M'; ?></div>
                </div>
                <div>
                    <div class="col-md-8 text-right"><?php echo GPSF_MAX_PRODUCTS_IN_FEED; ?></div>
                    <div class="col-md-4"><?php echo number_format((float)$maximum_products_in_feed, 0, '', ','); ?></div>
                </div>
                <form method="get" id="feed" action="<?php echo $gpsf_main_controller; ?>.php" class="form-horizontal" target="_blank">
                    <?php echo zen_draw_hidden_field('key', GPSF_ACCESS_KEY); ?>
                    <?php echo zen_draw_hidden_field('feed', 'fy_un_tp'); ?>
                    <div class="form-group">
                        <?php echo zen_draw_label(GPSF_MAX_PRODUCTS_TEXT, 'limit', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9">
                            <?php echo zen_draw_input_field('limit', ((int)GPSF_MAX_PRODUCTS > 0) ? (int)GPSF_MAX_PRODUCTS : '0', 'class="form-control" id="limit"'); ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <?php echo zen_draw_label(GPSF_STARTING_POINT_TEXT, 'offset', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9">
                            <?php echo zen_draw_input_field('offset', ((int)GPSF_START_PRODUCTS > 0) ? (int)GPSF_START_PRODUCTS : '0', 'class="form-control" id="offset"'); ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <?php echo zen_draw_label(GPSF_CURRENCY_TEXT, 'currency_code', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9">
                            <?php echo zen_draw_pull_down_menu('currency_code', $currency_options, GPSF_CURRENCY, 'class="form-control" id="currency_code"'); ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <?php echo zen_draw_label(GPSF_LANGUAGE_TEXT, 'language_id', 'class="col-sm-3 control-label"'); ?>
                        <div class="col-sm-9">
                            <?php echo zen_draw_pull_down_menu('language_id', $language_options, GPSF_LANGUAGE, 'class="form-control" id="language_id"'); ?>
                        </div>
                    </div>
                    <div class="form-group text-right">
                        <button id="feed-generate" type="submit" class="btn btn-primary"><?php echo GPSF_BUTTON_GENERATE_FEED; ?></button>
                    </div>
                </form>
                <div>
                    <h2><?php echo GPSF_CRON_URL_TEXT; ?></h2>
                    <code><?php echo 'wget \'' . HTTP_SERVER . DIR_WS_CATALOG . FILENAME_GPSF_MAIN_CONTROLLER . '.php?feed=fy_un_tp&key=' . GPSF_ACCESS_KEY . '\''; ?></code>
                    <br>
                    <br>
                    <p><?php echo GPSF_CRON_COPY_TEXT; ?></p>
                </div>
                <div>
                    <h2><?php echo GPSF_MERCHANT_CENTER_TEXT; ?></h2>
                    <ul>
                        <li><a href="https://www.google.com/retail/solutions/merchant-center/" target="_blank" rel="noreferrer noopener">
                            <?php echo GPSF_ACCOUNT_LINK_TEXT; ?>
                        </a></li>
                        <li><a href="https://www.google.com/support/merchants/bin/answer.py?hl=en&answer=188494#other" target="_blank" rel="noreferrer noopener">
                            <?php echo GPSF_FEED_SPECIFICATIONS_LINK_TEXT; ?>
                        </a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-8">
                <table class="table table-responsive table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center"><?php echo GPSF_DATE_HEADER; ?></th>
                            <th><?php echo GPSF_FILENAME_HEADER; ?></th>
                            <th class="text-center"><?php echo GPSF_FILESIZE_HEADER; ?></th>
                            <th class="text-center"><?php echo GPSF_ACTION; ?></th>
                        </tr>
                    </thead>
                    <tbody id="feed-files">
<?php
$gpsf_directory = DIR_FS_CATALOG . GPSF_DIRECTORY;
$feed_files = [];
$found_files = glob($gpsf_directory . '*.*');
if (!empty($found_files)) {
    foreach ($found_files as $next_file) {
        $next_file = str_replace($gpsf_directory, '', $next_file);
        if ($next_file === '.' || $next_file === '..' || $next_file === 'index.html' || $next_file === '.htaccess') {
            continue;
        }
        $feed_files[] = $next_file;
    }
}
if ($feed_files === []) {
?>
                        <tr>
                            <td colspan="4" class="text-center"><?php echo GPSF_NO_FILES_FOUND_TEXT; ?></td>
                        </tr>
<?php
} else {
    $file_href_template = HTTP_SERVER . DIR_WS_CATALOG . FILENAME_GPSF_MAIN_CONTROLLER . '.php?feed=fn_uy&upload_file=%s&key=' . GPSF_ACCESS_KEY; 
    foreach ($feed_files as $next_file) {
?>
                        <tr>
                            <td class="text-center"><?php echo date('d/m/Y H:i:s', filemtime($gpsf_directory . $next_file)); ?></td>
                            <td class="upload-file"><?php echo $next_file; ?></td>
                            <td class="text-center"><?php echo number_format((float)(filesize($gpsf_directory . $next_file) / 1024), 2, '.', ','); ?>KB</td>
                            <td class="text-center">
                                <a role="button" class="btn btn-primary btn-sm upload-feed" href="<?php echo sprintf($file_href_template, $next_file); ?>">
                                    <?php echo GPSF_BUTTON_UPLOAD; ?>
                                </a>
                                <a role="button" class="btn btn-danger btn-sm" href="<?php echo zen_href_link(FILENAME_GPSF_ADMIN, "file=$next_file&action=delete"); ?>">
                                    <?php echo GPSF_BUTTON_DELETE; ?>
                                </a>
                            </td>
                        </tr>
<?php
    }
}
?>
                    </tbody>
                </table>
                <div class="text-center">
                    <h2 id="feed-processing"><?php echo GPSF_PROCESSING_FEED_TEXT; ?></h2>
                    <div id="feed-output">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function() {
        jQuery('#feed-processing').hide();

        jQuery('#feed').on('submit', function() {
            jQuery('#feed-processing').show();
            jQuery('#feed-output').html('');
            jQuery('#feed-generate').prop('disabled', true);
            jQuery('*').css('cursor', 'wait');
            jQuery.get('<?php echo $gpsf_main_controller . '.php'; ?>', jQuery(this).serialize(), function(data) {
                jQuery.get('<?php echo zen_href_link(FILENAME_GPSF_ADMIN); ?>', function(data2) {
                    var availableDownloads = jQuery(data2).find('#feed-files').html();
                    jQuery('#feed-files').html(availableDownloads);
                });
                jQuery('#feed-processing').hide();
                jQuery('#feed-output').html(data);
                jQuery('*').css('cursor', 'default');
                jQuery('#feed-generate').prop('disabled', false);
            });
            return false;
        });

        jQuery('.upload-feed').on('click', function(e) {
            e.preventDefault();
            jQuery('html, body').css('cursor', 'wait!important');
            jQuery.get(jQuery(this).prop('href'), '', function(data) {
                jQuery('#feed-output').html(data);
                jQuery('html, body').css('cursor', 'default!important');

            });
            return false;
        });
    });
    </script>
    <?php require DIR_WS_INCLUDES . 'footer.php'; ?>
</body>
</html>
<?php require DIR_WS_INCLUDES . 'application_bottom.php'; ?>
