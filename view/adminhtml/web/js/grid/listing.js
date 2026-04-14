/**
 * Grid Listing Component
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
define([
    'Magento_Ui/js/grid/listing',
    'Panth_ProductAttachments/js/file-manager-modal',
    'mage/url'
], function (Listing, fileManagerModal, urlBuilder) {
    'use strict';

    return Listing.extend({
        defaults: {
            template: 'ui/grid/listing'
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            this.initFileManagerModal();
            return this;
        },

        /**
         * Initialize file manager modal
         */
        initFileManagerModal: function () {
            // Build proper admin URL with secret key
            var baseUrl = window.location.origin + window.BASE_URL.replace(/\/[^\/]+$/, '');
            var modalUrl = baseUrl + '/productattachments/attachment/filemanager';

            var config = {
                modalUrl: modalUrl
            };

            console.log('[LISTING] File manager modal URL:', modalUrl);
            fileManagerModal(config);
        }
    });
});
