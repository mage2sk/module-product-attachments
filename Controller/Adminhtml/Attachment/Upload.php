<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Panth\ProductAttachments\Helper\File as FileHelper;

class Upload extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::attachment_save';

    const ATTACHMENT_PATH = 'panth/productattachments';

    protected $uploaderFactory;

    protected $filesystem;

    protected $fileHelper;

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

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'attachment_file']);

            $uploader->setAllowedExtensions(FileHelper::ALLOWED_UPLOAD_EXTENSIONS);

            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            $uploader->setAllowCreateFolders(true);

            if (!$uploader->checkAllowedExtension($uploader->getFileExtension())) {
                throw new LocalizedException(__('File type not allowed.'));
            }

            $requestFile = $this->getRequest()->getFiles('attachment_file');
            if (!$this->fileHelper->isFileWithinUploadLimit((string)($requestFile['tmp_name'] ?? ''))) {
                throw new LocalizedException(
                    __($this->fileHelper->getUploadLimitError((string)($requestFile['name'] ?? '')))
                );
            }

            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $path = $varDirectory->getAbsolutePath(self::ATTACHMENT_PATH);

            $uploadResult = $uploader->save($path);

            if (!$uploadResult) {
                throw new LocalizedException(__('File cannot be uploaded.'));
            }

            $this->assertSafeSavedFile($uploadResult, $path);

            $filename = $this->fileHelper->sanitizeFilename($uploadResult['name']);

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

    private function assertSafeSavedFile(array $uploadResult, string $path): void
    {
        $savedPath = rtrim((string)($uploadResult['path'] ?? $path), '/') . '/'
            . ltrim((string)($uploadResult['file'] ?? ''), '/');
        $extension = strtolower(pathinfo($savedPath, PATHINFO_EXTENSION));
        if (!$this->fileHelper->isSafeUploadedFile($savedPath, $extension)) {
            $varDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $varDirectory->delete($varDirectory->getRelativePath($savedPath));
            throw new LocalizedException(__('The file content does not match its file type.'));
        }
    }

    protected function getMediaUrl($file)
    {
        return $this->_url->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA])
            . self::ATTACHMENT_PATH . $file;
    }
}
