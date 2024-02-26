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
 * @copyright Copyright 2007-2008 Numinix Technology http://www.numinix.com
 * @copyright Portions Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: googleProducts.php 24 2012-10-04 19:20:18Z numinix $
 * @author Numinix Technology
 */
class gpsfFeedGenerator
{
    const FEED_OUTPUT_FREQUENCY = 2500;

    protected
        $productsSkipped = [],
        $categoryInfoCache = [],
        $defaultGoogleProductCategory,
        $attributeVariants,

        $extensions,

        $feedParameters = [],

        $fp,
        $currencyCode,
        $currencyValue,
        $xmlWriter,
        $sniffer,

        $products,
        $totalProducts = 0,
        $productsProcessed = 0,
        $taxRates = [],
        $identifiersSet,
        $identifiersList,

        $alternateImageUrl,
        $alternateImageUrlIsLocal;

    public function __construct()
    {
        // -----
        // Various feed-related product attributes that can be specified by (er) Zen Cart
        // attributes.
        //
        $this->attributeVariants = [
            'ads redirect',
            'adult',
            'age group',
            'color',
            'colour',
            'condition',
            'ean',
            'gender',
            'google product category',
            'gtin',
            'isbn',
            'jan',
            'material',
            'pattern',
            'promotion id',
            'size',
            'size type',
            'size system',
            'upc',
        ];

        // -----
        // If there are site/plugin extensions for the feed, load those classes
        // in now for use within the feed's processing.
        //
        $dir_fs_gpsf_classes = DIR_FS_CATALOG . DIR_WS_CLASSES . 'gpsf/';
        if (file_exists($dir_fs_gpsf_classes . 'gpsfBase.php')) {
            $base_loaded = false;
            foreach (glob(DIR_FS_CATALOG . DIR_WS_CLASSES . 'gpsf/*.php') as $next_file) {
                $file_pathinfo = pathinfo($next_file);
                if ($file_pathinfo['basename'] === 'gpsfBase.php') {
                    continue;
                }
                if ($base_loaded === false) {
                    require $dir_fs_gpsf_classes . 'gpsfBase.php';
                    $this->extensions = [];
                }
                require $next_file;
                $this->extensions[] = new $file_pathinfo['filename']();
            }
        }
    }

    public function setFeedParameters($feed_parameters)
    {
        $feed = 'yes';
        $type = 'products';
        $feed_parameters_ok = true;

        if ($feed_parameters !== '') {
            foreach (explode('_', $feed_parameters) as $next_param) {
                if (strpos($next_param, 'f') === 0) {
                    if ($next_param !== 'fy') {
                        $feed = 'no';
                    }
                } elseif (strpos($next_param, 't') === 0) {
                    if ($next_param === 'td') {
                        $type = 'documents';
                    } elseif ($next_param === 'tn') {
                        $type = 'news';
                    } elseif ($next_param !== 'tp') {
                        trigger_error("Unknown 'type' parameter ($feed_parameters) specified.", E_USER_WARNING);
                        $feed_parameters_ok = false;
                        $feed = 'no';
                    }
                }
            }
        }
        $this->feedParameters = [
            'feed' => $feed,
            'type' => $type,
        ];
        return $feed_parameters_ok;
    }
    public function isFeedGeneration()
    {
        return $this->feedParameters['feed'];
    }
    public function getFeedType()
    {
        return $this->feedParameters['type'];
    }

    public function getTotalProducts()
    {
        return $this->totalProducts;
    }
    public function getTotalProductsProcessed()
    {
        return $this->productsProcessed;
    }

