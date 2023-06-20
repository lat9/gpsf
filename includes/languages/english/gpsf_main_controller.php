<?php
// -----
// Google Product Search Feeder II, main controller language file.
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
 * @version $Id: google_product_search_defines.php 5 2011-11-17 11:19:31Z numinix $
 */
define('TEXT_GPSF_STARTED', 'Google Product Search Feeder II v%s started ' . date('Y/m/d H:i:s'));
define('TEXT_GPSF_FILE_LOCATION', 'Feed file - ');

//- %f is the number of seconds the feed took to process
define('TEXT_GPSF_FEED_COMPLETE', 'Product Feed Complete in %.6f seconds.  Peak memory usage: %.2fM');

//- %1$u is the total number of products 'examined' for the feed
//- %2$u is the number of products processed
//- %3$u is the number skipped due to error
define('TEXT_GPSF_FEED_PROCESSED', '%1$u total products. %2$u products processed for the feed. %3$u products skipped due to errors.');

define('ERROR_GPSF_DIRECTORY_NOT_WRITEABLE', 'Your Google Product Search folder is not writeable! Please chmod the /' . GPSF_DIRECTORY . ' folder to 755 or 777 depending on your host.');
define('ERROR_GPSF_DIRECTORY_DOES_NOT_EXIST', 'Your Google Product Search output directory does not exist! Please create a /' . GPSF_DIRECTORY . ' directory and chmod to 755 or 777 depending on your host.');
define('ERROR_GPSF_OPEN_FILE', 'Error opening Google Product Search output file "' . DIR_FS_CATALOG . GPSF_DIRECTORY . GPSF_OUTPUT_FILENAME . '"');
