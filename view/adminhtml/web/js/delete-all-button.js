/**
 * Delete All Button with Confirmation
 */
define([
    'jquery',
    'Magento_Ui/js/modal/confirm'
], function ($, confirmation) {
    'use strict';

    return function (config, element) {
        $(element).on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            confirmation({
                title: config.confirmTitle,
                content: config.confirmMessage,
                actions: {
                    confirm: function () {
                        window.location.href = config.url;
                    }
                }
            });

            return false;
        });
    };
});
