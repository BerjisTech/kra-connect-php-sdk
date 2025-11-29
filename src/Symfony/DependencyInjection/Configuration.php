<?php

declare(strict_types=1);

namespace KraConnect\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Symfony Configuration Definition for KRA Connect
 *
 * @package KraConnect\Symfony\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Get configuration tree builder.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('kra_connect');

        $treeBuilder->getRootNode()
            ->children()
                // API Configuration
                ->scalarNode('api_key')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('Your KRA GavaConnect API key')
                ->end()
                ->scalarNode('base_url')
                    ->defaultValue('https://api.kra.go.ke/gavaconnect/v1')
                    ->info('The base URL for the KRA API')
                ->end()
                ->floatNode('timeout')
                    ->defaultValue(30.0)
                    ->min(1.0)
                    ->info('Request timeout in seconds')
                ->end()
                ->booleanNode('debug')
                    ->defaultFalse()
                    ->info('Enable debug mode')
                ->end()
                ->scalarNode('user_agent')
                    ->defaultNull()
                    ->info('Custom user agent string')
                ->end()
                ->arrayNode('additional_headers')
                    ->useAttributeAsKey('name')
                    ->scalarPrototype()->end()
                    ->info('Additional HTTP headers')
                ->end()

                // Cache Configuration
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                            ->info('Enable caching')
                        ->end()
                        ->integerNode('ttl')
                            ->defaultValue(3600)
                            ->min(0)
                            ->info('Default cache TTL in seconds')
                        ->end()
                        ->integerNode('pin_verification_ttl')
                            ->defaultValue(3600)
                            ->min(0)
                            ->info('PIN verification cache TTL in seconds')
                        ->end()
                        ->integerNode('tcc_verification_ttl')
                            ->defaultValue(1800)
                            ->min(0)
                            ->info('TCC verification cache TTL in seconds')
                        ->end()
                        ->integerNode('eslip_validation_ttl')
                            ->defaultValue(3600)
                            ->min(0)
                            ->info('E-slip validation cache TTL in seconds')
                        ->end()
                        ->integerNode('taxpayer_details_ttl')
                            ->defaultValue(7200)
                            ->min(0)
                            ->info('Taxpayer details cache TTL in seconds')
                        ->end()
                        ->scalarNode('prefix')
                            ->defaultValue('kra_connect:')
                            ->info('Cache key prefix')
                        ->end()
                        ->integerNode('max_size')
                            ->defaultValue(1000)
                            ->min(1)
                            ->info('Maximum cache size')
                        ->end()
                    ->end()
                ->end()

                // Rate Limit Configuration
                ->arrayNode('rate_limit')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                            ->info('Enable rate limiting')
                        ->end()
                        ->integerNode('max_requests')
                            ->defaultValue(100)
                            ->min(1)
                            ->info('Maximum requests per window')
                        ->end()
                        ->integerNode('window_seconds')
                            ->defaultValue(60)
                            ->min(1)
                            ->info('Rate limit window in seconds')
                        ->end()
                        ->booleanNode('block_on_limit')
                            ->defaultTrue()
                            ->info('Block when rate limit is reached')
                        ->end()
                    ->end()
                ->end()

                // Retry Configuration
                ->arrayNode('retry')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_retries')
                            ->defaultValue(3)
                            ->min(0)
                            ->info('Maximum number of retry attempts')
                        ->end()
                        ->floatNode('initial_delay')
                            ->defaultValue(1.0)
                            ->min(0.1)
                            ->info('Initial retry delay in seconds')
                        ->end()
                        ->floatNode('max_delay')
                            ->defaultValue(32.0)
                            ->min(1.0)
                            ->info('Maximum retry delay in seconds')
                        ->end()
                        ->floatNode('backoff_multiplier')
                            ->defaultValue(2.0)
                            ->min(1.1)
                            ->info('Exponential backoff multiplier')
                        ->end()
                        ->booleanNode('retry_on_timeout')
                            ->defaultTrue()
                            ->info('Retry on timeout errors')
                        ->end()
                        ->booleanNode('retry_on_server_error')
                            ->defaultTrue()
                            ->info('Retry on server errors (5xx)')
                        ->end()
                        ->booleanNode('retry_on_rate_limit')
                            ->defaultTrue()
                            ->info('Retry on rate limit errors')
                        ->end()
                        ->arrayNode('retryable_status_codes')
                            ->integerPrototype()->end()
                            ->defaultValue([408, 429, 500, 502, 503, 504])
                            ->info('HTTP status codes that trigger retries')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
