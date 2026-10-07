<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutFactory;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentFile\CollectionFactory;

class FileList extends Action
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment';

    protected $resultJsonFactory;

    protected $layoutFactory;

    protected $attachmentRepository;

    protected $fileCollectionFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LayoutFactory $layoutFactory,
        AttachmentRepositoryInterface $attachmentRepository,
        CollectionFactory $fileCollectionFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->attachmentRepository = $attachmentRepository;
        $this->fileCollectionFactory = $fileCollectionFactory;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $attachmentId = (int)$this->getRequest()->getParam('attachment_id');

        if (!$attachmentId) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Attachment ID is required')
            ]);
        }

        try {
            $attachment = $this->attachmentRepository->getById($attachmentId);

            $files = $this->fileCollectionFactory->create()
                ->addFieldToFilter('attachment_id', $attachmentId)
                ->setOrder('is_primary', 'DESC')
                ->setOrder('sort_order', 'ASC');

            $html = $this->generateFileGridHtml($files);

            return $resultJson->setData([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Error loading files: %1', $e->getMessage())
            ]);
        }
    }

    protected function generateFileGridHtml($files)
    {
        if ($files->getSize() === 0) {
            return '<div class="fm-no-files"><p>' . __('No files uploaded yet. Use the upload section above to add files.') . '</p></div>';
        }

        $html = '<div class="fm-files-grid" id="files-grid">';
        foreach ($files as $file) {
            $fileId = (int)$file->getFileId();
            $isPrimary = $file->getIsPrimary();
            $filename = $this->escapeHtml($file->getOriginalFilename());
            $fileSize = $this->formatFileSize($file->getFileSize());
            $downloads = (int)$file->getDownloadCount();
            $isPreviewable = $this->isPreviewable($file->getOriginalFilename());

            $html .= '<div class="fm-file-card" data-file-id="' . $fileId . '">';

            if ($isPrimary) {
                $html .= '<span class="fm-primary-badge">PRIMARY</span>';
            }

            $html .= '<div class="fm-file-name">' . $filename . '</div>';
            $html .= '<div class="fm-file-meta">';
            $html .= '<span>' . $fileSize . '</span>';
            $html .= '<span>Downloads: ' . $downloads . '</span>';
            $html .= '</div>';

            $html .= '<div class="fm-file-actions">';
            $html .= '<button type="button" class="fm-btn fm-btn-download" data-file-id="' . $fileId . '">Download</button>';

            if ($isPreviewable) {
                $html .= '<button type="button" class="fm-btn fm-btn-preview" data-file-id="' . $fileId . '" data-filename="' . $this->escapeHtmlAttr($filename) . '">Preview</button>';
            }

            if (!$isPrimary) {
                $html .= '<button type="button" class="fm-btn fm-btn-primary" data-file-id="' . $fileId . '">Set Primary</button>';
            }

            $html .= '<button type="button" class="fm-btn fm-btn-delete" data-file-id="' . $fileId . '">Delete</button>';
            $html .= '</div>';

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    protected function formatFileSize($bytes)
    {
        $bytes = (int)$bytes;

        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        return number_format($bytes / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }

    protected function isPreviewable($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $previewableExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'svg', 'webp'];
        return in_array($extension, $previewableExtensions);
    }

    protected function escapeHtml($string)
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    protected function escapeHtmlAttr($string)
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
