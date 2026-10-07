<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit\Block;

use Magento\Framework\UrlInterface;

trait BlockInstantiationTrait
{
    private array $urlCalls = [];

    private function instantiate(string $class, array $properties = [])
    {
        $reflection = new \ReflectionClass($class);
        $object = $reflection->newInstanceWithoutConstructor();
        foreach ($properties as $name => $value) {
            $this->setProperty($object, $name, $value);
        }
        return $object;
    }

    private function setProperty(object $object, string $name, $value): void
    {
        $class = new \ReflectionClass($object);
        while ($class && !$class->hasProperty($name)) {
            $class = $class->getParentClass();
        }
        if (!$class) {
            throw new \InvalidArgumentException('Unknown property ' . $name);
        }
        $class->getProperty($name)->setValue($object, $value);
    }

    private function urlBuilder(): UrlInterface
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            function ($route = null, $params = null) {
                $this->urlCalls[] = [$route, $params];
                $query = $params ? '?' . http_build_query($params) : '';
                return 'https://shop.test/' . $route . $query;
            }
        );
        return $url;
    }
}