    // -----
    // Previously inline in google_product_search.php.  Moving all feed generation
    // into the class.
    //
    public function generateProductsFeed($fp, $limit, $offset)
    {
        global $currencies;

        $this->fp = $fp;

        $this->initializeProductsFeed($limit, $offset);

        // -----
        // Initialize some additional variables to support a feed that skips duplicate titles.
        //
        $skip_duplicate_titles = (GPSF_SKIP_DUPLICATE_TITLES === 'true');
        $last_title = false;

        // -----
        // The initialization has gathered the feed's products into the class'
        // products array, loop through each.
        //
        $this->productsProcessed = 0;
        foreach ($this->products as $product) {
            $products_id = $product['products_id'];
            $products_name = $product['products_name'];

            // -----
            // Check to see if the extension indicates that the current product should be
            // bypassed for the feed.
            //
            if ($this->extensions !== null) {
                $extension_bypass_product = false;
                foreach ($this->extensions as $extension_class) {
                    $extension_message = $extension_class->bypassProductInFeed($products_id, $product);
                    if ($extension_message !== '') {
                        $extension_bypass_product = true;
                        break;
                    }
                }
                if ($extension_bypass_product === true) {
                    if ($this->addSkippedProduct($products_id, $products_name . ": Bypassed by extension ($extension_message).") === false) {
                        break;
                    }
                    continue;
                }
            }

            // -----
            // If the feed's configuration indicates that duplicate titles are to be
            // skipped, skip this product.
            //
            if ($skip_duplicate_titles === true) {
                if ($last_title === $products_name) {
                    if ($this->addSkippedProduct($products_id, $products_name . ': Duplicate title') === false) {
                        break;
                    }
                    continue;
                }
                $last_title = $products_name;
            }

            /* BEGIN GLOBAL ELEMENTS USED IN ALL ITEMS */

            $price = zen_get_products_base_price($products_id);
            $sale_price = $this->getProductsSalePrice($products_id, $price);

            // -----
            // See if any GPSF extensions have updates for the product's pricing.
            //
            if ($this->extensions !== null) {
                foreach ($this->extensions as $extension_class) {
                    list($price, $sale_price) = $extension_class->getProductPricing($products_id, $product, $price, $sale_price);
                }
            }

            // -----
            // For the feed to be valid, an item's price must be greater than 0.
            //
            if ($price <= 0 && $product['products_priced_by_attribute'] !== '1') {
                if ($this->addSkippedProduct($products_id, $products_name . ': price below 0') === false) {
                    break;
                }
                continue;
            }

            // -----
            // Caching the tax rates to reduce database hits.
            //
            if (isset($this->taxRates[$product['products_tax_class_id']])) {
                $tax_rate = $this->taxRates[$product['products_tax_class_id']];
            } else {
                $tax_rate = zen_get_tax_rate($product['products_tax_class_id']);
                $this->taxRates[$product['products_tax_class_id']] = $tax_rate;
            }

            // -----
            // Update the pricing to add tax if DISPLAY_PRICE_WITH_TAX is set to 'true' in the
            // Zen Cart admin.
            //
            $price = zen_add_tax($price, $tax_rate);
            $sale_price = zen_add_tax($sale_price, $tax_rate);

            // -----
            // Update the pricing based on the currently-selected currency.
            //
            $price = $currencies->value($price, true, $this->currencyCode, $this->currencyValue);
            $sale_price = $currencies->value($sale_price, true, $this->currencyCode, $this->currencyValue);

            // -----
            // Determine the product's description, which must be supplied.  Use the base description and
            // then give any defined extension the opportunity to extend that information.
            //
            $products_description = $product['products_description'];
            if ($this->extensions !== null) {
                foreach ($this->extensions as $extension_class) {
                    $products_description = $extension_class->modifyProductsDescription($products_id, $products_description, $product);
                }
            }
            $products_description = $this->sanitizeXml($products_description);
            if (empty($products_description)) {
                if ($this->addSkippedProduct($products_id, $products_name . ': Product description cannot be empty.') === false) {
                    break;
                }
                continue;
            }

            // -----
            // Determine the product's 'title', which must be at least 3 characters long.  This is
            // either its meta-tag title (if enabled and not empty) or the product's name otherwise.
            //
            if (GPSF_META_TITLE === 'true' && !empty($product['metatags_title'])) {
                $products_title = $this->sanitizeXml($product['metatags_title']);
            } else {
                $products_title = $this->sanitizeXml($products_name);
            }
            if (empty($products_title)) {
                if ($this->addSkippedProduct($products_id, $products_name . ': title cannot be empty') === false) {
                    break;
                }
                continue;
            }

            list($categories_list, $cPath) = $this->getCategoryInfo($product['master_categories_id']);
            $cPath_href = (GPSF_USE_CPATH === 'true') ? ('cPath=' . implode('_', $cPath) . '&') : '';
            $link = zen_href_link($product['type_handler'] . '_info', $cPath_href . 'products_id=' . $products_id, 'NONSSL', false);

            $id = false;
            if (GPSF_OFFER_ID === 'id') {
                $id = $products_id;
            } elseif ($product['products_model'] !== '') {
                $id = $this->sanitizeXml($product['products_model']);
            }

            if ($this->extensions !== null) {
                foreach ($this->extensions as $extension_class) {
                    $id = $extension_class->getProductsFeedId($products_id, $id, $product);
                }
            }

            if ($id === false) {
                if ($this->addSkippedProduct($products_id, $products_name . ': no id found for the product, it\'s required!') === false) {
                    break;
                }
                continue;
            }

            $products_image = $this->getProductsImageUrl($product['products_image']);
            if ($products_image === false) {
                if ($this->addSkippedProduct($products_id, $products_name . ': products image (' . $product['products_image'] . ') not found.') === false) {
                    break;
                }
                continue;
            }

            // -----
            // Determine if a product has any 'custom' fields (like size or color)
            // based on its attributes, if present.  Then, if there's a site-specific
            // handler, let that handler make any modifications necessary.
            //
            $custom_fields = (zen_has_product_attributes($products_id, 'false') === false) ? [] : $this->getProductsAttributes($products_id);
            if ($this->extensions !== null) {
                $custom_fields = $this->getExtensionsAttributes($products_id, $product, $custom_fields);
            }

            // -----
            // Set a string version of the identifiers as {xx}[,{xx}]... so that the
            // values can be found with a 'quick' strpos instead of an array lookup.
            //
            $this->identifiersList = '{' . implode('}{', array_keys($custom_fields)) . '}';
            $this->identifiersSet = $custom_fields;

            $this->xmlWriter->startElement('item');

            $this->createBaseProduct($id, $product, $products_title, $tax_rate, $price, $sale_price);

            $this->writeCustomFields($custom_fields);

            // add universal elements/attributes to products
            $this->addUniversalAttributes($product, $products_description, $products_image);

            // finalize item
            $this->xmlWriter->endElement(); // end item

            // -----
            // Increment the number of products output to the feed.  Every so often,
            // flush the XML to the output file so we don't run out of memory.
            //
            $this->productsProcessed++;
            if (($this->productsProcessed % self::FEED_OUTPUT_FREQUENCY) === 0) {
                fwrite($this->fp, $this->xmlWriter->flush(true));
                fflush($this->fp);
             }
        }

        // -----
        // Since all products are now staged for the feed, free up the
        // memory associated with the feed's products.
        //
        unset($this->products);

        // -----
        // Finalize the feed's output, pushing all results to the
        // specified file-pointer.
        //
        $this->finalizeProductsFeed();
    }

    // by checking for an array, sub-attributes of an xml element are now allowed.
    // pRoseLA
    private function writeCustomFields(array $array) {
        foreach ($array as $key => $value) {
            if ($value === false || $key === '') {
                continue;
            }
            if (is_array($value)) {
                $this->xmlWriter->startElement('g:' . $key);
                $this->writeCustomFields($value);
                $this->xmlWriter->endElement();
            } else {
                $this->xmlWriter->writeElement('g:' . $key, $value);
            }
        }
    }

