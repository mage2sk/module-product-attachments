<?php
/**
 * Update Attachment Types with Bootstrap Icons
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

class UpdateAttachmentTypesWithBootstrapIcons implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        // Map existing types to Bootstrap Icons
        $iconUpdates = [
            'user_manual' => 'bi-book',
            'installation_guide' => 'bi-tools',
            'quick_start_guide' => 'bi-rocket-takeoff',
            'technical_specs' => 'bi-gear',
            'datasheet' => 'bi-file-text',
            'brochure' => 'bi-folder-open',
            'certificate' => 'bi-award',
            'warranty' => 'bi-shield-check',
            'safety_instructions' => 'bi-exclamation-triangle',
            'troubleshooting' => 'bi-question-circle',
            'product_images' => 'bi-images',
            'cad_drawings' => 'bi-pencil-square',
            'video_tutorial' => 'bi-camera-video',
            'firmware_software' => 'bi-download',
            'compliance' => 'bi-check-circle',
            'faq_document' => 'bi-question-diamond',
            'case_study' => 'bi-briefcase',
            'white_paper' => 'bi-file-earmark-pdf',
            'maintenance_guide' => 'bi-wrench-adjustable',
            'parts_list' => 'bi-list-ul',
            'other' => 'bi-file-earmark'
        ];

        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable('panth_product_attachment_type');

        foreach ($iconUpdates as $code => $iconClass) {
            try {
                $connection->update(
                    $tableName,
                    ['icon_class' => $iconClass],
                    ['code = ?' => $code]
                );
            } catch (\Exception $e) {
                // Log error but continue with other types
                continue;
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
        return [
            AddDefaultAttachmentTypes::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
