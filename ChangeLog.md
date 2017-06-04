URI handling change log
=======================

## ?.?.? / ????-??-??

## 1.0.0 / 2017-06-04

* Added forward compatibility with XP 9.0.0 - @thekid

## 0.5.0 / 2017-03-26

* Added `asPath()` method to convert URIs to file paths
  (@thekid)
* Added `URI::file()` method to create URIs from file paths
  (@thekid)

## 0.4.0 / 2017-03-26

* Added default encoding for path, query and fragment to the 
  fluent interface returned by `using()` and `with()`.
  (@thekid)
* Added default decoding for path, query and fragment; it can be
  bypassed by passing `false` to the respective accessor methods.
  (@thekid)

## 0.3.0 / 2017-03-26

* Reorganized packages: Only `URI` and `Authority` classes stay in
  the top-level `util` package, utility classes are moved to the new
  `util.uri` package.
  (@thekid)

## 0.2.0 / 2017-03-26

* Implemented write access to parameters via `params()` and `param()`
  methods on the `URICreation` class
  (@thekid)
* Implemented read access to parameters via `params()` and `param()`
  methods on the `URI` class
  (@thekid)

## 0.1.0 / 2017-03-24

* Hello World! First release - @thekid