    protected function initializeProductsFeed($limit, $offset)
    {
        global $db, $currencies;

        // -----
        // Determine if the feed's images are located somewhere other than the site's /images
        // directory.
        //
        $this->alternateImageUrl = false;
        $this->alternateImageUrlIsLocal = false;
       if (GPSF_ALTERNATE_IMAGE_URL !== '') {
            if (strpos(GPSF_ALTERNATE_IMAGE_URL, HTTP_SERVER . '/' . DIR_WS_IMAGES) !== 0) {
                $this->alternateImageUrl = GPSF_ALTERNATE_IMAGE_URL;
            } else {
                $this->alternateImageUrlIsLocal = true;
                $this->alternateImageUrl = str_replace(HTTP_SERVER . '/' . DIR_WS_IMAGES, '', GPSF_ALTERNATE_IMAGE_URL);
            }
        }

        // -----
        // Save the current currency that we're to use when generating the feed.
        //
        $this->currencyCode = (isset($_GET['currency_code'])) ? $_GET['currency_code'] : GPSF_CURRENCY;
        $this->currencyValue = $currencies->get_value($this->currencyCode);

        // -----
        // Create the overall XMLWriter instance that's used to output the feed and set the base feed
        // descriptive elements.
        //
        // Note: The rss and channel elements, as well as the overall document, are 'ended' via call to
        // the finalizeProductsFeed method.
        //
        $this->xmlWriter = new XMLWriter();
        $this->xmlWriter->openMemory();
        $this->xmlWriter->startDocument('1.0', 'UTF-8');
        $this->xmlWriter->setIndent(true);
        $this->xmlWriter->startElement('rss');
        $this->xmlWriter->writeAttribute('version', '2.0');
        $this->xmlWriter->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        $this->xmlWriter->startElement('channel');

        $this->xmlWriter->startElement('title');
        $this->xmlWriter->writeCData($this->sanitizeXml(STORE_NAME));
        $this->xmlWriter->endElement();

        $this->xmlWriter->writeElement('link', HTTP_SERVER . DIR_WS_CATALOG);
        $this->xmlWriter->writeElement('description', $this->sanitizeXml(HOME_PAGE_META_DESCRIPTION));
        fwrite($this->fp, $this->xmlWriter->flush(true));
        fflush($this->fp);

        // -----
        // Determine any additional fields and/or tables to be gathered from the database, depending
        // on configuration and extensions' additions.
        //
        list($additional_fields, $additional_tables, $additional_where_clause) = $this->getAdditionalQueryFields();

        // -----
        // Initialize the products' query to pull the fields required for the to-be-generated feed.
        //
        $products_query =
            "SELECT DISTINCT p.products_id, p.products_model, pd.products_description, pd.products_name, p.products_image,
                    p.products_tax_class_id, p.products_price, p.products_priced_by_attribute,
                    p.products_type, p.master_categories_id, GREATEST(p.products_date_added, IFNULL(p.products_last_modified, 0),
                    IFNULL(p.products_date_available, 0)) AS base_date, p.products_date_available, m.manufacturers_name,
                    p.products_quantity, pt.type_handler, p.products_weight, p.product_is_always_free_shipping" . $additional_fields . "
               FROM " . TABLE_PRODUCTS . " p
                    LEFT JOIN " . TABLE_MANUFACTURERS . " m
                        ON p.manufacturers_id = m.manufacturers_id
                    LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd
                        ON p.products_id = pd.products_id
                       AND pd.language_id = " . $_SESSION['languages_id'] . "
                    LEFT JOIN " . TABLE_PRODUCT_TYPES . " pt
                        ON p.products_type = pt.type_id
                    LEFT JOIN " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c
                        ON p2c.products_id = p.products_id" . $additional_tables;

        // -----
        // Create the 'base' WHERE clause for the query.  For a product to be included, it must:
        //
        // - Be enabled
        // - Not be a "Document General" type.
        // - Not be "Call for Price"
        // - Not be free
        // - Contain an image
        //
        $where =
            " WHERE p.products_status = 1
                AND p.products_type != 3
                AND p.product_is_call != 1
                AND p.product_is_free != 1
                AND p.products_image IS NOT NULL
                AND p.products_image != ''
                AND p.products_image != '" . PRODUCTS_IMAGE_NO_IMAGE . "'" .
            $additional_where_clause;

        // -----
        // Now, add additional limitations to products gathered, based on the current configuration.
        //
        if (GPSF_INCLUDE_OUT_OF_STOCK === 'false') {
            $where .= ' AND p.products_quantity > 0';
        }

        if (GPSF_NEG_MANUFACTURERS !== '') {
            $where .= ' AND p.manufacturers_id NOT IN (' . GPSF_NEG_MANUFACTURERS . ')';
        }

        if (GPSF_POS_MANUFACTURERS !== '') {
            $where .= ' AND p.manufacturers_id IN (' . GPSF_POS_MANUFACTURERS . ')';
        }

        if (GPSF_POS_CATEGORIES !== '') {
            $where .= ' AND p2c.categories_id IN (' . GPSF_POS_CATEGORIES . ')';
        }

        if (GPSF_NEG_CATEGORIES !== '') {
            $where .= ' AND p2c.categories_id NOT IN (' . GPSF_NEG_CATEGORIES . ')';
        }

        $order_by = (GPSF_SKIP_DUPLICATE_TITLES === 'true') ? ' ORDER BY pd.products_name ASC, p.products_id ASC' : ' ORDER BY p.products_id ASC';
        $order_by .= $limit . $offset;

        // -----
        // Retrieve all the products to be included in the feed from the database.
        //
        $products_query .= $where . $order_by;
        $this->products = $db->Execute($products_query);
        $this->totalProducts = $this->products->RecordCount();

        // -----
        // Set the feed's default "Google Product Category".
        //
        $this->defaultGoogleProductCategory = (GPSF_DEFAULT_PRODUCT_CATEGORY === '') ? false : $this->sanitizeXml(GPSF_DEFAULT_PRODUCT_CATEGORY);
    }

