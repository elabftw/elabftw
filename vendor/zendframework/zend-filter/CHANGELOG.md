# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.7.1 - 2016-04-18

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#27](https://github.com/zendframework/zend-filter/pull/27) fixes the
  `Module::init()` method to properly receive a `ModuleManager` instance, and
  not expect a `ModuleEvent`.

## 2.7.0 - 2016-04-06

### Added

- [#25](https://github.com/zendframework/zend-filter/pull/25) exposes the
  package as a ZF component and/or generic configuration provider, by adding the
  following:
  - `FilterPluginManagerFactory`, which can be consumed by container-interop /
    zend-servicemanager to create and return a `FilterPluginManager` instance.
  - `ConfigProvider`, which maps the service `FilterManager` to the above
    factory.
  - `Module`, which does the same as `ConfigProvider`, but specifically for
    zend-mvc applications. It also provices a specification to
    `Zend\ModuleManager\Listener\ServiceListener` to allow modules to provide
    filter configuration.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.6.1 - 2016-02-08

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#24](https://github.com/zendframework/zend-filter/pull/24) updates the
  `FilterPluginManager` to reference the `NumberFormat` **filter**, instead of
  the **view helper**.

## 2.6.0 - 2016-02-04

### Added

- [#14](https://github.com/zendframework/zend-filter/pull/14) adds the
  `UpperCaseWords` filter to the default list of filters known to the
  `FilterPluginManager`.
- [#22](https://github.com/zendframework/zend-filter/pull/22) adds
  documentation, and automatically publishes it to
  https://zendframework.github.io/zend-filter/

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#15](https://github.com/zendframework/zend-filter/pull/15),
  [#19](https://github.com/zendframework/zend-filter/pull/19), and
  [#21](https://github.com/zendframework/zend-filter/pull/21)
  update the component to be forwards-compatible with zend-servicemanager v3,
  and reduce the number of development dependencies required for testing.
