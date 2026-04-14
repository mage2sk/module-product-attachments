<?php
/**
 * File Manager Controller
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;

class FileManager extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment';

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var AttachmentRepositoryInterface
     */
    protected $attachmentRepository;

    /**
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param LayoutFactory $layoutFactory
     * @param AttachmentRepositoryInterface $attachmentRepository
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        LayoutFactory $layoutFactory,
        AttachmentRepositoryInterface $attachmentRepository
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->layoutFactory = $layoutFactory;
        $this->attachmentRepository = $attachmentRepository;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $attachmentId = (int)$this->getRequest()->getParam('id');
        $resultRaw = $this->resultRawFactory->create();

        if (!$attachmentId) {
            return $resultRaw->setContents('<div class="error-message"><p>Invalid attachment ID.</p></div>');
        }

        try {
            $attachment = $this->attachmentRepository->getById($attachmentId);

            $layout = $this->layoutFactory->create();
            $block = $layout->createBlock(
                \Panth\ProductAttachments\Block\Adminhtml\Attachment\FileManager::class,
                'attachment.filemanager'
            );
            $block->setAttachment($attachment);
            $block->setTemplate('Panth_ProductAttachments::attachment/filemanager.phtml');

            return $resultRaw->setContents($block->toHtml());
        } catch (\Exception $e) {
            return $resultRaw->setContents('<div class="error-message"><p>Error loading attachment: ' . $e->getMessage() . '</p></div>');
        }
    }
}
