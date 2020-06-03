<?php

declare(strict_types=1);

namespace Facile\MongoDbMessenger\Tests\Unit\Transport;

use Facile\MongoDbMessenger\Tests\Stubs\NotInstantiableDocumentEnhancer;
use Facile\MongoDbMessenger\Transport\TransportFactory;
use MongoDB\Database;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class TransportFactoryTest extends TestCase
{
    public function testCreateTransportWithWrongDSN(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('mongo.connection.foobar')
            ->shouldNotBeCalled();
        $factory = new TransportFactory($container->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given MongoDB Messenger DSN ":" is invalid.');

        $factory->createTransport(':', [], $this->mockSerializer());
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

    public function testCreateTransportWithAdditionalOptionsInQueryString(): void
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

    private function mockContainer(): ContainerInterface
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('mongo.connection.foobar')
            ->willReturn($this->prophesize(Database::class)->reveal());

        return $container->reveal();
    }

    private function mockSerializer(): SerializerInterface
    {
        return $this->prophesize(SerializerInterface::class)->reveal();
    }
}
