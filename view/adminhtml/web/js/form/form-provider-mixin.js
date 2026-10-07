/**
 * Form Provider Mixin to handle file uploads
 */
define([
    'jquery',
    'mage/utils/wrapper'
], function ($, wrapper) {
    'use strict';

    return function (FormProvider) {
        /**
         * Override save method to handle file uploads
         */
        FormProvider.prototype.save = wrapper.wrap(
            FormProvider.prototype.save,
            function (originalSave, options) {
                var formElement = $('[data-role=content]').find('form').first();
                var fileInputs = formElement.find('input[type="file"]');
                var hasFiles = false;

                // Check if any file inputs have files
                fileInputs.each(function () {
                    if (this.files && this.files.length > 0) {
                        hasFiles = true;
                        return false; // break
                    }
                });

                // If files are present, use native form submission
                if (hasFiles) {
                    // Set form enctype
                    formElement.attr('enctype', 'multipart/form-data');

                    // Collect and inject grid data before submission

                    // Collect product IDs
                    if (window.getAttachmentProducts) {
                        var productIds = window.getAttachmentProducts();
                        if (productIds && productIds.length > 0) {
                            var productInput = formElement.find('input[name="product_ids"]');
                            if (!productInput.length) {
                                productInput = $('<input>').attr({
                                    type: 'hidden',
                                    name: 'product_ids'
                                }).appendTo(formElement);
                            }
                            productInput.val(productIds.join(','));
                        }
                    }

                    // Collect page IDs
                    if (window.getAttachmentPages) {
                        var pageIds = window.getAttachmentPages();
                        if (pageIds && pageIds.length > 0) {
                            var pageInput = formElement.find('input[name="page_ids"]');
                            if (!pageInput.length) {
                                pageInput = $('<input>').attr({
                                    type: 'hidden',
                                    name: 'page_ids'
                                }).appendTo(formElement);
                            }
                            pageInput.val(pageIds.join(','));
                        }
                    }

                    // Collect category IDs
                    if (window.updateSelectedCategories) {
                        var categoryIds = window.updateSelectedCategories();
                        if (categoryIds && categoryIds.length > 0) {
                            var categoryInput = formElement.find('input[name="category_ids"]');
                            if (!categoryInput.length) {
                                categoryInput = $('<input>').attr({
                                    type: 'hidden',
                                    name: 'category_ids'
                                }).appendTo(formElement);
                            }
                            categoryInput.val(JSON.stringify(categoryIds));
                        }
                    }

                    // Add back parameter to return to edit page
                    if (!formElement.find('input[name="back"]').length) {
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'back',
                            value: 'edit'
                        }).appendTo(formElement);
                    }

                    // Submit the form natively
                    formElement.submit();
                    return;
                }

                // No files, use AJAX submission
                // Collect and inject grid data before AJAX submission

                // Collect product IDs
                if (window.getAttachmentProducts) {
                    var productIds = window.getAttachmentProducts();
                    if (productIds && productIds.length > 0 && this.source && this.source.set) {
                        this.source.set('data.product_ids', productIds.join(','));
                    }
                }

                // Collect page IDs
                if (window.getAttachmentPages) {
                    var pageIds = window.getAttachmentPages();
                    if (pageIds && pageIds.length > 0 && this.source && this.source.set) {
                        this.source.set('data.page_ids', pageIds.join(','));
                    }
                }

                // Collect category IDs
                if (window.updateSelectedCategories) {
                    var categoryIds = window.updateSelectedCategories();
                    if (categoryIds && categoryIds.length > 0 && this.source && this.source.set) {
                        this.source.set('data.category_ids', JSON.stringify(categoryIds));
                    }
                }

                return originalSave.call(this, options);
            }
        );

        return FormProvider;
    };
});
