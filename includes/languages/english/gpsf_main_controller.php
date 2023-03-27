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
define('TEXT_GPSF_FEED_COMPLETE', 'Product Feed Complete');
define('TEXT_GPSF_FEED_TIMER', 'Time:');
define('TEXT_GPSF_FEED_SECONDS', 'Seconds');
define('TEXT_GPSF_FEED_RECORDS', ' Records Processed');
define('GPSF_TIME_TAKEN', 'In');

define('ERROR_GPSF_DIRECTORY_NOT_WRITEABLE', 'Your Google Product Search folder is not writeable! Please chmod the /' . GPSF_DIRECTORY . ' folder to 755 or 777 depending on your host.');
define('ERROR_GPSF_DIRECTORY_DOES_NOT_EXIST', 'Your Google Product Search output directory does not exist! Please create a /' . GPSF_DIRECTORY . ' directory and chmod to 755 or 777 depending on your host.');
define('ERROR_GPSF_OPEN_FILE', 'Error opening Google Product Search output file "' . DIR_FS_CATALOG . GPSF_DIRECTORY . GPSF_OUTPUT_FILENAME . '"');

define('TEXT_GPSF_UPLOAD_STARTED', 'Upload started...');
define('TEXT_GPSF_UPLOAD_FAILED', 'Upload failed...');
define('TEXT_GPSF_UPLOAD_OK', 'Upload ok!');

define('GPSF_FTP_FAILED', 'Your hosting does not support FTP functions.');
define('GPSF_FTP_CONNECTION_FAILED', 'FTP connection failed:');
define('GPSF_FTP_CONNECTION_OK', 'FTP connection successful:');
define('GPSF_FTP_LOGIN_FAILED', 'FTP login failed:');
define('GPSF_FTP_LOGIN_OK', 'FTP login was successful.');
define('GPSF_FTP_CURRENT_DIRECTORY', 'FTP current directory is:');
define('GPSF_FTP_UPLOAD_FAILED', 'FTP upload failed.');
define('GPSF_FTP_UPLOAD_SUCCESS', 'FTP upload successful.');
