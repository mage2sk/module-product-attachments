<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller\Adminhtml;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdminResourceAclTest extends TestCase
{
    private static function aclResources(): array
    {
        $xml = simplexml_load_file(dirname(__DIR__, 4) . '/etc/acl.xml');
        $ids = [];
        foreach ($xml->xpath('//resource') as $node) {
            $ids[] = (string)$node['id'];
        }
        return $ids;
    }

    public static function controllerProvider(): array
    {
        $base = 'Panth\\ProductAttachments\\Controller\\Adminhtml\\';
        $map = [
            'Analytics\\Index' => 'Panth_ProductAttachments::analytics',
            'Attachment\\Delete' => 'Panth_ProductAttachments::attachment_delete',
            'Attachment\\DeleteFile' => 'Panth_ProductAttachments::attachment_save',
            'Attachment\\DownloadFile' => 'Panth_ProductAttachments::attachment',
            'Attachment\\DownloadVersion' => 'Panth_ProductAttachments::attachment',
            'Attachment\\Edit' => 'Panth_ProductAttachments::attachment_save',
            'Attachment\\FileList' => 'Panth_ProductAttachments::attachment',
            'Attachment\\FileManager' => 'Panth_ProductAttachments::attachment',
            'Attachment\\Index' => 'Panth_ProductAttachments::attachment',
            'Attachment\\MassDelete' => 'Panth_ProductAttachments::attachment_delete',
            'Attachment\\MassStatus' => 'Panth_ProductAttachments::attachment_save',
            'Attachment\\MoveTempFiles' => 'Panth_ProductAttachments::attachment_save',
            'Attachment\\NewAction' => 'Panth_ProductAttachments::attachment_save',
            'Attachment\\PageGrid' => 'Panth_ProductAttachments::attachment_save',
            'Attachment\\PreviewFile' => 'Panth_ProductAttachments::attachment',
            'Attachment\\ProductGrid' => 'Panth_ProductAttachments::attachment_save',
            'Attachment\\Save' => 'Panth_ProductAttachments::attachment_save',
            'Attachment\\SetPrimaryFile' => 'Panth_ProductAttachments::attachment_save',
            'Attachment\\TempUpload' => 'Panth_ProductAttachments::attachment_save',
            'Attachment\\Upload' => 'Panth_ProductAttachments::attachment_save',
            'Attachment\\UploadFiles' => 'Panth_ProductAttachments::attachment_save',
            'Attachment\\Versions' => 'Panth_ProductAttachments::attachment',
            'Category\\SaveAttachment' => 'Panth_ProductAttachments::attachment_save',
            'Page\\SaveAttachment' => 'Panth_ProductAttachments::attachment_save',
            'Product\\SaveAttachment' => 'Panth_ProductAttachments::attachment_save',
            'Type\\Delete' => 'Panth_ProductAttachments::type_delete',
            'Type\\Edit' => 'Panth_ProductAttachments::type_save',
            'Type\\Index' => 'Panth_ProductAttachments::type',
            'Type\\InlineEdit' => 'Panth_ProductAttachments::type_save',
            'Type\\MassDelete' => 'Panth_ProductAttachments::type_delete',
            'Type\\NewAction' => 'Panth_ProductAttachments::type_save',
            'Type\\Save' => 'Panth_ProductAttachments::type_save',
            'UnusedFiles\\DeleteAll' => 'Panth_ProductAttachments::unusedfiles',
            'UnusedFiles\\Index' => 'Panth_ProductAttachments::unusedfiles',
            'UnusedFiles\\MassDelete' => 'Panth_ProductAttachments::unusedfiles',
        ];
        $cases = [];
        foreach ($map as $class => $resource) {
            $cases[$class] = [$base . $class, $resource];
        }
        return $cases;
    }

    #[DataProvider('controllerProvider')]
    public function testAdminResourceIsExpectedAndDeclaredInAcl(string $class, string $resource): void
    {
        $this->assertSame($resource, constant($class . '::ADMIN_RESOURCE'));
        $this->assertContains($resource, self::aclResources());
    }

    public function testDestructiveActionsOnlyAcceptPost(): void
    {
        $base = 'Panth\\ProductAttachments\\Controller\\Adminhtml\\';
        $postOnly = [
            'Attachment\\Delete', 'Attachment\\DeleteFile', 'Attachment\\MassDelete', 'Attachment\\MassStatus',
            'Attachment\\MoveTempFiles', 'Attachment\\Save', 'Attachment\\SetPrimaryFile', 'Attachment\\TempUpload',
            'Attachment\\Upload', 'Attachment\\UploadFiles', 'Category\\SaveAttachment', 'Page\\SaveAttachment',
            'Product\\SaveAttachment', 'Type\\Delete', 'Type\\InlineEdit', 'Type\\MassDelete', 'Type\\Save',
            'UnusedFiles\\DeleteAll', 'UnusedFiles\\MassDelete',
        ];
        foreach ($postOnly as $class) {
            $this->assertTrue(
                is_subclass_of($base . $class, \Magento\Framework\App\Action\HttpPostActionInterface::class),
                $class . ' must be POST only'
            );
        }
    }
}
