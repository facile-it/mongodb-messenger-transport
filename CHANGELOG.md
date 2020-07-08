# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 1.0.0 (2020-07-08)
First release of this package; features include:
 - A Symfony Messenger transport that relies on MongoDB, using [`facile-it/mongodb-bundle`](https://github.com/facile-it/mongodb-bundle/)
 - The bundle support to be used in a Symfony app
 - An extension point to enrich the persisted document, using the `DocumentEnhancer` interface
 - A non-sendable `ReceivedStamp` stamp class dedicated to obtain the ID of the persisted document
 - A `RedeliveryStampExtractor` utility class
