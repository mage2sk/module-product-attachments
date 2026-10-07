/**
 * Form Data Interceptor for Product Attachments
 *
 * Intercepts UI Component form data before AJAX submission
 * and injects product/category/page IDs
 */
define([
    'jquery',
    'Magento_Ui/js/form/form',
    'uiRegistry'
], function ($, Form, registry) {
    'use strict';

    return Form.extend({
        /**
         * Submit form
         */
        submit: function () {
            // Collect product IDs - always set if function exists (even if empty)
            if (window.getAttachmentProducts) {
                var productIds = window.getAttachmentProducts();
                if (productIds && productIds.length > 0) {
                    this.source.set('data.product_ids', productIds.join(','));
                } else {
                    // User opened tab but deselected all - set to empty string to clear relations
                    this.source.set('data.product_ids', '');
                }
            }

            // Collect page IDs - always set if function exists (even if empty)
            if (window.getAttachmentPages) {
                var pageIds = window.getAttachmentPages();
                if (pageIds && pageIds.length > 0) {
                    this.source.set('data.page_ids', pageIds.join(','));
                } else {
                    // User opened tab but deselected all - set to empty string to clear relations
                    this.source.set('data.page_ids', '');
                }
            }

            // Collect category IDs - always set if function exists (even if empty)
            if (window.updateSelectedCategories) {
                var categoryIds = window.updateSelectedCategories();
                if (categoryIds && categoryIds.length > 0) {
                    this.source.set('data.category_ids', JSON.stringify(categoryIds));
                } else {
                    // User opened tab but deselected all - set to empty string to clear relations
                    this.source.set('data.category_ids', '');
                }
            }

            // Call parent submit
            return this._super();
        }
    });
});
