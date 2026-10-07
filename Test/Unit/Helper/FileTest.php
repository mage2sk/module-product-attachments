<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Helper\Config;
use Panth\ProductAttachments\Helper\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    private $maxFileSize = null;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/panth_pa_file_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->tmpDir);
    }

    private function createHelper(?Filesystem $filesystem = null, ?StoreManagerInterface $storeManager = null): File
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            fn ($path) => $path === Config::XML_PATH_MAX_FILE_SIZE ? $this->maxFileSize : null
        );
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        return new File(
            $context,
            $filesystem ?? $this->createStub(Filesystem::class),
            $storeManager ?? $this->createStub(StoreManagerInterface::class)
        );
    }

    private function writeFile(string $name, string $content): string
    {
        $path = $this->tmpDir . '/' . $name;
        file_put_contents($path, $content);
        return $path;
    }

    public function testGetMediaPathUsesMediaDirectory(): void
    {
        $directory = $this->createMock(ReadInterface::class);
        $directory->expects($this->once())
            ->method('getAbsolutePath')
            ->with(File::ATTACHMENT_PATH)
            ->willReturn('/var/www/pub/media/panth/attachments');
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())
            ->method('getDirectoryRead')
            ->with(DirectoryList::MEDIA)
            ->willReturn($directory);

        $this->assertSame('/var/www/pub/media/panth/attachments', $this->createHelper($filesystem)->getMediaPath());
    }

    public function testGetMediaUrlAppendsAttachmentPath(): void
    {
        $store = $this->createMock(Store::class);
        $store->expects($this->once())
            ->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA)
            ->willReturn('https://shop.test/media/');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $this->assertSame(
            'https://shop.test/media/panth/attachments/',
            $this->createHelper(null, $storeManager)->getMediaUrl()
        );
    }

    public static function iconProvider(): array
    {
        return [
            ['manual.PDF', 'icon-pdf'],
            ['sheet.xlsx', 'icon-xls'],
            ['archive.7z', 'icon-zip'],
            ['clip.mov', 'icon-video'],
            ['song.wav', 'icon-audio'],
            ['noext', 'icon-file'],
            ['weird.xyz', 'icon-file'],
        ];
    }

    #[DataProvider('iconProvider')]
    public function testGetFileIcon(string $filename, string $expected): void
    {
        $this->assertSame($expected, $this->createHelper()->getFileIcon($filename));
    }

    public function testTypeDetection(): void
    {
        $helper = $this->createHelper();
        $this->assertSame('jpeg', $helper->getFileExtension('Photo.JPEG'));
        $this->assertSame('', $helper->getFileExtension('README'));
        $this->assertTrue($helper->isImage('a.svg'));
        $this->assertFalse($helper->isImage('a.pdf'));
        $this->assertTrue($helper->isPdf('doc.Pdf'));
        $this->assertFalse($helper->isPdf('doc.docx'));
        $this->assertTrue($helper->isPreviewable('a.png'));
        $this->assertTrue($helper->isPreviewable('a.pdf'));
        $this->assertFalse($helper->isPreviewable('a.zip'));
        $this->assertSame('PDF', $helper->getFileBadge('manual.pdf'));
    }

    public function testSanitizeFilenameStripsPathAndUnsafeCharacters(): void
    {
        $helper = $this->createHelper();
        $this->assertSame('my_file.pdf', $helper->sanitizeFilename('../../etc/my file.pdf'));
        $this->assertSame('rsum2024.doc', $helper->sanitizeFilename("r\xC3\xA9sum<2024>.doc"));
    }

    public function testSanitizeFilenameTruncatesLongNamesKeepingExtension(): void
    {
        $result = $this->createHelper()->sanitizeFilename(str_repeat('a', 300) . '.pdf');
        $this->assertSame(255, strlen($result));
        $this->assertStringEndsWith('.pdf', $result);
    }

    public function testGenerateUniqueFilename(): void
    {
        $helper = $this->createHelper();
        $first = $helper->generateUniqueFilename('My Report.PDF');
        $this->assertMatchesRegularExpression('/^My_Report_[a-f0-9]{13}\.pdf$/', $first);
        $this->assertNotSame($first, $helper->generateUniqueFilename('My Report.PDF'));
    }

    public function testGetVersionedFilename(): void
    {
        $this->assertSame('guide_v2.1.pdf', $this->createHelper()->getVersionedFilename('guide.pdf', '2.1'));
    }

    public function testIsAllowedExtension(): void
    {
        $helper = $this->createHelper();
        $this->assertTrue($helper->isAllowedExtension('A.PDF', ['pdf']));
        $this->assertFalse($helper->isAllowedExtension('a.exe', ['pdf', 'doc']));
    }

    public static function bytesProvider(): array
    {
        return [
            [0, '0 B'],
            [1000, '1000 B'],
            [1024, '1024 B'],
            [2048, '2 KB'],
            [1572864, '1.5 MB'],
            [3221225472, '3 GB'],
        ];
    }

    #[DataProvider('bytesProvider')]
    public function testFormatBytes(int $bytes, string $expected): void
    {
        $this->assertSame($expected, $this->createHelper()->formatBytes($bytes));
    }

    public static function mimeProvider(): array
    {
        return [
            ['a.pdf', 'application/pdf'],
            ['a.DOCX', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            ['a.svg', 'image/svg+xml'],
            ['a.csv', 'text/csv'],
            ['a.exe', 'application/octet-stream'],
        ];
    }

    #[DataProvider('mimeProvider')]
    public function testGetMimeTypeFromExtension(string $file, string $expected): void
    {
        $this->assertSame($expected, $this->createHelper()->getMimeTypeFromExtension($file));
    }

    public function testGetDispersionPath(): void
    {
        $helper = $this->createHelper();
        $sep = DIRECTORY_SEPARATOR;
        $this->assertSame($sep . 'a' . $sep . 'b', $helper->getDispersionPath('abc.pdf'));
        $this->assertSame($sep . 'x', $helper->getDispersionPath('x'));
        $this->assertSame('', $helper->getDispersionPath(''));
    }

    public function testIsAllowedUploadExtensionIsCaseInsensitive(): void
    {
        $helper = $this->createHelper();
        $this->assertTrue($helper->isAllowedUploadExtension('PDF'));
        $this->assertTrue($helper->isAllowedUploadExtension('jpeg'));
        $this->assertFalse($helper->isAllowedUploadExtension('php'));
        $this->assertFalse($helper->isAllowedUploadExtension('svg'));
    }

    public function testIsSafeUploadedFileRejectsDisallowedExtension(): void
    {
        $path = $this->writeFile('plain.txt', 'hello');
        $this->assertFalse($this->createHelper()->isSafeUploadedFile($path, 'php'));
    }

    public function testIsSafeUploadedFileAcceptsPlainTextContent(): void
    {
        $path = $this->writeFile('plain.txt', 'just some text');
        $this->assertTrue($this->createHelper()->isSafeUploadedFile($path, 'txt'));
    }

    public function testIsSafeUploadedFileRejectsHtmlDisguisedAsPdf(): void
    {
        $path = $this->writeFile('evil.pdf', '<!DOCTYPE html><html><body><script>alert(1)</script></body></html>');
        $this->assertFalse($this->createHelper()->isSafeUploadedFile($path, 'pdf'));
    }

    public static function mismatchedContentProvider(): array
    {
        return [
            'text renamed to pdf' => ['notes.pdf', 'just some text', 'pdf'],
            'text renamed to png' => ['image.png', 'not an image at all', 'png'],
            'pdf renamed to jpg' => ['photo.jpg', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF", 'jpg'],
            'shell script as txt' => ['run.txt', "#!/bin/sh\necho hi\n", 'txt'],
            'empty file' => ['empty.txt', '', 'txt'],
        ];
    }

    #[DataProvider('mismatchedContentProvider')]
    public function testIsSafeUploadedFileRejectsContentThatDoesNotMatchExtension(
        string $name,
        string $content,
        string $extension
    ): void {
        $path = $this->writeFile($name, $content);
        $this->assertFalse($this->createHelper()->isSafeUploadedFile($path, $extension));
    }

    public function testIsSafeUploadedFileAcceptsRealPdfAndPngContent(): void
    {
        $pdf = $this->writeFile('real.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");
        $png = $this->writeFile('real.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
        $helper = $this->createHelper();
        $this->assertTrue($helper->isSafeUploadedFile($pdf, 'PDF'));
        $this->assertTrue($helper->isSafeUploadedFile($png, 'png'));
    }

    public static function linkUrlProvider(): array
    {
        return [
            ['https://example.com/manual.pdf', true],
            ['http://example.com', true],
            ['  https://example.com/a?b=1  ', true],
            ['javascript:alert(1)', false],
            ['JAVASCRIPT:alert(1)', false],
            ['data:text/html,hi', false],
            ['//example.com', false],
            ['mailto:a@example.com', false],
            ['https://', false],
            ['https://exa mple.com', false],
            ['https://example.com/<script>', false],
            ['', false],
        ];
    }

    #[DataProvider('linkUrlProvider')]
    public function testIsValidLinkUrl(string $url, bool $expected): void
    {
        $this->assertSame($expected, $this->createHelper()->isValidLinkUrl($url));
    }

    public function testNormalizeLinkTarget(): void
    {
        $helper = $this->createHelper();
        $this->assertSame('_self', $helper->normalizeLinkTarget('_self'));
        $this->assertSame('_blank', $helper->normalizeLinkTarget('_blank'));
        $this->assertSame('_blank', $helper->normalizeLinkTarget('_top'));
        $this->assertSame('_blank', $helper->normalizeLinkTarget(null));
    }

    public function testIsSafeUploadedFileWithMissingFileOnlyChecksExtension(): void
    {
        $this->assertTrue($this->createHelper()->isSafeUploadedFile($this->tmpDir . '/missing.pdf', 'pdf'));
    }

    public static function tempPathProvider(): array
    {
        $hash = str_repeat('a1', 32);
        return [
            'valid pdf' => ['panth/productattachments/tmp/' . $hash . '.pdf', 'pdf'],
            'blocked extension' => ['panth/productattachments/tmp/' . $hash . '.php', null],
            'traversal' => ['panth/productattachments/tmp/../' . $hash . '.pdf', null],
            'short hash' => ['panth/productattachments/tmp/abc.pdf', null],
            'uppercase hash' => ['panth/productattachments/tmp/' . strtoupper($hash) . '.pdf', null],
            'other folder' => ['panth/productattachments/secure/' . $hash . '.pdf', null],
            'empty' => ['', null],
        ];
    }

    #[DataProvider('tempPathProvider')]
    public function testGetTempUploadExtension(string $path, ?string $expected): void
    {
        $this->assertSame($expected, $this->createHelper()->getTempUploadExtension($path));
    }

    public function testGetInlineMimeType(): void
    {
        $helper = $this->createHelper();
        $this->assertSame('application/pdf', $helper->getInlineMimeType('/x/y/doc.PDF'));
        $this->assertSame('image/png', $helper->getInlineMimeType('img.png'));
        $this->assertNull($helper->getInlineMimeType('page.html'));
        $this->assertNull($helper->getInlineMimeType('vector.svg'));
    }

    public function testNormalizeUploadedFilesHandlesScalarAndEmptyInput(): void
    {
        $helper = $this->createHelper();
        $this->assertSame([], $helper->normalizeUploadedFiles(null));
        $this->assertSame([], $helper->normalizeUploadedFiles('string'));
        $single = ['name' => 'a.pdf', 'type' => 'application/pdf', 'tmp_name' => '/tmp/x', 'error' => 0, 'size' => 4];
        $this->assertSame([$single], $helper->normalizeUploadedFiles($single));
    }

    public function testNormalizeUploadedFilesFlattensMultiUploadStructure(): void
    {
        $field = [
            'name' => ['a.pdf', 'b.txt'],
            'type' => ['application/pdf'],
            'tmp_name' => ['/tmp/a', '/tmp/b'],
            'error' => [0],
            'size' => [10, 20],
        ];
        $result = $this->createHelper()->normalizeUploadedFiles($field);
        $this->assertCount(2, $result);
        $this->assertSame(
            ['name' => 'b.txt', 'type' => '', 'tmp_name' => '/tmp/b', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 20],
            $result[1]
        );
        $this->assertSame('application/pdf', $result[0]['type']);
    }

    public function testNormalizeUploadedFilesRecursesIntoListOfFiles(): void
    {
        $field = [
            ['name' => 'a.pdf', 'tmp_name' => '/tmp/a', 'error' => 0],
            ['name' => 'b.pdf', 'tmp_name' => '/tmp/b', 'error' => 0],
        ];
        $result = $this->createHelper()->normalizeUploadedFiles($field);
        $this->assertSame(['a.pdf', 'b.pdf'], array_column($result, 'name'));
    }

    public function testUploadLimitDisabledWhenNotConfigured(): void
    {
        $helper = $this->createHelper();
        $this->assertSame(0, $helper->getMaxUploadBytes());
        $this->assertTrue($helper->isWithinUploadLimit(PHP_INT_MAX));
    }

    public function testUploadLimitInMegabytes(): void
    {
        $this->maxFileSize = '1.5';
        $helper = $this->createHelper();
        $this->assertSame(1572864, $helper->getMaxUploadBytes());
        $this->assertTrue($helper->isWithinUploadLimit(1572864));
        $this->assertFalse($helper->isWithinUploadLimit(1572865));
    }

    public function testNegativeLimitIsTreatedAsUnlimited(): void
    {
        $this->maxFileSize = '-3';
        $this->assertSame(0, $this->createHelper()->getMaxUploadBytes());
    }

    public function testIsFileWithinUploadLimit(): void
    {
        $this->maxFileSize = '0.00001';
        $helper = $this->createHelper();
        $small = $this->writeFile('small.txt', 'abc');
        $big = $this->writeFile('big.txt', str_repeat('x', 100));
        $this->assertTrue($helper->isFileWithinUploadLimit($small));
        $this->assertFalse($helper->isFileWithinUploadLimit($big));
        $this->assertFalse($helper->isFileWithinUploadLimit($this->tmpDir . '/nope.txt'));
    }

    public function testGetUploadLimitErrorIncludesSafeNameAndLimit(): void
    {
        $this->maxFileSize = '2';
        $message = $this->createHelper()->getUploadLimitError('../evil";name.pdf');
        $this->assertSame('The file "evilname.pdf" exceeds the maximum upload size of 2 MB.', $message);
    }

    public static function headerNameProvider(): array
    {
        return [
            ['report.pdf', 'download', 'report.pdf'],
            ['C:\\Users\\me\\report.pdf', 'download', 'report.pdf'],
            ["bad\r\nname;\"x\".pdf", 'download', 'badnamex.pdf'],
            ['', 'download', 'download'],
            ['"";;', 'file', 'file'],
            ['dir/', 'download', 'dir'],
        ];
    }

    #[DataProvider('headerNameProvider')]
    public function testGetSafeHeaderFilename(string $input, string $fallback, string $expected): void
    {
        $this->assertSame($expected, $this->createHelper()->getSafeHeaderFilename($input, $fallback));
    }
}
