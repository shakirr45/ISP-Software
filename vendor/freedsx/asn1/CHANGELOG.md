CHANGELOG
=========

0.4.8 (2024-10-13)
------------------
* Update method signature for Asn1::integer to allow numeric strings (for big ints). The underlying code already supports it.
* Add PHP 8.3 to CI runs.

0.4.7 (2023-06-06)
------------------
* Removed return type of AbstractType::getValue, so it can be inferred from the property for static analysis.

0.4.6 (2023-06-06)
------------------
* Update return types for AbstractType and some ASN1 helper methods to provider better hints for static analysis.
* Time types now accept DateTimeInterface instead of just DateTime.

0.4.5 (2021-12-11)
------------------
* Support PHP 8.0 / 8.1.
* Fix non-optional params on some objects and return type declarations.

0.4.4 (2020-10-04)
------------------
* Fix an OID encoding case when the second component would be near or over the max int as defined by PHP.

0.4.3 (2020-09-27)
------------------
* Fix the encoding / decoding of OIDs when the first / second component is more than one byte (found by @danielmarschall)

0.4.2 (2020-05-02)
------------------
* Fix the length-of-length check for a partial PDU with long definite encoding under certain circumstances. 

0.4.1 (2019-03-10)
------------------
* More performance optimizations in the encoding / decoding process for large amounts of data structures.

0.4.0 (2019-03-03)
------------------
* Performance and memory improvements.
* Removed the 'trailing data' aspect of decoded types.
* Added a 'getLastPosition' method for encoders. Returns the last position in the byte stream the decoder stopped at.
* Removed the 'constructed_only' and 'primitive_only' options for the encoder.

0.3.1 (2019-01-21)
------------------
* Additional minor performance improvements.

0.3.0 (2019-01-20)
------------------
* Improve general encoder performance with various optimizations.
* Add arbitrary precision support for tag numbers.
* Add arbitrary precision support for OID types.
* Remove the TypeFactory. Do not load classes dynamically.
* Simplify long definite length decoding.
* Simplify VLQ decoding to be a single operation.

0.2.0 (2018-09-16)
------------------
* Support arbitrary-precision for Integer/Enumerated types with the GMP extension.

0.1.2 (2018-04-15)
------------------
* Fix option handling for current set of options.

0.1.1 (2018-04-15)
------------------
* Add an options setter / getter. Merge options recursively.

0.1.0 (2018-04-14)
------------------
* Initial release.
