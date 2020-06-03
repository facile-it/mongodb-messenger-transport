<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Transport;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer;
use MongoDB\Database;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

final class TransportFactory implements TransportFactoryInterface
{
    private const DEFAULT_OPTIONS = [
        self::COLLECTION_NAME => 'messenger_messages',
        self::QUEUE_NAME => 'default',
        self::REDELIVER_TIMEOUT => 3600,
        self::DOCUMENT_ENHANCERS => [],
    ];

    public const CONNECTION_NAME = 'connection_name';
    public const COLLECTION_NAME = 'collection_name';
    public const QUEUE_NAME = 'queue_name';
    public const REDELIVER_TIMEOUT = 'redeliver_timeout';
    public const DOCUMENT_ENHANCERS = 'document_enhancers';

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function supports(string $dsn, array $options): bool
    {
        return 0 === strpos($dsn, 'facile-it-mongodb://');
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $configuration = $this->buildConfiguration($dsn, $options);

        $database = $this->container->get('mongo.connection.' . $configuration[self::CONNECTION_NAME]);
        if ($database === null) {
            throw new \InvalidArgumentException('Cannot find MongoDB connection with name: ' . $configuration[self::CONNECTION_NAME]);
        }

        if (! $database instanceof Database) {
            throw new \LogicException('Expecting MongoDB\\Database from container, got ' . get_class($database));
        }

        $connection = new Connection(
            $database->selectCollection($configuration[self::COLLECTION_NAME]),
            $configuration[self::QUEUE_NAME],
            $configuration[self::REDELIVER_TIMEOUT]
        );

        $this->addDocumentEnhancers($connection, $configuration);

        return new MongoDbTransport($connection, $serializer);
    }

    /**
     * @param array{document_enhancers: string[]} $options
     */
    private function addDocumentEnhancers(Connection $connection, array $options): void
    {
        foreach ($options[self::DOCUMENT_ENHANCERS] as $name) {
            if ($this->isServiceDefinition($name)) {
                $enhancer = $this->container->get(ltrim($name, '@'));
            } else {
                $enhancer = new $name();
            }

            if (! $enhancer instanceof DocumentEnhancer) {
                throw new \InvalidArgumentException('Expecting class that implements DocumentEnhancer, got: ' . get_class($enhancer));
            }

            $connection->addDocumentEnhancer($enhancer);
        }
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{connection_name: string, collection_name: string, queue_name: string, redeliver_timeout: int, document_enhancers: string[]}
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

        return $configuration;
    }

    /**
     * @param string[] $configuration
     *
     * @throws \InvalidArgumentException If any of the document_enhancers values is not valid
     */
    private function validateDocumentEnhancers(array $configuration): void
    {
        foreach ($configuration as $name) {
            if ($this->isServiceDefinition($name)) {
                continue;
            }

            if (! class_exists($name)) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid entry in document_enhancers option: "%s" - value is neither a service reference nor an existing class',
                    $name
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
                if ($parameter->isOptional() || $parameter->isDefaultValueAvailable()) {
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

        return strpos($name, '@') === 0;
    }
}
