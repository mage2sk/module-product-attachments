<?php
/**
 * Add Default Attachment Types
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Model\AttachmentTypeFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType as AttachmentTypeResource;

class AddDefaultAttachmentTypes implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var AttachmentTypeFactory
     */
    private $typeFactory;

    /**
     * @var AttachmentTypeResource
     */
    private $typeResource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param AttachmentTypeFactory $typeFactory
     * @param AttachmentTypeResource $typeResource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        AttachmentTypeFactory $typeFactory,
        AttachmentTypeResource $typeResource,
        StoreManagerInterface $storeManager
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->typeFactory = $typeFactory;
        $this->typeResource = $typeResource;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $defaultTypes = [
            [
                'name' => 'User Manual',
                'code' => 'user_manual',
                'icon_class' => 'fa fa-book',
                'is_active' => 1,
                'sort_order' => 10
            ],
            [
                'name' => 'Installation Guide',
                'code' => 'installation_guide',
                'icon_class' => 'fa fa-wrench',
                'is_active' => 1,
                'sort_order' => 20
            ],
            [
                'name' => 'Quick Start Guide',
                'code' => 'quick_start_guide',
                'icon_class' => 'fa fa-rocket',
                'is_active' => 1,
                'sort_order' => 30
            ],
            [
                'name' => 'Technical Specifications',
                'code' => 'technical_specs',
                'icon_class' => 'fa fa-cogs',
                'is_active' => 1,
                'sort_order' => 40
            ],
            [
                'name' => 'Datasheet',
                'code' => 'datasheet',
                'icon_class' => 'fa fa-file-text',
                'is_active' => 1,
                'sort_order' => 50
            ],
            [
                'name' => 'Brochure',
                'code' => 'brochure',
                'icon_class' => 'fa fa-folder-open',
                'is_active' => 1,
                'sort_order' => 60
            ],
            [
                'name' => 'Certificate',
                'code' => 'certificate',
                'icon_class' => 'fa fa-certificate',
                'is_active' => 1,
                'sort_order' => 70
            ],
            [
                'name' => 'Warranty Information',
                'code' => 'warranty',
                'icon_class' => 'fa fa-shield',
                'is_active' => 1,
                'sort_order' => 80
            ],
            [
                'name' => 'Safety Instructions',
                'code' => 'safety_instructions',
                'icon_class' => 'fa fa-exclamation-triangle',
                'is_active' => 1,
                'sort_order' => 90
            ],
            [
                'name' => 'Troubleshooting Guide',
                'code' => 'troubleshooting',
                'icon_class' => 'fa fa-question-circle',
                'is_active' => 1,
                'sort_order' => 100
            ],
            [
                'name' => 'Product Images',
                'code' => 'product_images',
                'icon_class' => 'fa fa-image',
                'is_active' => 1,
                'sort_order' => 110
            ],
            [
                'name' => 'CAD Drawings',
                'code' => 'cad_drawings',
                'icon_class' => 'fa fa-pencil-square',
                'is_active' => 1,
                'sort_order' => 120
            ],
            [
                'name' => 'Video Tutorial',
                'code' => 'video_tutorial',
                'icon_class' => 'fa fa-video-camera',
                'is_active' => 1,
                'sort_order' => 130
            ],
            [
                'name' => 'Firmware/Software',
                'code' => 'firmware_software',
                'icon_class' => 'fa fa-download',
                'is_active' => 1,
                'sort_order' => 140
            ],
            [
                'name' => 'Compliance Documents',
                'code' => 'compliance',
                'icon_class' => 'fa fa-check-circle',
                'is_active' => 1,
                'sort_order' => 150
            ],
            [
                'name' => 'FAQ Document',
                'code' => 'faq_document',
                'icon_class' => 'fa fa-question',
                'is_active' => 1,
                'sort_order' => 160
            ],
            [
                'name' => 'Case Study',
                'code' => 'case_study',
                'icon_class' => 'fa fa-briefcase',
                'is_active' => 1,
                'sort_order' => 170
            ],
            [
                'name' => 'White Paper',
                'code' => 'white_paper',
                'icon_class' => 'fa fa-file-pdf-o',
                'is_active' => 1,
                'sort_order' => 180
            ],
            [
                'name' => 'Maintenance Guide',
                'code' => 'maintenance_guide',
                'icon_class' => 'fa fa-tools',
                'is_active' => 1,
                'sort_order' => 190
            ],
            [
                'name' => 'Parts List',
                'code' => 'parts_list',
                'icon_class' => 'fa fa-list-ul',
                'is_active' => 1,
                'sort_order' => 200
            ],
            [
                'name' => 'Other',
                'code' => 'other',
                'icon_class' => 'fa fa-file',
                'is_active' => 1,
                'sort_order' => 999
            ]
        ];

        foreach ($defaultTypes as $typeData) {
            // Check if type already exists
            $connection = $this->moduleDataSetup->getConnection();
            $select = $connection->select()
                ->from($this->moduleDataSetup->getTable('panth_product_attachment_type'))
                ->where('code = ?', $typeData['code']);

            $existingType = $connection->fetchRow($select);

            if (!$existingType) {
                // Create new type
                $type = $this->typeFactory->create();
                $type->setData($typeData);

                try {
                    $this->typeResource->save($type);

                    // Add store relations for all stores
                    $stores = $this->storeManager->getStores(true); // true = include admin
                    $storeData = [];
                    foreach ($stores as $store) {
                        $storeData[] = [
                            'type_id' => $type->getTypeId(),
                            'store_id' => $store->getId()
                        ];
                    }

                    if (!empty($storeData)) {
                        $connection->insertMultiple(
                            $this->moduleDataSetup->getTable('panth_product_attachment_type_store'),
                            $storeData
                        );
                    }
                } catch (\Exception $e) {
                    // Log error but continue with other types
                    continue;
                }
            }
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