    protected function getAdditionalQueryFields()
    {
        $additional_fields = '';
        $additional_tables = '';
        $additional_where_clause = '';

        if (GPSF_META_TITLE === 'true') {
            $additional_fields .= ', mtpd.metatags_title';
            $additional_tables .= ' LEFT JOIN ' . TABLE_META_TAGS_PRODUCTS_DESCRIPTION . ' mtpd ON (p.products_id = mtpd.products_id) ';
        }

        if (GPSF_INCLUDE_MIN_QUANTITY === 'true') {
            $additional_fields .= ', p.products_quantity_order_min';
        }

        // -----
        // If the site-specific "helper" function is provided, see if there are any
        // additional fields and/or tables that should be included in the products'
        // gathering query or any additional conditions added to the feed's "where" claues.
        //
        if ($this->extensions !== null) {
            foreach ($this->extensions as $extension_class) {
                list($extension_fields, $extension_tables, $extension_where_clause) = $extension_class->getAdditionalQueryFields($additional_fields, $additional_tables);
                $extension_fields = trim($extension_fields, ',');
                if ($extension_fields !== '') {
                    $additional_fields .= ', ' . $extension_fields;
                }
                $additional_tables .= ' ' . $extension_tables;
                $additional_where_clause .= ' ' . $extension_where_clause;
            }
        }

        return [
            $additional_fields,
            $additional_tables,
            $additional_where_clause,
        ];
    }

    protected function addProductsAdditionalImages($products_image)
    {
       if ($this->extensions !== null) {
            foreach ($this->extensions as $extension_class) {
                $extension_additional_image_urls = $extension_class->getProductsAdditionalImagesUrls($products_image);
                if (!is_array($extension_additional_image_urls)) {
                    continue;
                }
                $images_found = 0;
                foreach ($extension_additional_image_urls as $next_url) {
                    $this->xmlWriter->writeElement('g:additional_image_link', $next_url);
                    $images_found++;
                    if ($images_found === 9) {
                        break;
                    }
                }
                return;
            }
        }

        $image_pathinfo = pathinfo($products_image);

        // prepare image name
        $image_extension = '.' . $image_pathinfo['extension'];
        $image_filename = $image_pathinfo['filename'];

        $image_directory = $image_pathinfo['dirname'];
        if ($image_directory === '.') {
            $image_directory = '';
        } else {
            $image_directory .=  '/';
            $image_filename .= '_';
            if (is_dir(DIR_WS_IMAGES . $image_directory) === false) {
                return;
            }
        }

        $images_found = 0;
        $products_image = DIR_WS_IMAGES . $products_image;
        $formatting_additional_images = true;
        foreach (glob(DIR_WS_IMAGES . $image_directory . $image_filename . '*' . $image_extension) as $next_image) {
            if ($next_image === $products_image) {
                continue;
            }
            $this->xmlWriter->writeElement('g:additional_image_link', $this->getProductsImageUrl(str_replace(DIR_WS_IMAGES, '', $next_image), $formatting_additional_images));
            $images_found++;
            if ($images_found === 9) {
                break;
            }
        }
    }

    // creates the url for the products_image
    protected function getProductsImageUrl($products_image, $formatting_additional_images = false)
    {
        // -----
        // See if an extension wants to override the determination of a product's base image.
        //
        // The method returns:
        //
        // - (bool)false if no override is provided.
        // - (string)URL when returning the image's URL.
        // - null if the product's image, and thus the product, should not be included in the feed.
        //
        if ($formatting_additional_images === false && $this->extensions !== null) {
            foreach ($this->extensions as $extension_class) {
                $extension_image_url = $extension_class->getProductsImageUrl($products_image);
                if ($extension_image_url !== false) {
                    return $extension_image_url ?? false;
                }
            }
        }

        if ($this->alternateImageUrl !== false) {
            if ($this->alternateImageUrlIsLocal === true) {
                $products_image = $this->alternateImageUrl . $products_image;
            } else {
                return $this->alternateImageUrl . $products_image;
            }
        }

        $image_pathinfo = pathinfo($products_image);
        $products_image_extension = '.' . $image_pathinfo['extension'];
        $products_image_base = $image_pathinfo['basename'];
        $products_image_medium = $products_image_base . IMAGE_SUFFIX_MEDIUM . $products_image_extension;
        $products_image_large = $products_image_base . IMAGE_SUFFIX_LARGE . $products_image_extension;

        // check for a large image else use medium else use small
        if (file_exists(DIR_WS_IMAGES . 'large/' . $products_image_large)) {
            $products_image_large = DIR_WS_IMAGES . 'large/' . $products_image_large;
        } elseif (!file_exists(DIR_WS_IMAGES . 'medium/' . $products_image_medium)) {
                $products_image_large = DIR_WS_IMAGES . $products_image;
        } else {
            $products_image_large = DIR_WS_IMAGES . 'medium/' . $products_image_medium;
        }

        // -----
        // If the image isn't found, return (bool)false; it's required!
        //
        if (!file_exists($products_image_large)) {
            return false;
        }

        if (GPSF_IMAGE_HANDLER === 'true' && function_exists('handle_image')) {
            $image_ih = handle_image($products_image_large, '', LARGE_IMAGE_MAX_WIDTH, LARGE_IMAGE_MAX_HEIGHT, '');
            $products_image_link = HTTP_SERVER . DIR_WS_CATALOG . $image_ih[0];
        } else {
            $products_image_link = HTTP_SERVER . DIR_WS_CATALOG . $products_image_large;
        }

        return $this->sanitizeLink($products_image_link);
    }

