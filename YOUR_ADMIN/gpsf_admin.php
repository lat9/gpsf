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
    "SELECT code
       FROM " . TABLE_LANGUAGES . "
      ORDER BY code ASC"
);
$language_options = [];
foreach ($available_languages as $next_language) {
    $language_options[] = [
        'id' => $next_language['code'],
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

// -----
// The initial version of GPSF-2 supports zc156 through zc200.  Future versions will be removing
// the 'legacy' stylesheets and javascript provided in previous versions.  As such, determine
// the Zen Cart base version in use to maintain the downwardly-compatible use of this module.
//
$gspf_zc_version = PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR;
$admin_html_head_supported = ($gspf_zc_version >= '1.5.7');
$body_onload = ($admin_html_head_supported === true) ? '' : ' onload="init();"';
?>
<!doctype html>
<html <?= HTML_PARAMS ?>>
<head>
<?php
if ($admin_html_head_supported === true) {
    require DIR_WS_INCLUDES . 'admin_html_head.php';
} else {
?>
<meta charset="<?= CHARSET ?>">
<title><?= TITLE ?></title>
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
<?php
}
?>
</head>
<body<?= $body_onload ?>>
    <?php require DIR_WS_INCLUDES . 'header.php'; ?>
<?php
$gpsf_main_controller = HTTP_SERVER . DIR_WS_CATALOG . FILENAME_GPSF_MAIN_CONTROLLER;
?>
    <h1 class="pageHeading"><?= sprintf(HEADING_TITLE, GPSF_VERSION) ?></h1>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <div>
                    <div class="col-md-8 text-right"><?= GPSF_MAX_MEMORY_TEXT ?></div>
                    <div class="col-md-4"><?= (GPSF_MEMORY_LIMIT === '') ? ini_get('memory_limit') : ((int)GPSF_MEMORY_LIMIT . 'M') ?></div>
                </div>
                <div>
                    <div class="col-md-8 text-right">Maximum input time:</div>
                    <div class="col-md-4"><?= (ini_get('max_input_time') === -1) ? 'Same as below' : ini_get('max_input_time') ?></div>
                </div>
                <div>
                    <div class="col-md-8 text-right"><?= GPSF_MAX_EXECUTION_TIME_TEXT ?></div>
                    <div class="col-md-4"><?= (GPSF_MAX_EXECUTION_TIME === '') ? ini_get('max_execution_time') : GPSF_MAX_EXECUTION_TIME ?></div>
                </div>
                <div>
                    <div class="col-md-8 text-right"><?= GPSF_MAX_PRODUCTS_IN_FEED ?></div>
                    <div class="col-md-4"><?= number_format((float)$maximum_products_in_feed, 0, '', ',') ?></div>
                </div>
                <form method="get" id="feed" action="<?= $gpsf_main_controller ?>.php" class="form-horizontal" target="_blank">
                    <?= zen_draw_hidden_field('key', GPSF_ACCESS_KEY) ?>
                    <?= zen_draw_hidden_field('feed', 'fy_un_tp') ?>
                    <div class="form-group">
                        <?= zen_draw_label(GPSF_MAX_PRODUCTS_TEXT, 'limit', 'class="col-sm-3 control-label"') ?>
                        <div class="col-sm-9">
                            <?= zen_draw_input_field('limit', ((int)GPSF_MAX_PRODUCTS > 0) ? (int)GPSF_MAX_PRODUCTS : '0', 'class="form-control" id="limit"') ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <?= zen_draw_label(GPSF_STARTING_POINT_TEXT, 'offset', 'class="col-sm-3 control-label"') ?>
                        <div class="col-sm-9">
                            <?= zen_draw_input_field('offset', ((int)GPSF_START_PRODUCTS > 0) ? (int)GPSF_START_PRODUCTS : '0', 'class="form-control" id="offset"') ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <?= zen_draw_label(GPSF_CURRENCY_TEXT, 'currency_code', 'class="col-sm-3 control-label"') ?>
                        <div class="col-sm-9">
                            <?= zen_draw_pull_down_menu('currency_code', $currency_options, GPSF_CURRENCY, 'class="form-control" id="currency_code"') ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <?= zen_draw_label(GPSF_LANGUAGE_TEXT, 'language-code', 'class="col-sm-3 control-label"') ?>
                        <div class="col-sm-9">
                            <?= zen_draw_pull_down_menu('language', $language_options, DEFAULT_LANGUAGE, 'class="form-control" id="language-code"') ?>
                        </div>
                    </div>
                    <div class="form-group text-right">
                        <button id="feed-generate" type="submit" class="btn btn-primary"><?= GPSF_BUTTON_GENERATE_FEED ?></button>
                    </div>
                </form>
                <div>
                    <h2><?= GPSF_CRON_URL_TEXT ?></h2>
<?php
$base_cron_url = HTTP_SERVER . DIR_WS_CATALOG . FILENAME_GPSF_MAIN_CONTROLLER . '.php?key=' . GPSF_ACCESS_KEY;
if (count($languages) === 1) {
?>
                    <code><?= 'wget \'' . $base_cron_url . '\'' ?></code>
                    <br>
<?php
} else {
    foreach ($languages as $next_lang) {
?>
                    <code><?= 'wget \'' . $base_cron_url . '&language=' . $next_lang['code'] . '\'' ?></code> (<?= $next_lang['name'] ?>)
                    <br>
<?php
    }
}
?>
                    <br>
                    <p><?= GPSF_CRON_COPY_TEXT ?></p>
                </div>
                <div>
                    <h2><?= GPSF_MERCHANT_CENTER_TEXT ?></h2>
                    <ul>
                        <li><a href="https://www.google.com/retail/solutions/merchant-center/" target="_blank" rel="noreferrer noopener">
                            <?= GPSF_ACCOUNT_LINK_TEXT ?>
                        </a></li>
                        <li><a href="https://www.google.com/support/merchants/bin/answer.py?hl=en&answer=188494#other" target="_blank" rel="noreferrer noopener">
                            <?= GPSF_FEED_SPECIFICATIONS_LINK_TEXT ?>
                        </a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-8">
                <table class="table table-responsive table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center"><?= GPSF_DATE_HEADER ?></th>
                            <th><?= GPSF_FILENAME_HEADER ?></th>
                            <th class="text-center"><?= GPSF_FILESIZE_HEADER ?></th>
                            <th class="text-center"><?= GPSF_ACTION ?></th>
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
                            <td colspan="4" class="text-center"><?= GPSF_NO_FILES_FOUND_TEXT ?></td>
                        </tr>
<?php
} else {
    foreach ($feed_files as $next_file) {
?>
                        <tr>
                            <td class="text-center"><?= date('d/m/Y H:i:s', filemtime($gpsf_directory . $next_file)) ?></td>
                            <td class="upload-file"><a href="<?= HTTP_SERVER . DIR_WS_CATALOG . GPSF_DIRECTORY . $next_file ?>" target="_blank"><?= $next_file ?></a></td>
                            <td class="text-center"><?= number_format((float)(filesize($gpsf_directory . $next_file) / 1024), 2, '.', ',') ?>KB</td>
                            <td class="text-center">
                                <a role="button" class="btn btn-danger btn-sm" href="<?= zen_href_link(FILENAME_GPSF_ADMIN, "file=$next_file&action=delete") ?>">
                                    <?= GPSF_BUTTON_DELETE ?>
                                </a>
                            </td>
                        </tr>
<?php
    }
}
?>
                    </tbody>
                </table>
                <div id="feed-container" class="text-center">
                    <h2 id="feed-text"><?= GPSF_PROCESSING_FEED_TEXT ?></h2>
                    <h3>
                        <?= GPSF_FEED_STARTED_AT ?> <span id="feed-start-time"></span>
                    </h3>
                    <h4>
                        <?= GPSF_ELAPSED_TIME ?> <span id="feed-elapsed-time"></span>
                    </h4>
                </div>
                <div id="feed-output" class="text-center"></div>
            </div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function() {
        jQuery('#feed-container').hide();

        jQuery('#feed').on('submit', function() {
            const addZero = (num) => `${num}`.padStart(2, '0');

            // -----
            // Define the function that provides the feed's elapsed time and then start it.
            //
            function setElapsedTime(totalSeconds)
            {
                if (typeof(totalSeconds) === 'undefined') {
                    totalSeconds = 0;
                }

                if (totalSeconds !== 0 && jQuery('#feed-output').html() != '') {
                    return;
                }

                var hours = Math.floor(totalSeconds / 3600);
                var minutes = Math.floor((totalSeconds - (hours * 3600)) / 60);
                var seconds = totalSeconds - (hours * 3600) - (minutes * 60);

                jQuery('#feed-elapsed-time').text(addZero(hours)+':'+addZero(minutes)+':'+addZero(seconds));

                setTimeout(function() { setElapsedTime(totalSeconds + 1) }, 1000);
            }
            setElapsedTime();

            var date = new Date();
            jQuery('#feed-start-time').text(addZero(date.getHours())+':'+addZero(date.getMinutes())+':'+addZero(date.getSeconds()));

            jQuery('#feed-output').html('');
            jQuery('#feed-generate').prop('disabled', true);
            jQuery('*').css('cursor', 'wait');
            jQuery('#feed-container').show();

            jQuery.get('<?= $gpsf_main_controller . '.php' ?>', jQuery(this).serialize())
            .done(function(data, textStatus, jqXHR) {
                var lockMessage = '';
                jQuery.get('<?= zen_href_link(FILENAME_GPSF_ADMIN) ?>', function(data2) {
                    var availableDownloads = jQuery(data2).find('#feed-files').html();
                    jQuery('#feed-files').html(availableDownloads);
                    if (availableDownloads.indexOf('.xml.lock') >= 0) {
                        lockMessage = '<p class="text-danger">Since an .xml.lock file is present, the feed might have run out of either memory or time.  Check your <code>/logs</code> directory for details.</p>';
                    }
                });
                jQuery('#feed-text').hide();
                jQuery('#feed-output').html(data + lockMessage);
                jQuery('*').css('cursor', 'default');
                jQuery('#feed-generate').prop('disabled', false);
            })
            .fail(function(jqXHR) {
                if (jqXHR.status === 500) {
                    jQuery('#feed-output').html('<p class="text-danger">Request failed, Internal Server Error (500). Check your <code>/logs</code> directory for details.</p>');
                } else if (jqXHR.status === 504) {
                    jQuery('#feed-output').html('<p class="text-danger">Request failed, Gateway Timeout (504). Check your site\'s "Maximum Input Time".  You might need to contact your webhost for assistance.</p>');
                } else {
                    jQuery('#feed-output').html('<p class="text-danger">Request failed, ' + jqXHR.statusText + ' (' + jqXHR.status + ').</p>');
                }

                jQuery('#feed-text').hide();
                jQuery('*').css('cursor', 'default');
                jQuery('#feed-generate').prop('disabled', false);
            });
            return false;
        });
    });
    </script>
    <?php require DIR_WS_INCLUDES . 'footer.php'; ?>
</body>
</html>
<?php require DIR_WS_INCLUDES . 'application_bottom.php'; ?>
