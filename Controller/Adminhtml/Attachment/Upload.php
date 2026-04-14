<?php
/**
 * Attachment Upload Controller
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
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Panth\ProductAttachments\Helper\File as FileHelper;

class Upload extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';

    /**
     * Attachment files base path
     */
    const ATTACHMENT_PATH = 'panth/productattachments';

    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var FileHelper
     */
    protected $fileHelper;

    /**
     * @param Context $context
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param FileHelper $fileHelper
     */
    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        FileHelper $fileHelper
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->fileHelper = $fileHelper;
    }

    /**
     * Upload file action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'attachment_file']);

            // Set allowed extensions
            $uploader->setAllowedExtensions([
                'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
                'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif', 'svg'
            ]);

            // Set upload settings
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            $uploader->setAllowCreateFolders(true);

            // Validate file extension
            if (!$uploader->checkAllowedExtension($uploader->getFileExtension())) {
                throw new LocalizedException(__('File type not allowed.'));
            }

            // Store files in var/ directory for security (not publicly accessible)
            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $path = $varDirectory->getAbsolutePath(self::ATTACHMENT_PATH);

            // Upload file
            $uploadResult = $uploader->save($path);

            if (!$uploadResult) {
                throw new LocalizedException(__('File cannot be uploaded.'));
            }

            // Sanitize filename
            $filename = $this->fileHelper->sanitizeFilename($uploadResult['name']);

            // Return success response
            return $result->setData([
                'name' => $filename,
                'file' => $uploadResult['file'],
                'size' => $uploadResult['size'],
                'url' => $this->getMediaUrl($uploadResult['file']),
                'type' => $uploadResult['type']
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ]);
        }
    }

    /**
     * Get media URL for uploaded file
     *
     * @param string $file
     * @return string
     */
    protected function getMediaUrl($file)
    {
        return $this->_url->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA])
            . self::ATTACHMENT_PATH . $file;
    }
}
