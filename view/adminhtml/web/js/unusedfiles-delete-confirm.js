/**
 * Unused Files Delete All Confirmation
 */
define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function ($, confirmation, $t) {
    'use strict';

    return function () {
        // Wait for DOM to be ready
        $(document).ready(function () {
            // Use event delegation to catch clicks on delete_all button
            $(document).on('click', 'button[data-index="delete_all"]', function (e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                var deleteUrl = $(this).find('.action-menu-item').attr('href') ||
                               $(this).attr('data-url') ||
                               window.location.origin + window.location.pathname.replace('/index/', '/deleteAll/');

                confirmation({
                    title: $t('Delete All Unused Files'),
                    content: $t('Are you sure you want to delete all unused files? This action cannot be undone.'),
                    actions: {
                        confirm: function () {
                            window.location.href = deleteUrl;
                        }
                    }
                });

                return false;
            });
        });
    };
});