    protected function sanitizeLink($link)
    {
        $ampersand = (GPSF_CONVERT_AMPERSANDS === 'false') ? '&' : '%26';
        return str_replace(
            [
                ' ',
                '&amp;',
                '&',
            ],
            [
                '%20',
                $ampersand,
                $ampersand,
            ],
            $link
        );
    }

    protected function formatPriceElement($price)
    {
        return number_format($price, 2, '.', '') . ' ' . $this->currencyCode;
    }

    // -----
    // This method, renamed from googleProducts_get_category in the original, now caches
    // the category information based on the master_categories_id as a performance
    // enhancement.
    //
    protected function getCategoryInfo($master_categories_id)
    {
        if (isset($this->categoryInfoCache[$master_categories_id])) {
            $category_names = $this->categoryInfoCache[$master_categories_id]['category_names'];
            $cPath = $this->categoryInfoCache[$master_categories_id]['cPath'];
        } else {
            // build the cPath
            $cPath_array = zen_generate_category_path($master_categories_id);
            $category_names = [];
            $cPath = [];
            $cPath_array[0] = array_reverse($cPath_array[0]);
            foreach ($cPath_array[0] as $category) {
                $category_names[] = $category['text'];
                $cPath[] = $category['id'];
            }
            $this->categoryInfoCache[$master_categories_id] = [
                'category_names' => $category_names,
                'cPath' => $cPath,
            ];
        }
        return [$category_names, $cPath];
    }

    // -----
    // Create a product's "base" feed information (no attributes).  Previously named create_regular_product
    //
    protected function createBaseProduct($id, $product, $products_title, $tax_rate, $price, $sale_price)
    {
        $this->xmlWriter->startElement('g:title');
        $this->xmlWriter->writeCData(substr($products_title, 0, 150-12));
        $this->xmlWriter->endElement();

        $this->xmlWriter->writeElement('g:id', $id);

        $this->xmlWriter->writeElement('g:price', $this->formatPriceElement($price));
        if ($sale_price > 0 && $price > $sale_price) {
            $this->xmlWriter->writeElement('g:sale_price', $this->formatPriceElement($sale_price));
        }

        if (GPSF_TAX_DISPLAY === 'true' && GPSF_TAX_COUNTRY === 'US' && $tax_rate !== '') {
            if (GPSF_TAX_REGION !== '') {
                $regions = explode(',', GPSF_TAX_REGION);
                foreach ($regions as $region) {
                    if (trim($region) === '') {
                        continue;
                    }

                    $this->xmlWriter->startElement('g:tax');
                    $this->xmlWriter->writeElement('g:country', GPSF_TAX_COUNTRY);
                    $this->xmlWriter->writeElement('g:region', trim($region));
                    if (GPSF_TAX_SHIPPING === 'y') {
                        $this->xmlWriter->writeElement('g:tax_ship', 'yes');
                    }
                    $this->xmlWriter->writeElement('g:rate', $tax_rate);
                    $this->xmlWriter->endElement();
                }
            } else {
                $this->xmlWriter->startElement('g:tax');
                $this->xmlWriter->writeElement('g:country', GPSF_TAX_COUNTRY);
                if (GPSF_TAX_SHIPPING === 'y') {
                    $this->xmlWriter->writeElement('g:tax_ship', 'yes');
                }
                $this->xmlWriter->writeElement('g:rate', $tax_rate);
                $this->xmlWriter->endElement();
            }
        }

        if (STOCK_CHECK !== 'true') {
            $this->xmlWriter->writeElement('g:availability', 'in stock');
        } elseif ($product['products_quantity'] > 0) {
            if (isset($product['products_quantity_order_min']) && $product['products_quantity_order_min'] > $product['products_quantity']) {
                $this->xmlWriter->writeElement('g:availability', 'out_of_stock');
            } else {
                $this->xmlWriter->writeElement('g:availability', 'in stock');
            }
        } elseif (STOCK_ALLOW_CHECKOUT !== 'true') {
            $this->xmlWriter->writeElement('g:availability', 'out of stock');
        } elseif ($product['products_date_available'] === null || strtotime($product['products_date_available']) < time()) {
            $this->xmlWriter->writeElement('g:availability', 'in stock');
        } else {
            // -----
            // Format the product's availability date in ISO 8601 format (2024-02-12T00:00:00+00:00),
            // noting that the availability is scheduled at midnight on the date available.
            //
            $this->xmlWriter->writeElement('g:availability_date', date('c', strtotime($product['products_date_available'] . ' 00:00:00')));
            $this->xmlWriter->writeElement('g:availability', 'preorder');
        }

        if (GPSF_WEIGHT === 'true' && $product['products_weight'] > 0) {
            $this->xmlWriter->writeElement('g:product_weight', $product['products_weight'] . ' ' . GPSF_UNITS);
        }

        if (GPSF_SHIPPING_METHOD !== 'none') {
            $shipping_rate = $this->getProductsShippingRate($product['products_id'], $product['products_weight'], $price, $product['product_is_always_free_shipping']);

            if ((float)$shipping_rate >= 0) {
                $this->xmlWriter->startElement('g:shipping');
                if (GPSF_SHIPPING_COUNTRY !== '') {
                    $this->xmlWriter->writeElement('g:country', $this->getCountriesIsoCode2(GPSF_SHIPPING_COUNTRY));
                }
                if (GPSF_SHIPPING_REGION !== '') {
                    $this->xmlWriter->writeElement('g:region', GPSF_SHIPPING_REGION);
                }
                if (GPSF_SHIPPING_SERVICE !== '') {
                    $this->xmlWriter->writeElement('g:service', GPSF_SHIPPING_SERVICE);
                }
                $this->xmlWriter->writeElement('g:price', $this->formatPriceElement($shipping_rate));

                if (GPSF_WEIGHT === 'true' && $product['products_weight'] > 0) {
                    $this->xmlWriter->writeElement('g:shipping_weight', $product['products_weight'] . ' ' . GPSF_UNITS);
                }

                if (GPSF_SHIPPING_LABEL === 'categories') {
                    $this->xmlWriter->writeElement('g:shipping_label', $product['master_categories_id']);
                } else {
                    $this->xmlWriter->writeElement('g:shipping_label', $product['products_id']);
                }

                $this->xmlWriter->endElement();  //- END g:shipping
            }
        }
    }

