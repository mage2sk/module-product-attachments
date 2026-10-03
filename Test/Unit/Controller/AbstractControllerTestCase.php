<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Controller;

use Laminas\Stdlib\Parameters;
use Magento\Backend\App\Action\Context as BackendContext;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Action\Context as FrontendContext;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;

abstract class AbstractControllerTestCase extends TestCase
{
    protected array $params = [];
    protected $postValue = null;
    protected array $files = [];
    protected array $server = [];
    protected bool $isPost = true;

    protected ?string $redirectPath = null;
    protected array $redirectParams = [];
    protected array $messages = ['success' => [], 'error' => [], 'warning' => []];
    protected array $resultsByType = [];

    protected HttpRequest $request;
    protected Redirect $redirect;
    protected ManagerInterface $messageManager;
    protected HttpResponse $response;
    protected ?Json $json = null;
    protected $jsonData = null;

    protected function createRequest(): HttpRequest
    {
        $request = $this->createStub(HttpRequest::class);
        $request->method('getParam')->willReturnCallback(
            fn ($key, $default = null) => $this->params[$key] ?? $default
        );
        $request->method('getPostValue')->willReturnCallback(fn () => $this->postValue);
        $request->method('getPost')->willReturnCallback(
            fn ($name = null, $default = null) => is_array($this->postValue)
                ? ($this->postValue[$name] ?? $default)
                : $default
        );
        $request->method('getFiles')->willReturnCallback(
            fn ($name = null, $default = null) => $name === null
                ? new Parameters($this->files)
                : ($this->files[$name] ?? $default)
        );
        $request->method('isPost')->willReturnCallback(fn () => $this->isPost);
        $request->method('getServer')->willReturnCallback(
            fn ($name = null, $default = null) => $this->server[$name] ?? $default
        );
        return $request;
    }

    protected function createRedirectResult(): Redirect
    {
        $redirect = $this->createStub(Redirect::class);
        $redirect->method('setPath')->willReturnCallback(
            function ($path, array $params = []) use (&$redirect) {
                $this->redirectPath = $path;
                $this->redirectParams = $params;
                return $redirect;
            }
        );
        return $redirect;
    }

    protected function createMessageManager(): ManagerInterface
    {
        $manager = $this->createStub(ManagerInterface::class);
        $map = [
            'addSuccessMessage' => 'success',
            'addErrorMessage' => 'error',
            'addWarningMessage' => 'warning',
        ];
        foreach ($map as $method => $bucket) {
            $manager->method($method)->willReturnCallback(
                function ($message) use ($bucket, &$manager) {
                    $this->messages[$bucket][] = (string)$message;
                    return $manager;
                }
            );
        }
        return $manager;
    }

    private function prepareCommon(): array
    {
        $this->request = $this->createRequest();
        $this->redirect = $this->createRedirectResult();
        $this->messageManager = $this->createMessageManager();
        $this->response = $this->createStub(HttpResponse::class);

        $redirectFactory = $this->createStub(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($this->redirect);

        $resultFactory = $this->createStub(ResultFactory::class);
        $resultFactory->method('create')->willReturnCallback(
            fn ($type) => $this->resultsByType[$type] ?? $this->redirect
        );

        $redirector = $this->createStub(RedirectInterface::class);
        $redirector->method('redirect')->willReturnCallback(
            function ($response, $path, $arguments = []) {
                $this->redirectPath = $path;
                $this->redirectParams = $arguments;
            }
        );

        return [
            'getRequest' => $this->request,
            'getResponse' => $this->response,
            'getResultRedirectFactory' => $redirectFactory,
            'getResultFactory' => $resultFactory,
            'getMessageManager' => $this->messageManager,
            'getRedirect' => $redirector,
        ];
    }

    protected function createBackendContext(array $extra = []): BackendContext
    {
        $context = $this->createStub(BackendContext::class);
        $helper = $this->createStub(BackendHelper::class);
        $helper->method('getUrl')->willReturnCallback(
            function ($route = '', $params = []) {
                $this->redirectPath = $route;
                $this->redirectParams = $params;
                return 'https://admin.test/' . $route;
            }
        );
        $backend = [
            'getSession' => $this->createStub(BackendSession::class),
            'getActionFlag' => $this->createStub(ActionFlag::class),
            'getHelper' => $helper,
        ];
        foreach (array_merge($this->prepareCommon(), $backend, $extra) as $method => $value) {
            $context->method($method)->willReturn($value);
        }
        return $context;
    }

    protected function createFrontendContext(array $extra = []): FrontendContext
    {
        $context = $this->createStub(FrontendContext::class);
        foreach (array_merge($this->prepareCommon(), $extra) as $method => $value) {
            $context->method($method)->willReturn($value);
        }
        return $context;
    }

    protected function createJsonFactory(): JsonFactory
    {
        $json = $this->createStub(Json::class);
        $json->method('setData')->willReturnCallback(
            function ($data) use (&$json) {
                $this->jsonData = $data;
                return $json;
            }
        );
        $this->json = $json;
        $factory = $this->createStub(JsonFactory::class);
        $factory->method('create')->willReturn($json);
        return $factory;
    }

    protected function jsonValue(string $key)
    {
        $value = $this->jsonData[$key] ?? null;
        return $value instanceof Phrase ? (string)$value : $value;
    }
}
