<?php
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
    const ATTACHMENT_PATH = 'panth/attachments';

    const ALLOWED_UPLOAD_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif'
    ];

    const INLINE_MIME_TYPES = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ];

    const BLOCKED_MIME_PATTERN = '#^(text/html|application/xhtml\+xml|image/svg\+xml|text/x-php|application/x-php'
        . '|application/x-httpd-php|text/javascript|application/javascript|text/xml|application/xml)$#i';

    const TEMP_UPLOAD_PATTERN = '#^panth/productattachments/tmp/[a-f0-9]{64}\.([a-z0-9]{1,10})$#';

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

    protected $filesystem;

    protected $storeManager;

    public function __construct(
        Context $context,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
    }

    public function getMediaPath(): string
    {
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        return $mediaDirectory->getAbsolutePath(self::ATTACHMENT_PATH);
    }

    public function getMediaUrl(): string
    {
        $store = $this->storeManager->getStore();
        return $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . self::ATTACHMENT_PATH . '/';
    }

    public function getFileIcon(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return self::FILE_ICONS[$extension] ?? 'icon-file';
    }

    public function getFileExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    public function isImage(string $filename): bool
    {
        $extension = $this->getFileExtension($filename);
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg']);
    }

    public function isPdf(string $filename): bool
    {
        return $this->getFileExtension($filename) === 'pdf';
    }

    public function isPreviewable(string $filename): bool
    {
        return $this->isImage($filename) || $this->isPdf($filename);
    }

    public function sanitizeFilename(string $filename): string
    {
        $filename = basename($filename);

        $filename = str_replace(' ', '_', $filename);

        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);

        if (strlen($filename) > 255) {
            $extension = $this->getFileExtension($filename);
            $basename = substr($filename, 0, 255 - strlen($extension) - 1);
            $filename = $basename . '.' . $extension;
        }

        return $filename;
    }

    public function generateUniqueFilename(string $filename): string
    {
        $extension = $this->getFileExtension($filename);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $basename = $this->sanitizeFilename($basename);

        return $basename . '_' . uniqid() . '.' . $extension;
    }

    public function getVersionedFilename(string $filename, string $version): string
    {
        $extension = $this->getFileExtension($filename);
        $basename = pathinfo($filename, PATHINFO_FILENAME);

        return $basename . '_v' . $version . '.' . $extension;
    }

    public function isAllowedExtension(string $filename, array $allowedExtensions): bool
    {
        $extension = $this->getFileExtension($filename);
        return in_array($extension, $allowedExtensions);
    }

    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

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

    public function getFileBadge(string $filename): string
    {
        return strtoupper($this->getFileExtension($filename));
    }

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

    protected function _addDirSeparator(string $dir): string
    {
        return rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function isAllowedUploadExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::ALLOWED_UPLOAD_EXTENSIONS, true);
    }

    public function isSafeUploadedFile(string $absolutePath, string $extension): bool
    {
        if (!$this->isAllowedUploadExtension($extension)) {
            return false;
        }

        $mimeType = is_file($absolutePath) ? (string)mime_content_type($absolutePath) : '';

        return !preg_match(self::BLOCKED_MIME_PATTERN, $mimeType);
    }

    public function getTempUploadExtension(string $tmpPath): ?string
    {
        if (!preg_match(self::TEMP_UPLOAD_PATTERN, $tmpPath, $matches)) {
            return null;
        }

        return $this->isAllowedUploadExtension($matches[1]) ? $matches[1] : null;
    }

    public function getInlineMimeType(string $filename): ?string
    {
        return self::INLINE_MIME_TYPES[$this->getFileExtension($filename)] ?? null;
    }

    public function normalizeUploadedFiles($field): array
    {
        if (!is_array($field)) {
            return [];
        }

        if (isset($field['name'])) {
            if (!is_array($field['name'])) {
                return [$field];
            }

            $files = [];
            foreach (array_keys($field['name']) as $index) {
                $files[] = [
                    'name' => $field['name'][$index] ?? '',
                    'type' => $field['type'][$index] ?? '',
                    'tmp_name' => $field['tmp_name'][$index] ?? '',
                    'error' => $field['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $field['size'][$index] ?? 0,
                ];
            }

            return $files;
        }

        $files = [];
        foreach ($field as $item) {
            foreach ($this->normalizeUploadedFiles($item) as $file) {
                $files[] = $file;
            }
        }

        return $files;
    }

    public function getMaxUploadBytes(): int
    {
        $value = (float)$this->scopeConfig->getValue(Config::XML_PATH_MAX_FILE_SIZE);

        return $value > 0 ? (int)round($value * 1024 * 1024) : 0;
    }

    public function isWithinUploadLimit(int $bytes): bool
    {
        $max = $this->getMaxUploadBytes();

        return $max === 0 || $bytes <= $max;
    }

    public function isFileWithinUploadLimit(string $absolutePath): bool
    {
        if (!is_file($absolutePath)) {
            return false;
        }

        return $this->isWithinUploadLimit((int)filesize($absolutePath));
    }

    public function getUploadLimitError(string $fileName): string
    {
        return (string)__(
            'The file "%1" exceeds the maximum upload size of %2.',
            $this->getSafeHeaderFilename($fileName, 'file'),
            $this->formatBytes($this->getMaxUploadBytes())
        );
    }

    public function getSafeHeaderFilename(string $filename, string $fallback = 'download'): string
    {
        $name = basename(str_replace('\\', '/', $filename));
        $name = trim((string)preg_replace('/[\x00-\x1F\x7F"\\\\;]/', '', $name));

        return $name !== '' ? $name : $fallback;
    }
}