    // -----
    // Gathers the specified product's feed-related attributes.  Was previously in-line in
    // /google_product_search.php.
    //
    protected function getProductsAttributes($products_id)
    {
        global $db;

        $attributes_info = $db->Execute(
            "SELECT po.products_options_name, pov.products_options_values_name
               FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                    LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov
                        ON pov.products_options_values_id = pa.options_values_id
                       AND pov.language_id = " . $_SESSION['languages_id'] . "
                    LEFT JOIN " . TABLE_PRODUCTS_OPTIONS . " po
                        ON po.products_options_id = pa.options_id
                       AND po.language_id = " . $_SESSION['languages_id'] . "
              WHERE pa.products_id = $products_id
              ORDER BY products_attributes_id ASC"
        );

        $attributes = [];
        foreach ($attributes_info as $next_att) {
            $variant_key = strtolower($next_att['products_options_name']);
            if ($this->isStringInArray($variant_key, $this->attributeVariants) === false) {
                continue;
            }

            // check that we haven't already processed an option for this variant and that the option isn't a
            // default value like please choose or please select (obviously this has limitations)
            $options_name = str_replace(' ', '_', $variant_key);
            if (array_key_exists($options_name, $attributes) || $this->isStringInArray(strtolower($next_att['products_options_values_name']), ['choose', 'please select']) !== false) {
                continue;
            }

            $options_name = ($options_name === 'colour') ? 'color' : $options_name;
            $attributes[$options_name] = strtolower($this->sanitizeXml($next_att['products_options_values_name']));
        }
        return $attributes;
    }

    protected function getExtensionsAttributes(string $products_id, array $product, array $custom_fields): array
    {
        list($categories_list, $cPath) = $this->getCategoryInfo($product['master_categories_id']);
        foreach ($this->extensions as $extension_class) {
            $extension_custom_fields = $extension_class->getProductsAttributes($products_id, $product, $categories_list, $cPath, $custom_fields);
            $new_custom_fields = $this->processCustomFields($extension_custom_fields);
            $custom_fields = array_merge($custom_fields, $new_custom_fields);
        }

        return $custom_fields;
    }

