<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Controller\Adminhtml\Type;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Panth\ProductAttachments\Api\AttachmentTypeRepositoryInterface;

class InlineEdit extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_ProductAttachments::type_save';

    protected $jsonFactory;

    protected $typeRepository;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        AttachmentTypeRepositoryInterface $typeRepository
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->typeRepository = $typeRepository;
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data sent.')],
                'error' => true,
            ]);
        }

        foreach (array_keys($postItems) as $typeId) {
            try {
                $type = $this->typeRepository->getById((int)$typeId);
                $type->setData(array_merge($type->getData(), $postItems[$typeId]));
                $this->typeRepository->save($type);
            } catch (\Exception $e) {
                $messages[] = $e->getMessage();
                $error = true;
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }
}
