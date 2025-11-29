<?php

declare(strict_types=1);

namespace KraConnect\Symfony;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use KraConnect\Symfony\DependencyInjection\KraConnectExtension;

/**
 * Symfony Bundle for KRA Connect
 *
 * @package KraConnect\Symfony
 */
class KraConnectBundle extends Bundle
{
    /**
     * Get the bundle's container extension.
     *
     * @return KraConnectExtension
     */
    public function getContainerExtension(): KraConnectExtension
    {
        return new KraConnectExtension();
    }

    /**
     * Get the namespace of the bundle.
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return __NAMESPACE__;
    }

    /**
     * Get the path to the bundle.
     *
     * @return string
     */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