    // allows for sub attributes of xml element.
    // proseLA
    private function processCustomFields(array $array): array
    {
        $return = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $return[$key] = $this->processCustomFields($value);
            } else {
                $key = strtolower($key);
                $return[$key] = ($value === false) ? false : $this->sanitizeXml($value);
            }
        }
        return $return;
    }

    // takes already created $item and adds universal attributes from $products
    protected function addUniversalAttributes($product, $products_description, $products_image)
    {
        $unique_identifiers = 0;

        list($categories_list, $cPath) = $this->getCategoryInfo($product['master_categories_id']);

        // -----
        // If the product's 'brand' has been overridden by a site-specific extension, simply
        // indicate that a unique-identifier has been supplied; other, use the product's
        // manufacturer's name, if supplied.
        //
        if (strpos($this->identifiersList, '{brand}') !== false) {
            $unique_identifiers++;
        } elseif (!empty($product['manufacturers_name'])) {
            $unique_identifiers++;
            $this->xmlWriter->startElement('g:brand');
            $this->xmlWriter->writeCData(substr($this->sanitizeXml($product['manufacturers_name']), 0, 70-12));
            $this->xmlWriter->endElement();
        }

        // -----
        // If the 'product_type' hasn't been overridden by a site-specific extension,
        // determine the default value to be used.
        //
        if (strpos($this->identifiersList, '{product_type}') === false) {
            if (GPSF_PRODUCT_TYPE === 'default' && GPSF_DEFAULT_PRODUCT_TYPE !== '') {
                $product_type = htmlentities(GPSF_DEFAULT_PRODUCT_TYPE);
            } elseif (GPSF_PRODUCT_TYPE === 'top') {
                $product_type = $categories_list[0];
            } elseif (GPSF_PRODUCT_TYPE === 'bottom') {
                $product_type = end($product_type); // sets last category in array as bottom-level
            } elseif (GPSF_PRODUCT_TYPE === 'full') {
                $product_type = implode(' > ', $categories_list);
            } else {
                $product_type = '';
            }
            if (!empty($product_type)) {
                $this->xmlWriter->startElement('g:product_type');
                $this->xmlWriter->writeCData(substr($this->sanitizeXml($product_type), 0, 750));
                $this->xmlWriter->endElement();
            }
        }

        $this->xmlWriter->writeElement('g:image_link', $products_image);
        if (GPSF_INCLUDE_ADDITIONAL_IMAGES === 'true') {
            $this->addProductsAdditionalImages($product['products_image']);
        }

        // only include if less then 30 days as 30 is the max and leaving blank will default to the max
        if ((int)GPSF_EXPIRATION_DAYS !== 0 && (int)GPSF_EXPIRATION_DAYS <= 29) {
            $this->xmlWriter->writeElement('g:expiration_date', $this->getProductsExpirationDate($product['base_date']));
        }

        // -----
        // If the product's link hasn't been overridden by a site-specific extension,
        // add the default value.
        //
        if (strpos($this->identifiersList, '{link}') === false) {
            $cPath_href = (GPSF_USE_CPATH === 'true') ? ('cPath=' . implode('_', $cPath) . '&') : '';
            $link = zen_href_link($product['type_handler'] . '_info', $cPath_href . 'products_id=' . $product['products_id'], 'NONSSL', false);
            $this->xmlWriter->writeElement('g:link', $this->sanitizeLink($link));
        }

        if (strpos($this->identifiersList, '{mpn}') === false) {
            if ($product['products_model'] !== '') {
                $unique_identifiers++;
                $this->xmlWriter->writeElement('g:mpn', $this->sanitizeXml($product['products_model']));
            }
        } elseif ($this->identifiersSet['mpn'] !== false) {
            $unique_identifiers++;
        }

        if (strpos($this->identifiersList, '{gtin}') !== false && $this->identifiersSet['gtin'] !== false) {
            $unique_identifiers++;
        }

        if ($unique_identifiers === 0) {
            $this->xmlWriter->writeElement('g:identifier_exists', 'false');
        }

        if (strpos($this->identifiersList, '{condition}') === false) {
            $this->xmlWriter->writeElement('g:condition', GPSF_CONDITION);
        }

        $this->xmlWriter->startElement('g:description');
        $this->xmlWriter->writeCData(substr($products_description, 0, 5000-12));
        $this->xmlWriter->endElement();

        if ($this->defaultGoogleProductCategory !== false && strpos($this->identifiersList, '{google_product_category}') === false) {
            $this->xmlWriter->startElement('g:google_product_category');
            $this->xmlWriter->writeCData($this->defaultGoogleProductCategory);
            $this->xmlWriter->endElement();
        }
    }

    protected function sanitizeString($str): string
    {
        $str = (string)$str;
        $str = str_replace(
            [
                "\r\n",
                "\r",
                "\n",
                '&nbsp;',
                '</p>',
                '<br />',
                '<br>',
                '<hr />',
                '<hr>',
                '</h1>',
                '</h2>',
                '</h3>',
                '</h4>',
                '</h5>',
                '</h6>',
                '</li>',
                '</tr>',
            ],
            ' ',
            $str
        );
        return trim(strip_tags($str));
    }

    protected function sanitizeXml($str)
    {
        $str = $this->sanitizeString($str);
        if (GPSF_XML_SANITIZATION === 'false') {
            return $str;
        }

        if (function_exists('iconv')) {
            $strout = iconv(CHARSET, 'UTF-8//TRANSLIT', $str);
        } else {
            $str = $this->transcribe_cp1252_to_latin1($str); // transcribe windows characters
            $strout = '';
            for ($i = 0; $i < strlen($str); $i++) {
                $ord = ord($str[$i]);
                if (($ord > 0 && $ord < 32) || ($ord >= 127)) {
                    $strout .= "&#{$ord};";
                } else {
                    switch ($str[$i]) {
                        case '<':
                            $strout .= '&lt;';
                            break;
                        case '>':
                            $strout .= '&gt;';
                            break;
                        //case '&':
                        //$strout .= '&amp;';
                        //break;
                        case '"':
                            $strout .= '&quot;';
                            break;
                        default:
                            $strout .= $str[$i];
                            break;
                    }
                }
            }
        }
        return $strout;
    }

    protected function transcribe_cp1252_to_latin1($cp1252)
    {
        return strtr(
            $cp1252,
            [
              "\x80" => "e",  "\x81" => " ",    "\x82" => "'", "\x83" => 'f',
              "\x84" => '"',  "\x85" => "...",  "\x86" => "+", "\x87" => "#",
              "\x88" => "^",  "\x89" => "0/00", "\x8A" => "S", "\x8B" => "<",
              "\x8C" => "OE", "\x8D" => " ",    "\x8E" => "Z", "\x8F" => " ",
              "\x90" => " ",  "\x91" => "`",    "\x92" => "'", "\x93" => '"',
              "\x94" => '"',  "\x95" => "*",    "\x96" => "-", "\x97" => "--",
              "\x98" => "~",  "\x99" => "(TM)", "\x9A" => "s", "\x9B" => ">",
              "\x9C" => "oe", "\x9D" => " ",    "\x9E" => "z", "\x9F" => "Y"
            ]
        );
    }

    protected function getProductsExpirationDate($base_date)
    {
        if (GPSF_EXPIRATION_BASE === 'now' || $base_date === '0') {
            $expiration_date = time();
        } else {
            $expiration_date = strtotime($base_date);
        }
        $expiration_date += GPSF_EXPIRATION_DAYS * 24 * 60 * 60;

        return date('Y-m-d', $expiration_date);
    }

    // -----
    // Finalize the products' feed by closing the XML elements started by
    // the initializeProductsFeed method.
    //
    protected function finalizeProductsFeed()
    {
        $this->xmlWriter->endElement(); // end channel
        $this->xmlWriter->endElement(); // end rss
        $this->xmlWriter->endDocument(); // end xml

        // -----
        // Write the remaining in-memory XML elements to the output file.
        //
        fwrite($this->fp, $this->xmlWriter->flush(true));
        fflush($this->fp);

        unset($this->xmlWriter);
    }

