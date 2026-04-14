/**
 * File Manager Modal JS
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, modal, $t) {
    'use strict';

    return function (config) {
        var modalElement = null;
        var modalInstance = null;

        // Use native event listener with capture phase to intercept BEFORE grid handlers
        document.addEventListener('click', function(e) {
            var target = e.target;

            // Find the button (could be the button itself or a child element)
            while (target && target !== document) {
                if (target.classList && target.classList.contains('attachment-files-trigger')) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    handleFileManagerClick(target);
                    return false;
                }
                target = target.parentElement;
            }
        }, true); // TRUE = use capture phase

        function handleFileManagerClick(element) {
            var $element = $(element);
            var attachmentId = $element.data('attachment-id');

            if (!attachmentId) {
                return;
            }

            // Show loading indicator
            $('body').trigger('processStart');

            // Load modal content via AJAX
            $.ajax({
                url: config.modalUrl,
                type: 'GET',
                data: {
                    id: attachmentId
                },
                success: function (response) {
                    $('body').trigger('processStop');

                    // Remove previous modal if exists
                    if (modalInstance && typeof modalInstance.closeModal === 'function') {
                        try {
                            modalInstance.closeModal();
                        } catch (e) {
                            console.log('Error closing previous modal instance:', e);
                        }
                    }
                    if (modalElement && modalElement.length) {
                        modalElement.remove();
                    }

                    // Clean up any leftover modal wrappers
                    $('.attachment-file-manager-modal-wrapper').remove();

                    // Create modal element
                    modalElement = $('<div/>').html(response);
                    modalElement.addClass('attachment-file-manager-modal-wrapper');
                    $('body').append(modalElement);

                    // Initialize modal
                    var options = {
                        type: 'popup',
                        responsive: true,
                        innerScroll: true,
                        title: $t('File Manager'),
                        modalClass: 'attachment-file-manager-modal',
                        buttons: []
                    };

                    modalInstance = modal(options, modalElement);

                    // Store modal instance on the element for later access
                    modalElement.data('modalInstance', modalInstance);

                    modalElement.modal('openModal');

                    // Reload grid when modal is closed
                    modalElement.on('modalclosed', function() {
                        console.log('Modal closed event triggered, reloading grid...');

                        // Trigger grid reload for attachment listing
                        require(['uiRegistry'], function(registry) {
                            var listing = registry.get('attachment_listing.attachment_listing_data_source');
                            if (listing && typeof listing.reload === 'function') {
                                console.log('Grid found, reloading...');
                                listing.reload();
                            } else {
                                console.log('Grid not found in registry');
                            }
                        });

                        // Clean up modal element after a delay to prevent errors
                        setTimeout(function() {
                            if (modalElement && modalElement.length) {
                                modalElement.remove();
                                modalElement = null;
                                modalInstance = null;
                            }
                        }, 100);
                    });
                },
                error: function () {
                    $('body').trigger('processStop');
                    alert($t('Error loading file manager. Please try again.'));
                }
            });
        }

        // Add hover effect for buttons
        $(document).on('mouseenter', '.attachment-files-trigger', function () {
            $(this).css('background-color', '#e9e9e9');
        }).on('mouseleave', '.attachment-files-trigger', function () {
            $(this).css('background-color', '#fff');
        });
    };
});
