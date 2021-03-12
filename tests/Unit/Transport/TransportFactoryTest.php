<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Unit\Transport;

use Facile\MongoDbMessenger\Extension\DocumentEnhancer\LastErrorMessageEnhancer;
use Facile\MongoDbMessenger\Tests\Stubs\InstantiableDocumentEnhancer;
use Facile\MongoDbMessenger\Tests\Stubs\NotInstantiableDocumentEnhancer;
use Facile\MongoDbMessenger\Transport\MongoDbTransport;
use Facile\MongoDbMessenger\Transport\TransportFactory;
use MongoDB\Collection;
use MongoDB\Database;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class TransportFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateTransportWithAdditionalOptionsInQueryString(): void
    {
        $factory = new TransportFactory($this->mockContainerWithWorkingCollection('bar'));

        $transport = $factory->createTransport('mongodb://foobar?collection_name=bar', [], $this->mockSerializer());

        $this->assertInstanceOf(MongoDbTransport::class, $transport);
    }

    /**
     * @dataProvider invalidDSNDataProvider
     */
    public function testCreateTransportWithWrongDSN(string $invalidDSN, string $message): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('mongo.connection.foobar')
            ->shouldNotBeCalled();
        $factory = new TransportFactory($container->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $factory->createTransport($invalidDSN, [], $this->mockSerializer());
    }

    /**
     * @return string[][]
     */
    public function invalidDSNDataProvider(): array
    {
        return [
            [':', 'The given MongoDB Messenger DSN ":" is invalid.'],
            ['?foo=bar', 'Error while parsing DSN'],
        ];
    }

    public function testCreateTransportWithWrongConnectionName(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('mongo.connection.foobar')
            ->willReturn(null);
        $factory = new TransportFactory($container->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot find MongoDB connection with name: foobar');

        $factory->createTransport('mongodb://foobar', [], $this->mockSerializer());
    }

    public function testCreateTransportWithAdditionalOptions(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('mongo.connection.foobar')
            ->shouldNotBeCalled();
        $factory = new TransportFactory($container->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown option found : [foo]');

        $factory->createTransport('mongodb://foobar', ['foo' => 'bar'], $this->mockSerializer());
    }

    public function testCreateTransportWithWrongOptionsInQueryString(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('mongo.connection.foobar')
            ->shouldNotBeCalled();
        $factory = new TransportFactory($container->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown option found in DSN: [foo]');

        $factory->createTransport('mongodb://foobar?foo=bar', [], $this->mockSerializer());
    }

    public function testCreateTransportWithWrongObjectReturnedFromContainer(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('mongo.connection.foobar')
            ->willReturn(new \DateTime());
        $factory = new TransportFactory($container->reveal());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expecting MongoDB\Database from container, got DateTime');

        $factory->createTransport('mongodb://foobar', [], $this->mockSerializer());
    }

    public function testCreateTransportWithWrongDocumentEnhancerFQCN(): void
    {
        $options = [
            'document_enhancers' => [\DateTime::class],
        ];
        $factory = new TransportFactory($this->mockContainer());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expecting class that implements DocumentEnhancer, got: DateTime');

        $factory->createTransport('mongodb://foobar', $options, $this->mockSerializer());
    }

    public function testCreateTransportWithDocumentEnhancerFQCNWhichRequiresConstructorArguments(): void
    {
        $options = [
            'document_enhancers' => [NotInstantiableDocumentEnhancer::class],
        ];
        $factory = new TransportFactory($this->mockContainer());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Class ' . NotInstantiableDocumentEnhancer::class . ' is not instantiable without arguments');

        $factory->createTransport('mongodb://foobar', $options, $this->mockSerializer());
    }

    public function testCreateTransportWithDocumentEnhancerFQCNWhichHasOptionalConstructorArguments(): void
    {
        $options = [
            'document_enhancers' => [InstantiableDocumentEnhancer::class],
        ];
        $factory = new TransportFactory($this->mockContainerWithWorkingCollection());

        $transport = $factory->createTransport('mongodb://foobar', $options, $this->mockSerializer());

        $this->assertInstanceOf(MongoDbTransport::class, $transport);
    }

    public function testCreateTransportWithDocumentEnhancerInvalidServiceReferenceString(): void
    {
        $options = [
            'document_enhancers' => ['@'],
        ];
        $factory = new TransportFactory($this->mockContainer());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('value is neither a service reference nor an existing class');

        $factory->createTransport('mongodb://foobar', $options, $this->mockSerializer());
    }

    /**
     * @dataProvider validEnhancerDataProvider
     */
    public function testCreateTransportWithWrongDocumentEnhancerAfterAGoodOne(string $validEnhancer): void
    {
        $options = [
            'document_enhancers' => [
                $validEnhancer,
                \DateTime::class,
            ],
        ];
        $factory = new TransportFactory($this->mockContainer());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expecting class that implements DocumentEnhancer, got: DateTime');

        $factory->createTransport('mongodb://foobar', $options, $this->mockSerializer());
    }

    /**
     * @return array{0: string}[]
     */
    public function validEnhancerDataProvider(): array
    {
        return [
            [LastErrorMessageEnhancer::class],
            [InstantiableDocumentEnhancer::class],
            ['@acme.service.document_enhancer'],
        ];
    }

    private function mockContainer(): ContainerInterface
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('mongo.connection.foobar')
            ->willReturn($this->prophesize(Database::class)->reveal());

        return $container->reveal();
    }

    private function mockContainerWithWorkingCollection(string $collectionName = 'messenger_messages'): ContainerInterface
    {
        $container = $this->prophesize(ContainerInterface::class);
        $database = $this->prophesize(Database::class);
        $database->selectCollection($collectionName)
            ->shouldBeCalledOnce()
            ->willReturn($this->prophesize(Collection::class)->reveal());
        $container->get('mongo.connection.foobar')
            ->shouldBeCalledOnce()
            ->willReturn($database->reveal());

        return $container->reveal();
    }

    private function mockSerializer(): SerializerInterface
    {
        return $this->prophesize(SerializerInterface::class)->reveal();
    }
}
