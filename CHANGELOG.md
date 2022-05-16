# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 1.4.0 (2022-05-16)
* Allow Symfony 6.0 ([#14](https://github.com/facile-it/mongodb-messenger-transport/issues/14))

## 1.3.1 (2022-02-07)
* Fix handling of headers during serialization ([#13](https://github.com/facile-it/mongodb-messenger-transport/issues/13)); this unlocks the possibility of using JSON serialization, i.e. with the Symfony Serializer 

## 1.3.0 (2021-08-12)
* Force `typeMap['root']` to `BSONDocument` ([#8](https://github.com/facile-it/mongodb-messenger-transport/issues/8))
* Add `resettable` option to add the choice of having a transport that does not implement `ResetInterface` (#10); default is `true` for BC, but it should be a possible fix for tests under Symfony 5.3 that wipe the queue due to that.

## 1.2.0 (2021-03-12)
* Allow PHP 8 (#9)
* Drop support for PHP 7.2 (#9)

## 1.1.0 (2021-03-12)
* Adapt behaviour when retrieving last error from stamps due to [symfony/symfony#32904](https://github.com/symfony/symfony/pull/32904) (#5)
* Deprecate `RedeliveryStampExtractor` when using Symfony >= 5.2 due to [symfony/symfony#32904](https://github.com/symfony/symfony/pull/32904) (#5) 

## 1.0.0 (2020-07-08)
First release of this package; features include:
 - A Symfony Messenger transport that relies on MongoDB, using [`facile-it/mongodb-bundle`](https://github.com/facile-it/mongodb-bundle/)
 - The bundle support to be used in a Symfony app
 - An extension point to enrich the persisted document, using the `DocumentEnhancer` interface
 - A non-sendable `ReceivedStamp` stamp class dedicated to obtain the ID of the persisted document
 - A `RedeliveryStampExtractor` utility class
