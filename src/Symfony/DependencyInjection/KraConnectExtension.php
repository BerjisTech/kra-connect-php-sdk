<?php

declare(strict_types=1);

namespace KraConnect\Symfony\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use KraConnect\KraClient;
use KraConnect\Config\KraConfig;
use KraConnect\Config\RetryConfig;
use KraConnect\Config\CacheConfig;
use KraConnect\Config\RateLimitConfig;

/**
 * Symfony Dependency Injection Extension for KRA Connect
 *
 * @package KraConnect\Symfony\DependencyInjection
 */
class KraConnectExtension extends Extension
{
    /**
     * Load configuration.
     *
     * @param array<mixed> $configs
     * @param ContainerBuilder $container
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Register RetryConfig
        $retryConfigDefinition = new Definition(RetryConfig::class);
        $retryConfigDefinition->setArguments([
            '$maxRetries' => $config['retry']['max_retries'],
            '$initialDelay' => $config['retry']['initial_delay'],
            '$maxDelay' => $config['retry']['max_delay'],
            '$backoffMultiplier' => $config['retry']['backoff_multiplier'],
            '$retryOnTimeout' => $config['retry']['retry_on_timeout'],
            '$retryOnServerError' => $config['retry']['retry_on_server_error'],
            '$retryOnRateLimit' => $config['retry']['retry_on_rate_limit'],
            '$retryableStatusCodes' => $config['retry']['retryable_status_codes'],
        ]);
        $container->setDefinition('kra_connect.retry_config', $retryConfigDefinition);

        // Register CacheConfig
        $cacheConfigDefinition = new Definition(CacheConfig::class);
        $cacheConfigDefinition->setArguments([
            '$enabled' => $config['cache']['enabled'],
            '$ttl' => $config['cache']['ttl'],
            '$pinVerificationTtl' => $config['cache']['pin_verification_ttl'],
            '$tccVerificationTtl' => $config['cache']['tcc_verification_ttl'],
            '$eslipValidationTtl' => $config['cache']['eslip_validation_ttl'],
            '$taxpayerDetailsTtl' => $config['cache']['taxpayer_details_ttl'],
            '$prefix' => $config['cache']['prefix'],
            '$maxSize' => $config['cache']['max_size'],
        ]);
        $container->setDefinition('kra_connect.cache_config', $cacheConfigDefinition);

        // Register RateLimitConfig
        $rateLimitConfigDefinition = new Definition(RateLimitConfig::class);
        $rateLimitConfigDefinition->setArguments([
            '$enabled' => $config['rate_limit']['enabled'],
            '$maxRequests' => $config['rate_limit']['max_requests'],
            '$windowSeconds' => $config['rate_limit']['window_seconds'],
            '$blockOnLimit' => $config['rate_limit']['block_on_limit'],
        ]);
        $container->setDefinition('kra_connect.rate_limit_config', $rateLimitConfigDefinition);

        // Register KraConfig
        $kraConfigDefinition = new Definition(KraConfig::class);
        $kraConfigDefinition->setArguments([
            '$apiKey' => $config['api_key'],
            '$baseUrl' => $config['base_url'],
            '$timeout' => $config['timeout'],
            '$retryConfig' => new Reference('kra_connect.retry_config'),
            '$cacheConfig' => new Reference('kra_connect.cache_config'),
            '$rateLimitConfig' => new Reference('kra_connect.rate_limit_config'),
            '$debug' => $config['debug'],
            '$userAgent' => $config['user_agent'],
            '$additionalHeaders' => $config['additional_headers'],
        ]);
        $container->setDefinition('kra_connect.config', $kraConfigDefinition);

        // Register KraClient
        $kraClientDefinition = new Definition(KraClient::class);
        $kraClientDefinition->setArguments([
            '$config' => new Reference('kra_connect.config'),
        ]);
        $kraClientDefinition->setPublic(true);
        $container->setDefinition(KraClient::class, $kraClientDefinition);
        $container->setAlias('kra_connect.client', KraClient::class);
    }

    /**
     * Get the alias for the extension.
     *
     * @return string
     */
    public function getAlias(): string
    {
        return 'kra_connect';
    }
}
