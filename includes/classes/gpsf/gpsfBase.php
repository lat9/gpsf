<?php
// -----
// A base class for optional extensions to the Google Product Search Feeder II for Zen Carts 1.5.6b and later.
// Copyright (C) 2023, https://vinosdefrutastropicales.com
//
// Last updated: v1.0.0
//
class gpsfBase
{
    // -----
    // Issued from gpsfFeedGenerator::getAdditionalQueryFields to see if there are additional
    // site-specific database fields and/or tables to include in the feed's product-gathering
    // query.
    //
    // The base query includes the following tables and fields, with programmatically-determined
    // additions as identified in the parameters passed:
    //
    // - TABLE_PRODUCTS (p):
    //      p.products_id, p.products_model, , p.products_image, p.products_tax_class_id, p.products_price,
    //      p.products_priced_by_attribute, p.products_type, p.master_categories_id,
    //      GREATEST(p.products_date_added, IFNULL(p.products_last_modified, 0), IFNULL(p.products_date_available, 0)) AS base_date,
    //      p.products_date_available, p.products_quantity, p.products_weight, p.product_is_always_free_shipping
    // - TABLE_PRODUCTS_DESCRIPTION (pd):
    //     pd.products_description, pd.products_name
    // - TABLE_MANUFACTURERS (m):
    //     m.manufacturers_name
    // - TABLE_PRODUCT_TYPES (pt):
    //     pt.type_handler
    //
    // Returns a simple array containing:
    //
    // $additional_fields
    //    Contains a comma-separated string that identifies any additional fields to
    //    be gathered from the database.
    // $additional_tables
    //    Contains a string of additional LEFT JOIN clauses to include additional
    //    database tables in the query.
    // $additional_where_clause
    //    Contains a string of additional 'AND' elements to be added to the feed's database query.
    //
    public function getAdditionalQueryFields(string $additional_fields_base, string $additional_tables_base):array
    {
        $additional_fields = '';
        $additional_tables = '';
        $additional_where_clause = '';

        return [
            $additional_fields,
            $additional_tables,
            $additional_where_clause,
        ];
    }

    // -----
    // Gives an extension the opportunity to bypass a product for the feed, with
    // a message returned.
    //
    // The extension returns an empty string ('') if the product is to be included or
    // a message (which is 'logged' if the GPSF debug is enabled.
    //
    public function bypassProductInFeed(string $products_id, array $product):string
    {
        return '';
    }

    // -----
    // Gives an extension a means to override the specified product's
    // g:id value for the feed.
    //
    // The input $id value is either a string if a value was found or (bool)false otherwise.
    //
    // Returning (bool)false will cause the product to be not included in the feed.
    //
    public function getProductsFeedId(string $products_id, $id, array $product):string
    {
        return $id;
    }

    // -----
    // Gives an extension the means to modify a product's description; for example, the description
    // could be appended with an additional field.
    //
    public function modifyProductsDescription(string $products_id, string $products_description, array $product):string
    {
        return $products_description;
    }

    // -----
    // Gives an extension the means to insert additional feed-related attributes, e.g. 'color or 'adult' into
    // the product's feed for the product identified as $products_id.
    //
    // The input $product array contains the database information retrieved for the product, possibly including
    // extension-specific values as identified in the 'getAdditionalQueryFields' method's return.
    //
    // The input $categories_list array contains the name(s) of the product's parent category-chain; element 0
    // contains the product's topmost category's name.
    //
    // The input $cPath array conains the category id value(s) of the product's parent category-chain; element 0
    // contains the product's topmost category ID.
    //
    // The input $custom_fields array identifies any custom fields already gathered.
    //
    // The output array from this method is merged with the $custom_fields input and, thus, will overwrite
    // any previously-set value!
    //
    // Note: It is the extension's responsibility to properly format any non-string attribute type.  For
    // example, the 'adult' attribute should be set to (string)'true'/'false' values, not (bool)true/false.
    // Any URL-type attributes returned should be have spaces (' ') converted to %20 and ampersands (&) converted
    // to %26.
    //
    // If an attribute is set to (bool)false, the feed's associated value will not be included.
    //
    public function getProductsAttributes(string $products_id, array $product, array $categories_list, array $cPath, array $custom_fields):array
    {
        $extension_custom_fields = [];

        return $extension_custom_fields;
    }

    // -----
    // Either returns an extension-specific value associated with the product's shipping cost or (float)-1.0
    // to have the 'base' feed calculations apply.
    //
    public function getProductsShippingRate(string $products_id, $products_weight, $products_price, string $product_is_always_free_shipping):float
    {
        return -1.0;
    }

    // -----
    // Gives extension customizations to provide an override for a specified product's pricing.
    //
    // If no override is necessary, this method simply returns the input $price and $sale_price in
    // a simple array.
    //
    public function getProductPricing(string $products_id, array $product, $price, $sale_price):array
    {
        return [
            $price,
            $sale_price
        ];
    }

    // -----
    // Gives an extension a means to override the determination/generation of a product's image
    // for the feed.
    //
    // If no override is necessary, this method returns (bool)false.  Otherwise it returns either the string
    // URL associated with the specified image or null if the image is not present and the product
    // should not be included in the feed.
    //
    public function getProductsImageUrl(string $products_image)
    {
        return false;
    }

    // -----
    // Gives an extension a means to override the determination/generation of a product's additional
    // image URLs for the feed.
    //
    // If no override is necessary, this method returns (bool)false, otherwise it returns an array of
    // additional-image URLs to be included in the feed.
    //
    public function getProductsAdditionalImagesUrls(string $products_image)
    {
        return false;
    }
}