// SHIPPING FUNCTIONS //

    protected function getCountriesIsoCode2($countries_id)
    {
        global $db;

        $countries_query =
            "SELECT countries_iso_code_2
               FROM " . TABLE_COUNTRIES . "
              WHERE countries_id = " . (int)$countries_id . "
              LIMIT 1";
        $countries = $db->Execute($countries_query);

        return ($countries->EOF) ? '??' : $countries->fields['countries_iso_code_2'];
    }

    protected function getProductsShippingRate($products_id, $products_weight, $products_price, $product_is_always_free_shipping)
    {
        global $currencies;

        // -----
        // See if there's an extension-override for the shipping rate for the product.  If the response is less than 0,
        // then continue on to do the built-in calculations.
        //
        if ($this->extensions !== null) {
            foreach ($this->extensions as $extension_class) {
                $rate = $extension_class->getProductsShippingRate($products_id, $products_weight, $products_price, $product_is_always_free_shipping);
                if ($rate >= 0) {
                    return $rate;
                }
            }
        }

        $rate = -1;
        // skip the calculation for products that are always free shipping
        if ($product_is_always_free_shipping === '1' || $this->currencyCode === '' || empty($this->currencyValue)) {
            $rate = 0;
        } else {
            switch (GPSF_SHIPPING_METHOD) {
                case 'flat rate':
                    $rate = MODULE_SHIPPING_FLAT_COST;
                    break;
                case 'per item':
                    $rate = MODULE_SHIPPING_ITEM_COST + MODULE_SHIPPING_ITEM_HANDLING;
                    break;
                case 'per weight unit':
                    $rate = (MODULE_SHIPPING_PERWEIGHTUNIT_COST * $products_weight) + MODULE_SHIPPING_PERWEIGHTUNIT_HANDLING;
                    break;
                case 'table rate':
                    $rate = $this->numinixGetTableRate($products_weight, $products_price);
                    break;
                case 'zones':
                    $rate = $this->numinixGetZonesRate($products_weight, $products_price, GPSF_RATE_ZONE);
                    break;
                case 'free shipping':
                    $rate = 0;
                    break;
                case 'none':
                default:
                    $rate = -1;
                    break;
            }
        }
        if ($rate >= 0) {
            $rate = $currencies->value($rate, true, $this->currencyCode, $this->currencyValue);
        }
        return $rate;
    }

    protected function numinixGetTableRate($products_weight, $products_price)
    {
        switch (MODULE_SHIPPING_TABLE_MODE) {
            case 'price':
                $rate_basis = $products_price;
                break;
            case 'weight':
                $rate_basis = $products_weight;
                break;
            case 'item':
                $rate_basis = 1;
                break;
        }
        $rate_basis = round($rate_basis, 9);

        $shipping = 0;
        $table_cost = preg_split("/[:,]/" , MODULE_SHIPPING_TABLE_COST);
        for ($i = 0, $n = count($table_cost); $i < $n; $i += 2) {
            if ($rate_basis <= $table_cost[$i]) {
                if (strpos($table_cost[$i+1], '%') !== false) {
                    $shipping = ($table_cost[$i+1] / 100) * $products_price;
                } else {
                    $shipping = $table_cost[$i+1];
                }
                break;
            }
        }

        return $shipping + MODULE_SHIPPING_TABLE_HANDLING;
    }

    protected function numinixGetZonesRate($products_weight, $products_price, $table_zone)
    {
        switch (MODULE_SHIPPING_ZONES_METHOD) {
            case 'Price':
                $rate_basis = $products_price;
                break;
            case 'Weight':
                $rate_basis = round($products_weight, 9);
                break;
            case 'Item':
                $rate_basis = 1;
                break;
        }

        $shipping = 0;

        $zones_cost = constant('MODULE_SHIPPING_ZONES_COST_' . $table_zone);
        $zones_table = preg_split("/[:,]/", $zones_cost);
        for ($i = 0, $n = count($table_cost); $i < $n; $i += 2) {
            if ($rate_basis <= $zones_table[$i]) {
                if (strpos($zones_table[$i+1], '%') !== false) {
                    $shipping = ($zones_table[$i+1] / 100) * $products_price;
                } else {
                    $shipping = $zones_table[$i+1];
                }
                break;
            }
        }

        return $shipping + constant('MODULE_SHIPPING_ZONES_HANDLING_' . $table_zone);
    }

    // =====
    // PRICE FUNCTIONS
    // =====

    // -----
    // Determine the product sale price to include in the feed.  In the original implementation, this method
    // was named google_get_products_actual_price.
    //
    protected function getProductsSalePrice($products_id, $display_normal_price)
    {
        $display_sale_price = zen_get_products_special_price($products_id, false);
        if ($display_sale_price != 0) {
            $products_actual_price = $display_sale_price;
        } else {
            $display_special_price = zen_get_products_special_price($products_id, true);
            if ($display_special_price != 0) {
                $products_actual_price = $display_special_price;
            } else {
                $products_actual_price = $display_normal_price;
            }
        }
        return $products_actual_price;
    }

    public function microtime_float()
    {
       list($usec, $sec) = explode(' ', microtime());
       return ((float)$usec + (float)$sec);
    }

    // returns the array key of first needle in needles found or false
    // offset skips the first X number of elements in the array or the first X characters in a string
    protected function isStringInArray(string $find_string, array $values): bool
    {
        $string_to_find = '@\b' . $find_string . '\b@i';
        foreach ($values as $key => $value) {
            if (preg_match($string_to_find, $value) === 1) {
                return true;
            }
        }
        // we should only get here if nothing was returned
        return false;
    }

    // -----
    // Adds a product to the list of those skipped, if debug is enabled. If debug *is* enabled,
    // also checks to see that the maximum number of skipped products has been reached.
    //
    // Returns:
    //
    // (bool)false if the maximum number of skipped products has been reached.
    // (bool)true if either the debug is not enabled or the maximum number of skipped products
    //      has not been reached.
    //
    protected function addSkippedProduct($products_id, $message): bool
    {
        if (GPSF_DEBUG === 'false') {
            return true;
        }
        $this->productsSkipped[$products_id] = $message;
        if (GPSF_DEBUG_MAX_SKIPPED !== '' && count($this->productsSkipped) > (int)GPSF_DEBUG_MAX_SKIPPED) {
            $this->productsSkipped['max-out'] = 'Maximum number of skipped products reached (' . GPSF_DEBUG_MAX_SKIPPED . '); feed terminating.';
            return false;
        }
        return true;
    }

    public function googleOutputDebug()
    {
        if (GPSF_DEBUG === 'true' && $this->productsSkipped !== []) {
            print('<pre>' . print_r($this->productsSkipped, true) . '</pre>');
        }
    }
}
