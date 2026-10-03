<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Console\Command;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\PageFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Api\AttachmentRepositoryInterface;
use Panth\ProductAttachments\Api\AttachmentTypeRepositoryInterface;
use Panth\ProductAttachments\Model\AttachmentFactory;
use Panth\ProductAttachments\Model\AttachmentFileFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType\CollectionFactory as TypeCollectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallSampleData extends Command
{
    private $attachmentFactory;

    private $attachmentFileFactory;

    private $attachmentRepository;

    private $typeRepository;

    private $typeCollectionFactory;

    private $productFactory;

    private $productRepository;

    private $pageFactory;

    private $pageRepository;

    private $filesystem;

    private $fileDriver;

    private $storeManager;

    private $state;

    private $groupRepository;

    private $searchCriteriaBuilder;

    private const SAMPLE_FILES = [
        ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'User Manual', 'type_code' => 'user_manual'],
        ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'Installation Guide', 'type_code' => 'installation_guide'],
        ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'Quick Start Guide', 'type_code' => 'quick_start_guide'],
        ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'Technical Specifications', 'type_code' => 'technical_specs'],
        ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'Datasheet', 'type_code' => 'datasheet'],
        ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'Product Brochure', 'type_code' => 'brochure'],
        ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'Certificate of Authenticity', 'type_code' => 'certificate'],
        ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'Warranty Information', 'type_code' => 'warranty'],
        ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'Safety Instructions', 'type_code' => 'safety_instructions'],
        ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'Troubleshooting Guide', 'type_code' => 'troubleshooting'],
        ['ext' => 'jpg', 'mime' => 'image/jpeg', 'name' => 'Product Image 1', 'type_code' => 'product_images'],
        ['ext' => 'jpg', 'mime' => 'image/jpeg', 'name' => 'Product Image 2', 'type_code' => 'product_images'],
        ['ext' => 'png', 'mime' => 'image/png', 'name' => 'Product Diagram', 'type_code' => 'product_images'],
        ['ext' => 'dwg', 'mime' => 'application/acad', 'name' => 'CAD Drawing', 'type_code' => 'cad_drawings'],
        ['ext' => 'mp4', 'mime' => 'video/mp4', 'name' => 'Installation Video', 'type_code' => 'video_tutorial'],
        ['ext' => 'mp4', 'mime' => 'video/mp4', 'name' => 'Product Demo Video', 'type_code' => 'video_tutorial'],
        ['ext' => 'zip', 'mime' => 'application/zip', 'name' => 'Firmware Update', 'type_code' => 'firmware_software'],
        ['ext' => 'zip', 'mime' => 'application/zip', 'name' => 'Driver Package', 'type_code' => 'firmware_software'],
        ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'Compliance Documents', 'type_code' => 'compliance'],
        ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'FAQ Document', 'type_code' => 'faq_document'],
        ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'Case Study - Manufacturing', 'type_code' => 'case_study'],
        ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'White Paper - Technology', 'type_code' => 'white_paper'],
        ['ext' => 'pdf', 'mime' => 'application/pdf', 'name' => 'Maintenance Guide', 'type_code' => 'maintenance_guide'],
        ['ext' => 'xlsx', 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'name' => 'Parts List', 'type_code' => 'parts_list'],
        ['ext' => 'docx', 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'name' => 'Additional Documentation', 'type_code' => 'other'],
        ['ext' => 'txt', 'mime' => 'text/plain', 'name' => 'Release Notes', 'type_code' => 'other'],
        ['ext' => 'csv', 'mime' => 'text/csv', 'name' => 'Specifications Table', 'type_code' => 'technical_specs'],
        ['ext' => 'svg', 'mime' => 'image/svg+xml', 'name' => 'Vector Logo', 'type_code' => 'product_images'],
        ['ext' => 'json', 'mime' => 'application/json', 'name' => 'API Configuration', 'type_code' => 'firmware_software'],
        ['ext' => 'xml', 'mime' => 'application/xml', 'name' => 'Data Export', 'type_code' => 'other'],
    ];

    public function __construct(
        AttachmentFactory $attachmentFactory,
        AttachmentFileFactory $attachmentFileFactory,
        AttachmentRepositoryInterface $attachmentRepository,
        AttachmentTypeRepositoryInterface $typeRepository,
        TypeCollectionFactory $typeCollectionFactory,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        PageFactory $pageFactory,
        PageRepositoryInterface $pageRepository,
        Filesystem $filesystem,
        File $fileDriver,
        StoreManagerInterface $storeManager,
        State $state,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->attachmentFactory = $attachmentFactory;
        $this->attachmentFileFactory = $attachmentFileFactory;
        $this->attachmentRepository = $attachmentRepository;
        $this->typeRepository = $typeRepository;
        $this->typeCollectionFactory = $typeCollectionFactory;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->pageFactory = $pageFactory;
        $this->pageRepository = $pageRepository;
        $this->filesystem = $filesystem;
        $this->fileDriver = $fileDriver;
        $this->storeManager = $storeManager;
        $this->state = $state;
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('panth:attachments:install-sample-data')
            ->setDescription('Install sample data for Product Attachments module - creates sample files, product, and CMS page');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);

            $output->writeln('<info>Starting Product Attachments Sample Data Installation...</info>');
            $output->writeln('<info>Note: If sample data already exists, it will be replaced.</info>');
            $output->writeln('');

            $output->writeln('<comment>Step 1: Creating sample files directory...</comment>');
            $sampleDir = $this->createSampleDirectory();
            $output->writeln("<info>[SUCCESS] Sample directory created: {$sampleDir}</info>");

            $output->writeln('<comment>Step 2: Generating 30 sample files...</comment>');
            $generatedFiles = $this->generateSampleFiles($sampleDir);
            $output->writeln("<info>[SUCCESS] Generated " . count($generatedFiles) . " sample files</info>");

            $output->writeln('<comment>Step 3: Loading attachment types...</comment>');
            $typesMap = $this->getAttachmentTypesMap();
            $output->writeln("<info>[SUCCESS] Loaded " . count($typesMap) . " attachment types</info>");

            $output->writeln('<comment>Step 4: Creating sample product...</comment>');
            $product = $this->createSampleProduct();
            $output->writeln("<info>[SUCCESS] Created product: {$product->getName()} (ID: {$product->getId()}, SKU: {$product->getSku()})</info>");

            $output->writeln('<comment>Step 5: Cleaning up old sample data...</comment>');
            $this->deleteExistingAttachments((int)$product->getId());
            $this->cleanupOrphanedFileRecords();
            $output->writeln('<info>[SUCCESS] Cleanup complete</info>');

            $output->writeln('<comment>Step 6: Creating attachments for product (all store views)...</comment>');
            $attachmentIds = $this->createAttachments($generatedFiles, (int)$product->getId(), 'product', $typesMap);
            $output->writeln("<info>[SUCCESS] Created " . count($attachmentIds) . " attachments for all store views</info>");

            $output->writeln('<comment>Step 7: Creating CMS page with widgets...</comment>');
            $page = $this->createCmsPage((int)$product->getId());
            $output->writeln("<info>[SUCCESS] Created CMS page: {$page->getTitle()} (ID: {$page->getId()}, URL: {$page->getIdentifier()})</info>");

            $output->writeln('');
            $output->writeln('<info>════════════════════════════════════════════════════════════════</info>');
            $output->writeln('<info>[SUCCESS] Sample Data Installation Complete!</info>');
            $output->writeln('<info>════════════════════════════════════════════════════════════════</info>');
            $output->writeln('');
            $output->writeln('<comment>Summary:</comment>');

            $varDir = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath();
            $filesLocation = $varDir . 'panth/attachments/samples/';

            $output->writeln(" • Sample files location: {$filesLocation}");
            $output->writeln(" • Product: {$product->getName()} (SKU: {$product->getSku()})");
            $output->writeln(" • Attachments created: " . count($attachmentIds));
            $output->writeln(" • CMS Page: {$page->getIdentifier()}");
            $output->writeln('');
            $output->writeln('<comment>Next Steps:</comment>');

            $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);

            $productUrl = $baseUrl . $product->getUrlKey() . '.html';
            if ($product->getUrlPath()) {
                $productUrl = $baseUrl . $product->getUrlPath();
            }

            $cmsUrl = $baseUrl . $page->getIdentifier();

            $output->writeln(" 1. View product: {$productUrl}");
            $output->writeln(" 2. View CMS page: {$cmsUrl}");
            $output->writeln(" 3. Flush cache: php bin/magento cache:flush");
            $output->writeln('');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            $output->writeln('<error>Trace: ' . $e->getTraceAsString() . '</error>');
            return Command::FAILURE;
        }
    }

    private function cleanupOrphanedFileRecords(): void
    {
        try {
            $attachment = $this->attachmentFactory->create();
            $connection = $attachment->getResource()->getConnection();

            $connection->delete(
                $connection->getTableName('panth_product_attachment_file'),
                "file_path LIKE 'panth/attachments/samples/%'"
            );
        } catch (\Exception $e) {
        }
    }

    private function createSampleDirectory(): string
    {
        $varDir = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath();
        $sampleDir = $varDir . 'panth/attachments/samples/';

        if (!$this->fileDriver->isDirectory($sampleDir)) {
            $this->fileDriver->createDirectory($sampleDir, 0775);
        }

        return $sampleDir;
    }

    private function generateSampleFiles(string $sampleDir): array
    {
        $generatedFiles = [];

        $baseTimestamp = time();

        foreach (self::SAMPLE_FILES as $index => $fileData) {
            $uniqueId = $baseTimestamp . '_' . str_pad((string)($index + 1), 3, '0', STR_PAD_LEFT);
            $filename = $this->sanitizeFilename($fileData['name']) . '_' . $uniqueId . '.' . $fileData['ext'];
            $filePath = $sampleDir . $filename;

            $content = $this->generateFileContent($fileData['ext'], $fileData['name']);

            $this->fileDriver->filePutContents($filePath, $content);

            $generatedFiles[] = [
                'path' => $filePath,
                'filename' => $filename,
                'original_name' => $fileData['name'] . '.' . $fileData['ext'],
                'title' => $fileData['name'],
                'relative_path' => 'panth/attachments/samples/' . $filename,
                'mime_type' => $fileData['mime'],
                'size' => $this->fileDriver->stat($filePath)['size'],
                'type_code' => $fileData['type_code']
            ];
        }

        return $generatedFiles;
    }

    private function generateFileContent(string $ext, string $name): string
    {
        switch (strtolower($ext)) {
            case 'pdf':
                return $this->generatePdfContent($name);
            case 'txt':
                return "Sample text file: {$name}\n\nThis is a sample document generated for demonstration purposes.\n\nContent:\n- Line 1\n- Line 2\n- Line 3\n";
            case 'csv':
                return "Column1,Column2,Column3\nValue1,Value2,Value3\nData1,Data2,Data3\n";
            case 'json':
                return json_encode(['name' => $name, 'type' => 'sample', 'data' => ['key1' => 'value1', 'key2' => 'value2']], JSON_PRETTY_PRINT);
            case 'xml':
                return "<?xml version=\"1.0\"?>\n<root>\n  <name>{$name}</name>\n  <type>sample</type>\n  <data>Sample XML content</data>\n</root>\n";
            case 'svg':
                return '<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="200" fill="#3498db"/><text x="50%" y="50%" text-anchor="middle" fill="white" font-size="16">' . $name . '</text></svg>';
            case 'jpg':
            case 'png':

                return $this->generateSampleImage($ext);
            default:
                return "Sample file: {$name}\n\nThis is a placeholder file for demonstration purposes.";
        }
    }

    private function generatePdfContent(string $title): string
    {
        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> /MediaBox [0 0 612 792] /Contents 4 0 R >>\nendobj\n";
        $pdf .= "4 0 obj\n<< /Length 55 >>\nstream\nBT /F1 24 Tf 100 700 Td ({$title}) Tj ET\nendstream\nendobj\n";
        $pdf .= "xref\n0 5\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\n0000000314 00000 n\n";
        $pdf .= "trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n419\n%%EOF\n";

        return $pdf;
    }

    private function generateSampleImage(string $ext): string
    {
        $img = imagecreatetruecolor(100, 100);
        $blue = imagecolorallocate($img, 52, 152, 219);
        imagefill($img, 0, 0, $blue);

        ob_start();
        if ($ext === 'jpg') {
            imagejpeg($img, null, 90);
        } else {
            imagepng($img);
        }
        $imageData = ob_get_clean();
        imagedestroy($img);

        return $imageData;
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $filename);
        $filename = preg_replace('/\s+/', '_', $filename);
        return strtolower($filename);
    }

    private function getAttachmentTypesMap(): array
    {
        $typesMap = [];
        $collection = $this->typeCollectionFactory->create();

        foreach ($collection as $type) {
            $typesMap[$type->getCode()] = $type->getTypeId();
        }

        return $typesMap;
    }

    private function createSampleProduct()
    {
        $sku = 'sample-product-with-attachments';

        try {
            $existingProduct = $this->productRepository->get($sku);
            if ($existingProduct->getId()) {
                $this->productRepository->delete($existingProduct);
            }
        } catch (\Exception $e) {
        }

        $product = $this->productFactory->create();
        $product->setSku($sku);
        $product->setName('Sample Product with Attachments');
        $product->setAttributeSetId(4);
        $product->setStatus(1);
        $product->setVisibility(4);
        $product->setTypeId('simple');
        $product->setPrice(99.99);
        $product->setWeight(1.0);
        $product->setStockData([
            'use_config_manage_stock' => 0,
            'manage_stock' => 1,
            'is_in_stock' => 1,
            'qty' => 100
        ]);
        $product->setWebsiteIds([1]);
        $product->setDescription('This is a sample product created to demonstrate the Product Attachments module. It includes various types of attachments like manuals, videos, images, and documents.');
        $product->setShortDescription('Sample product with multiple attachment types for demonstration');
        $product->setUrlKey('sample-product-attachments-demo');

        return $this->productRepository->save($product);
    }

    private function deleteExistingAttachments(int $productId): void
    {
        try {
            $attachment = $this->attachmentFactory->create();
            $connection = $attachment->getResource()->getConnection();

            $select = $connection->select()
                ->from($connection->getTableName('panth_product_attachment_product'), 'attachment_id')
                ->where('product_id = ?', $productId);
            $attachmentIds = $connection->fetchCol($select);

            foreach ($attachmentIds as $attachmentId) {
                $connection->delete(
                    $connection->getTableName('panth_product_attachment_file'),
                    ['attachment_id = ?' => $attachmentId]
                );

                $connection->delete(
                    $connection->getTableName('panth_product_attachment_product'),
                    ['attachment_id = ?' => $attachmentId]
                );

                $connection->delete(
                    $connection->getTableName('panth_product_attachment_store'),
                    ['attachment_id = ?' => $attachmentId]
                );

                $connection->delete(
                    $connection->getTableName('panth_product_attachment_category'),
                    ['attachment_id = ?' => $attachmentId]
                );

                $connection->delete(
                    $connection->getTableName('panth_product_attachment_page'),
                    ['attachment_id = ?' => $attachmentId]
                );

                $connection->delete(
                    $connection->getTableName('panth_product_attachment'),
                    ['attachment_id = ?' => $attachmentId]
                );
            }
        } catch (\Exception $e) {
        }
    }

    private function createAttachments(array $files, int $entityId, string $entityType, array $typesMap): array
    {
        $attachmentIds = [];
        $sortOrder = 10;
        $storeId = 0;

        foreach ($files as $fileData) {
            try {
                $attachment = $this->attachmentFactory->create();
                $attachment->setTitle($fileData['title']);
                $attachment->setDescription('Sample ' . $fileData['type_code'] . ' file for demonstration purposes');

                if (isset($typesMap[$fileData['type_code']])) {
                    $attachment->setAttachmentTypeId($typesMap[$fileData['type_code']]);
                }

                $attachment->setData('filename', $fileData['filename']);
                $attachment->setData('original_filename', $fileData['original_name']);
                $attachment->setData('file_path', $fileData['relative_path']);
                $attachment->setData('file_size', $fileData['size']);
                $attachment->setData('mime_type', $fileData['mime_type']);
                $attachment->setData('is_link', 0);

                $customerGroupIds = $this->getAllCustomerGroupIds();
                $attachment->setData('customer_group_ids', implode(',', $customerGroupIds));

                $attachment->setIsActive(true);
                $attachment->setSortOrder($sortOrder);

                $savedAttachment = $this->attachmentRepository->save($attachment);

                try {
                    $attachmentFile = $this->attachmentFileFactory->create();
                    $attachmentFile->setAttachmentId($savedAttachment->getAttachmentId());
                    $attachmentFile->setFilename($fileData['filename']);
                    $attachmentFile->setOriginalFilename($fileData['original_name']);
                    $attachmentFile->setFilePath($fileData['relative_path']);
                    $attachmentFile->setFileSize($fileData['size']);
                    $attachmentFile->setMimeType($fileData['mime_type']);

                    $extension = strtoupper(pathinfo($fileData['filename'], PATHINFO_EXTENSION));
                    $attachmentFile->setData('file_extension', $extension);

                    $attachmentFile->setData('is_primary', 1);
                    $attachmentFile->setData('sort_order', 0);

                    $attachmentFile->save();
                } catch (\Exception $fileEx) {
                    $this->attachmentRepository->delete($savedAttachment);
                    throw new \Exception("File save failed: " . $fileEx->getMessage(), 0, $fileEx);
                }

                $connection = $attachment->getResource()->getConnection();

                $relationTable = '';
                $relationData = [
                    'attachment_id' => $savedAttachment->getAttachmentId(),
                    'sort_order' => $sortOrder
                ];

                switch ($entityType) {
                    case 'product':
                        $relationTable = 'panth_product_attachment_product';
                        $relationData['product_id'] = $entityId;
                        break;
                    case 'category':
                        $relationTable = 'panth_product_attachment_category';
                        $relationData['category_id'] = $entityId;
                        break;
                    case 'page':
                        $relationTable = 'panth_product_attachment_page';
                        $relationData['page_id'] = $entityId;
                        break;
                }

                if ($relationTable) {
                    $connection->insertOnDuplicate(
                        $connection->getTableName($relationTable),
                        $relationData
                    );
                }

                $storeRelationData = [
                    'attachment_id' => $savedAttachment->getAttachmentId(),
                    'store_id' => $storeId
                ];
                $connection->insertOnDuplicate(
                    $connection->getTableName('panth_product_attachment_store'),
                    $storeRelationData
                );

                $attachmentIds[] = $savedAttachment->getAttachmentId();
                $sortOrder += 10;
            } catch (\Exception $e) {
                error_log("Failed to create attachment {$fileData['original_name']}: " . $e->getMessage());
                continue;
            }
        }

        return $attachmentIds;
    }

    private function createCmsPage(int $productId)
    {
        $identifier = 'product-attachments-demo';

        try {
            $collection = $this->pageFactory->create()->getCollection()
                ->addFieldToFilter('identifier', $identifier);
            if ($collection->getSize() > 0) {
                foreach ($collection as $existingPage) {
                    $this->pageRepository->delete($existingPage);
                }
            }
        } catch (\Exception $e) {
        }

        $page = $this->pageFactory->create();
        $page->setTitle('Product Attachments Demo - Table & List Views');
        $page->setIdentifier($identifier);
        $page->setIsActive(true);
        $page->setStores([0]);
        $page->setPageLayout('1column');

        $content = <<<HTML
<div class="page-title-wrapper">
    <h1 class="page-title">Product Attachments Demo</h1>
</div>

<div class="page-content">
    <p>This page demonstrates the Product Attachments module with both <strong>Table View</strong> and <strong>List View</strong> layouts. All attachments are loaded from the sample product created by the installation command.</p>

    <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #1976d2;">Features Demonstrated:</h3>
        <ul style="margin-bottom: 0;">
            <li>30 different attachment types (PDF, images, videos, documents, etc.)</li>
            <li>Bootstrap Icons for file type visualization</li>
            <li>Preview functionality with Magento modal popup</li>
            <li>Download functionality</li>
            <li>Responsive design (2-column on desktop, 1-column on mobile)</li>
            <li>Both Table and List view modes</li>
        </ul>
    </div>

    <hr style="margin: 40px 0; border: none; border-top: 2px solid #e0e0e0;">

    <h2 style="color: #2c3e50; margin-top: 40px;">Table View</h2>
    <p>The table view displays attachments in a structured table format with columns for icon, title, file info, type, and actions.</p>

    {{widget type="Panth\ProductAttachments\Block\Widget\Attachments" title="Product Attachments - Table View" product_id="{$productId}" display_mode="table" template="Panth_ProductAttachments::widget/attachments.phtml"}}

    <hr style="margin: 40px 0; border: none; border-top: 2px solid #e0e0e0;">

    <h2 style="color: #2c3e50; margin-top: 40px;">List View</h2>
    <p>The list view displays attachments as cards in a 2-column grid layout (1-column on mobile devices) with compact presentation.</p>

    {{widget type="Panth\ProductAttachments\Block\Widget\Attachments" title="Product Attachments - List View" product_id="{$productId}" display_mode="list" template="Panth_ProductAttachments::widget/attachments.phtml"}}

    <hr style="margin: 40px 0; border: none; border-top: 2px solid #e0e0e0;">

    <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #856404;">Tips:</h3>
        <ul style="margin-bottom: 0;">
            <li>Click the <strong>eye icon</strong> to preview files (PDFs and images supported)</li>
            <li>Click the <strong>download icon</strong> to download files</li>
            <li>Resize your browser to see the responsive behavior</li>
            <li>Check the product page to see attachments in product context</li>
        </ul>
    </div>
</div>
HTML;

        $page->setContent($content);

        return $this->pageRepository->save($page);
    }

    private function getAllCustomerGroupIds(): array
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $groups = $this->groupRepository->getList($searchCriteria)->getItems();

            $groupIds = [];
            foreach ($groups as $group) {
                $groupIds[] = $group->getId();
            }

            return $groupIds;
        } catch (\Exception $e) {
            return [0, 1, 2, 3];
        }
    }
}
