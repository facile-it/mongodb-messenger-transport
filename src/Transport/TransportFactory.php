<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Transport;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer;
use MongoDB\Database;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;

/**
 * @template-implements TransportFactoryInterface<MongoDbUnresettableTransport>
 */
final class TransportFactory implements TransportFactoryInterface
{
    private const DEFAULT_OPTIONS = [
        self::COLLECTION_NAME => 'messenger_messages',
        self::QUEUE_NAME => 'default',
        self::REDELIVER_TIMEOUT => 3_600,
        self::DOCUMENT_ENHANCERS => [],
        self::RESETTABLE => true,
    ];

    public const CONNECTION_NAME = 'connection_name';

    public const COLLECTION_NAME = 'collection_name';

    public const QUEUE_NAME = 'queue_name';

    public const REDELIVER_TIMEOUT = 'redeliver_timeout';

    public const DOCUMENT_ENHANCERS = 'document_enhancers';

    public const RESETTABLE = 'resettable';

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    /**
     * @param array<string, mixed> $options
     */
    public function supports(string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'facile-it-mongodb://');
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): MongoDbUnresettableTransport
    {
        $configuration = $this->buildConfiguration($dsn, $options);

        $database = $this->container->get('mongo.connection.' . $configuration[self::CONNECTION_NAME]);
        if ($database === null) {
            throw new \InvalidArgumentException('Cannot find MongoDB connection with name: ' . $configuration[self::CONNECTION_NAME]);
        }

        if (! $database instanceof Database) {
            throw new \LogicException('Expecting MongoDB\\Database from container, got ' . $database::class);
        }

        $connection = new Connection(
            $database->selectCollection($configuration[self::COLLECTION_NAME]),
            $configuration[self::QUEUE_NAME],
            $configuration[self::REDELIVER_TIMEOUT],
        );

        $this->addDocumentEnhancers($connection, $configuration);

        if ($configuration[self::RESETTABLE]) {
            return new MongoDbTransport($connection, $serializer);
        }

        return new MongoDbUnresettableTransport($connection, $serializer);
    }

    /**
     * @param array{document_enhancers: string[]} $options
     */
    private function addDocumentEnhancers(Connection $connection, array $options): void
    {
        foreach ($options[self::DOCUMENT_ENHANCERS] as $name) {
            $enhancer = $this->isServiceDefinition($name)
                ? $this->container->get(ltrim($name, '@'))
                : new $name();

            if (! $enhancer instanceof DocumentEnhancer) {
                throw new \InvalidArgumentException('Expecting DocumentEnhancer, got ' . get_debug_type($enhancer));
            }

            $connection->addDocumentEnhancer($enhancer);
        }
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{connection_name: string, collection_name: string, queue_name: string, redeliver_timeout: int, document_enhancers: string[], resettable: bool|0|1}
     */
    private function buildConfiguration(string $dsn, array $options = []): array
    {
        if (false === $components = parse_url($dsn)) {
            throw new InvalidArgumentException(sprintf('The given MongoDB Messenger DSN "%s" is invalid.', $dsn));
        }

        $query = [];
        if (isset($components['query'])) {
            parse_str($components['query'], $query);
        }

        if (! isset($components['host'])) {
            throw new \InvalidArgumentException('Error while parsing DSN: ' . $dsn);
        }

        $configuration = [self::CONNECTION_NAME => $components['host']];
        unset($options['transport_name']);
        $configuration += $options + $query + self::DEFAULT_OPTIONS;

        $this->validateDocumentEnhancers($configuration[self::DOCUMENT_ENHANCERS]);

        // check for extra keys in options
        $optionsExtraKeys = array_diff(array_keys($options), array_keys(self::DEFAULT_OPTIONS));
        if (0 < \count($optionsExtraKeys)) {
            throw new \InvalidArgumentException(sprintf('Unknown option found : [%s]. Allowed options are [%s].', implode(', ', $optionsExtraKeys), implode(', ', array_keys(self::DEFAULT_OPTIONS))));
        }

        // check for extra keys in options
        $queryExtraKeys = array_diff(array_keys($query), array_keys(self::DEFAULT_OPTIONS));
        if (0 < \count($queryExtraKeys)) {
            throw new \InvalidArgumentException(sprintf('Unknown option found in DSN: [%s]. Allowed options are [%s].', implode(', ', $queryExtraKeys), implode(', ', array_keys(self::DEFAULT_OPTIONS))));
        }

        if (! in_array($configuration[self::RESETTABLE], [0, 1, true, false], true)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown option value for resettable: [%s]. Allowed values are booleans, 0 or 1.',
                print_r($configuration[self::RESETTABLE], true),
            ));
        }

        return $configuration;
    }

    /**
     * @param mixed $enhancers
     *
     * @throws \InvalidArgumentException If any of the document_enhancers values is not valid
     *
     * @phpstan-assert array<array-key, string> $enhancers
     */
    private function validateDocumentEnhancers($enhancers): void
    {
        if (! is_array($enhancers)) {
            throw new \InvalidArgumentException('Document enhancers should array, got ' . get_debug_type($enhancers));
        }

        foreach ($enhancers as $name) {
            if (! is_string($name)) {
                throw new \InvalidArgumentException('Document enhancers should be strings, got ' . get_debug_type($name));
            }

            if ($this->isServiceDefinition($name)) {
                continue;
            }

            if (! class_exists($name)) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid entry in document_enhancers option: "%s" - value is neither a service reference nor an existing class',
                    $name,
                ));
            }

            $reflectionClass = new \ReflectionClass($name);
            if (! $reflectionClass->implementsInterface(DocumentEnhancer::class)) {
                throw new \InvalidArgumentException('Expecting class that implements DocumentEnhancer, got: ' . $name);
            }

            $constructor = $reflectionClass->getConstructor();

            if (null === $constructor) {
                continue;
            }

            foreach ($constructor->getParameters() as $parameter) {
                if ($parameter->isDefaultValueAvailable()) {
                    continue;
                }

                throw new \InvalidArgumentException(sprintf('Class %s is not instantiable without arguments; if you want to use it as a DocumentEnhancer please define it as a service and add the service reference in the options instead', $name));
            }
        }
    }

    private function isServiceDefinition(string $name): bool
    {
        if (strlen($name) <= 1) {
            return false;
        }

        return str_starts_with($name, '@');
    }
}
