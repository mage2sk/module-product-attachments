<?php
/**
 * File Helper
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class File extends AbstractHelper
{
    /**
     * Attachment directory path
     */
    const ATTACHMENT_PATH = 'panth/attachments';

    /**
     * File icon mapping
     */
    const FILE_ICONS = [
        'pdf' => 'icon-pdf',
        'doc' => 'icon-doc',
        'docx' => 'icon-doc',
        'xls' => 'icon-xls',
        'xlsx' => 'icon-xls',
        'ppt' => 'icon-ppt',
        'pptx' => 'icon-ppt',
        'zip' => 'icon-zip',
        'rar' => 'icon-zip',
        '7z' => 'icon-zip',
        'jpg' => 'icon-img',
        'jpeg' => 'icon-img',
        'png' => 'icon-img',
        'gif' => 'icon-img',
        'bmp' => 'icon-img',
        'svg' => 'icon-img',
        'mp4' => 'icon-video',
        'avi' => 'icon-video',
        'mov' => 'icon-video',
        'wmv' => 'icon-video',
        'mp3' => 'icon-audio',
        'wav' => 'icon-audio',
        'txt' => 'icon-txt',
        'csv' => 'icon-csv',
    ];

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
    }

    /**
     * Get media directory path
     *
     * @return string
     */
    public function getMediaPath(): string
    {
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        return $mediaDirectory->getAbsolutePath(self::ATTACHMENT_PATH);
    }

    /**
     * Get media URL
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMediaUrl(): string
    {
        $store = $this->storeManager->getStore();
        return $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . self::ATTACHMENT_PATH . '/';
    }

    /**
     * Get file icon class based on extension
     *
     * @param string $filename
     * @return string
     */
    public function getFileIcon(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return self::FILE_ICONS[$extension] ?? 'icon-file';
    }

    /**
     * Get file extension
     *
     * @param string $filename
     * @return string
     */
    public function getFileExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Check if file is an image
     *
     * @param string $filename
     * @return bool
     */
    public function isImage(string $filename): bool
    {
        $extension = $this->getFileExtension($filename);
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg']);
    }

    /**
     * Check if file is a PDF
     *
     * @param string $filename
     * @return bool
     */
    public function isPdf(string $filename): bool
    {
        return $this->getFileExtension($filename) === 'pdf';
    }

    /**
     * Check if file is previewable
     *
     * @param string $filename
     * @return bool
     */
    public function isPreviewable(string $filename): bool
    {
        return $this->isImage($filename) || $this->isPdf($filename);
    }

    /**
     * Sanitize filename
     *
     * @param string $filename
     * @return string
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove any path info
        $filename = basename($filename);

        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);

        // Remove special characters except dots, dashes, and underscores
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);

        // Ensure filename is not too long (max 255 characters)
        if (strlen($filename) > 255) {
            $extension = $this->getFileExtension($filename);
            $basename = substr($filename, 0, 255 - strlen($extension) - 1);
            $filename = $basename . '.' . $extension;
        }

        return $filename;
    }

    /**
     * Generate unique filename
     *
     * @param string $filename
     * @return string
     */
    public function generateUniqueFilename(string $filename): string
    {
        $extension = $this->getFileExtension($filename);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $basename = $this->sanitizeFilename($basename);

        return $basename . '_' . uniqid() . '.' . $extension;
    }

    /**
     * Get versioned filename
     *
     * @param string $filename
     * @param string $version
     * @return string
     */
    public function getVersionedFilename(string $filename, string $version): string
    {
        $extension = $this->getFileExtension($filename);
        $basename = pathinfo($filename, PATHINFO_FILENAME);

        return $basename . '_v' . $version . '.' . $extension;
    }

    /**
     * Validate file extension
     *
     * @param string $filename
     * @param array $allowedExtensions
     * @return bool
     */
    public function isAllowedExtension(string $filename, array $allowedExtensions): bool
    {
        $extension = $this->getFileExtension($filename);
        return in_array($extension, $allowedExtensions);
    }

    /**
     * Convert bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get MIME type from extension
     *
     * @param string $filename
     * @return string
     */
    public function getMimeTypeFromExtension(string $filename): string
    {
        $extension = $this->getFileExtension($filename);

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            '7z' => 'application/x-7z-compressed',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Get file badge text (extension uppercase)
     *
     * @param string $filename
     * @return string
     */
    public function getFileBadge(string $filename): string
    {
        return strtoupper($this->getFileExtension($filename));
    }

    /**
     * Get dispersion path for file
     *
     * @param string $filename
     * @return string
     */
    public function getDispersionPath(string $filename): string
    {
        $char = 0;
        $disPath = '';

        while ($char < 2 && $char < strlen($filename)) {
            if (empty($disPath)) {
                $disPath = DIRECTORY_SEPARATOR . substr($filename, $char, 1);
            } else {
                $disPath = $this->_addDirSeparator($disPath) . substr($filename, $char, 1);
            }
            $char++;
        }

        return $disPath;
    }

    /**
     * Add directory separator
     *
     * @param string $dir
     * @return string
     */
    protected function _addDirSeparator(string $dir): string
    {
        return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}
