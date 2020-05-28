# facile-it/mongodb-messenger-transport
A Symfony Messenger transport on MongoDB, on top of [`facile-it/mongodb-bundle`](https://github.com/facile-it/mongodb-bundle/)


[![Build Status](https://travis-ci.com/facile-it/mongodb-messenger-transport.svg?branch=master)](https://travis-ci.com/facile-it/mongodb-messenger-transport)
[![codecov](https://codecov.io/gh/facile-it/mongodb-messenger-transport/branch/master/graph/badge.svg)](https://codecov.io/gh/facile-it/mongodb-messenger-transport)

## Installation
 * To install this package, use Composer:
```bash
composer require facile-it/mongodb-messenger-transport
```
This package register itself as a bundle inside Symfony; you have to have both it and the `FacileMongoDbBundle` enabled, if you hadn't it before.
To do it, you either: 
 * let Flex enable it automatically, if you're using it:
```diff
# config/bundles.php

# ...
    Facile\MongoDbBundle\FacileMongoDbBundle::class => ['all' => true],
+     Facile\MongoDbMessenger\FacileMongoDbMessengerBundle::class => ['all' => true],
];
```
 * Enable it yourself in your kernel:
```diff
<?php

class Kernel extends BaseKernel
{
    public function registerBundles(): array
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            # ...
            new Facile\MongoDbBundle\FacileMongoDbBundle(),
+            new Facile\MongoDbMessenger\FacileMongoDbMessengerBundle(),
        ];
    }
}
```
 * [ONLY FOR THIS BUNDLE] Register the transport factory manually as a service in your container:
```yaml
services:
    Facile\MongoDbMessenger\Transport\TransportFactory:
        arguments:
          - '@service_container'
        tags:
          - ['messenger.transport_factory']
```

### Configuration
1. If you haven't already, configure the MongoDB connection following instructions for [`facile-it/mongodb-bundle`](https://github.com/facile-it/mongodb-bundle/blob/master/README.MD#configuration)
2. Using the connection name from that configuration (i.e. `default` in the Flex recipe), configure a new transport for the Messenger like this:
```yaml
# config/packages/messenger.yaml
framework:
  messenger:
    transports:
      new_transport: 'facile-it-mongodb://default'
```

Note: when using it for the first time, or when calling the `messenger:setup-transports` console command, this transport **creates the collection with an index** for optimal performances, since it's tailored to the properties that are used to retrieve the messages. 

## Suggestions
It's suggested to use this transport for failed messages, like the Doctrine one; if you want to do that, you can do it like this:
```yaml
framework:
  messenger:
    failure_transport: new_transport
    transports:
      new_transport: 'facile-it-mongodb://default'
```
If you configure this transport multiple times, remember to use the `queue_name` and/or the `collection_name` options (see below) to differentiate the messages.

## Full configuration reference
This transport, like other default ones, has a number of options available, which can be passed as a query string in the DSN, or as an array below, like in the following example (all values are the provided defaults):
```yaml
framework:
  messenger:
    transports:
      new_transport: 
        dsn: 'facile-it-mongodb://default'
        options:
          collection_name: 'messenger_messages'
          queue_name: 'default'
          redeliver_timeout: 3600
          document_enhancers: [] 
```

### The `redeliver_timeout` option
The `redeliver_timeout` works in the same way as the `DoctrineTransport`: when a message is delivered but not `ack`ed nor `reject`ed (maybe due to a fatal crash or a network failure), it works as a timeout, and makes the message redeliverable after it expires.

### The `document_enhancers` option
The `document_enhancers` option is an extension point of this transport; it accepts an array of strings, each of them a fully qualified class name or a service name with the `@` prefix.

It allows the end user to add fields to the document that will be persisted for each message. Each enhancer has to implement the `Facile\MongoDbMessenger\Extension\DocumentEnhancer` interface, which requires the implementation of a `enhance(BSONDocument $document, Envelope $envelope): void` method. The BSONDocument will be persisted afterwards, and it can be enriched with additional properties, which can be useful for searching and indexing for specific information with i.e. `MongoDbTransport::find`.

You can take a look at the provided `\Facile\MongoDbMessenger\Extension\DocumentEnhancer\LastErrorMessageEnhancer` as an example, or use it like this:
```yaml
framework:
  messenger:
    transports:
      new_transport: 
        dsn: 'facile-it-mongodb://default'
        options:
          document_enhancers:
          - 'Facile\MongoDbMessenger\Extension\DocumentEnhancer\LastErrorMessageEnhancer'
          # or
          - '@my_document_enhancer'

services:
  my_document_enhancer:
    class: App\My\Class # which implements the DocumentEnhancer interface
    arguments:
    - '...'
```
