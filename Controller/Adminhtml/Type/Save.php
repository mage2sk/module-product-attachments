<?php
/**
 * Save Attachment Type Controller
 *
 * @category  Panth
 * @package   Panth_ProductAttachments
 * @author    Panth
 * @copyright Copyright (c) 2025 Panth
 */
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Type;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ProductAttachments\Model\AttachmentTypeFactory;
use Panth\ProductAttachments\Model\ResourceModel\AttachmentType as AttachmentTypeResource;

class Save extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::type_save';

    /**
     * @var AttachmentTypeFactory
     */
    protected $typeFactory;

    /**
     * @var AttachmentTypeResource
     */
    protected $typeResource;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param AttachmentTypeFactory $typeFactory
     * @param AttachmentTypeResource $typeResource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        AttachmentTypeFactory $typeFactory,
        AttachmentTypeResource $typeResource,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->typeFactory = $typeFactory;
        $this->typeResource = $typeResource;
        $this->storeManager = $storeManager;
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $id = $this->getRequest()->getParam('type_id');

        try {
            $model = $this->typeFactory->create();

            if ($id) {
                $this->typeResource->load($model, $id);
                if (!$model->getId()) {
                    throw new LocalizedException(__('This attachment type no longer exists.'));
                }
            }

            // Set data
            if (isset($data['name'])) {
                $model->setName($data['name']);
            }
            if (isset($data['code'])) {
                $model->setCode($data['code']);
            }
            if (isset($data['icon_class'])) {
                $model->setIconClass($data['icon_class']);
            }
            if (isset($data['is_active'])) {
                $model->setIsActive($data['is_active']);
            }
            if (isset($data['sort_order'])) {
                $model->setSortOrder((int)$data['sort_order']);
            }

            // Save the model
            $this->typeResource->save($model);

            // Handle store relations
            if (isset($data['stores'])) {
                $this->saveStoreRelations($model->getTypeId(), $data['stores']);
            }

            $this->messageManager->addSuccessMessage(__('You saved the attachment type.'));

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['type_id' => $model->getTypeId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            if ($id) {
                return $resultRedirect->setPath('*/*/edit', ['type_id' => $id]);
            }
            return $resultRedirect->setPath('*/*/new');
        }
    }

    /**
     * Save store relations
     *
     * @param int $typeId
     * @param array $storeIds
     * @return void
     */
    protected function saveStoreRelations($typeId, $storeIds)
    {
        $connection = $this->typeResource->getConnection();
        $table = $this->typeResource->getTable('panth_product_attachment_type_store');

        // Delete existing relations
        $connection->delete($table, ['type_id = ?' => $typeId]);

        // Insert new relations
        if (!empty($storeIds)) {
            $data = [];
            foreach ($storeIds as $storeId) {
                $data[] = ['type_id' => $typeId, 'store_id' => $storeId];
            }
            $connection->insertMultiple($table, $data);
        }
    }
}
