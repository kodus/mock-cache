kodus/mock-cache
================

[![PHP Version](https://img.shields.io/badge/php-8.1%2B-blue.svg)](https://packagist.org/packages/kodus/mock-cache)
[![Build Status](https://travis-ci.org/kodus/mock-cache.svg?branch=master)](https://travis-ci.org/kodus/mock-cache)
[![Code Coverage](https://scrutinizer-ci.com/g/kodus/mock-cache/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/kodus/mock-cache/?branch=master)

This library provides a [PSR-16](https://github.com/php-fig/fig-standards/blob/master/proposed/simplecache.md)
cache-implementation for integration testing, backed by a simple `array`.

It simulates a simple system clock and expiration - in your tests, calling e.g. `$cache->skipTime(10)` will
travel forward in time 10 seconds, but time will otherwise stand still, so you don't need to write integration
tests that have to `sleep()` to test for side-effects from cache-expiration.
