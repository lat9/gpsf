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
    // $additional_fields   Contains a comma-separated string that identifies any additional fields to
    //                      be gathered from the database.
    // $additional_tables   Contains a string of additional LEFT JOIN clauses to include additional
    //                      database tables in the query.
    //
    public function getAdditionalQueryFields(string $additional_fields_base, string $additional_tables_base):array
    {
        $additional_fields = '';
        $additional_tables = '';

        return [
            $additional_fields,
            $additional_tables
        ];
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
    // The input $products array contains the database information retrieved for the product, possibly including
    // extension-specific values as identified in the 'getAdditionalQueryFields' method's return.
    //
    // The input $custom_fields array identifies any custom fields already gathered.
    //
    // The output array from this method is merged with the $custom_fields input and, thus, will overwrite
    // any previously-set value!
    //
    // Note: It is the extension's responsibility to properly format any non-string attribute type.  For
    // example, the 'adult' attribute should be set to (string)'true'/'false' values, not (bool)true/false.
    // Any URL-type attributes returned should be urlencoded.
    //
    public function getProductsAttributes(string $products_id, array $product, array $custom_fields):array
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
}