URI handling change log
=======================

## ?.?.? / ????-??-??

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