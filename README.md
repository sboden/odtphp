odtphp
======

### Original description

OdtPHP is a library to quickly generate Open Document Text-files that can be read by a [gigantic set][3] of Office Suites, including LibreOffice, OpenOffice and even Microsoft Office from PHP code. It uses a simple templating mechanism.
See the tests/ folder for a set of examples.

This repository already includes the changes suggested by [Vikas Mahajan][1] and a number of other bug fixes.

### Unit tests

The project uses PHPUnit for testing. The tests compare generated ODT files with gold standard files to ensure correct document generation.

#### Setup and Running Tests

1. Install dependencies:
```bash
composer install
```
2. Run the tests:
```bash
cd test/tests/src 
../../vendor/bin/phpunit .
```
Or run with debug information:
```bash
cd test/tests/src 
../../vendor/bin/phpunit . --debug
```
#### Test Structure

- `tests/src/` - Contains all test files
  - `OdtTestCase.php` - Base test class with common functionality
  - `Basic1Test.php` - Basic document generation tests
  - `Basic2Test.php` - Tests with image insertion

Each test is run twice:
- Once using PclZip library
- Once using PHP's native Zip extension

The tests generate ODT files and compare them with gold standard files located in:
- `tests/src/files/gold_phpzip/` - Gold files for PHP Zip tests
- `tests/src/files/gold_pclzip/` - Gold files for PclZip tests


### History
This project was initially started by Julien Pauli, Olivier Booklage, Vincent Brouté and published at [http://www.odtphp.com][2] (link leads to archived version of page, as it is not available any longer).

### Links:

* http://sourceforge.net/projects/odtphp/ Sourceforge Project of the initial library (stale)

[1]: http://vikasmahajan.wordpress.com/2010/12/09/odtphp-bug-solved/
[2]: https://web.archive.org/web/20120531095719/http://www.odtphp.com/index.php?i=home
[3]: https://en.wikipedia.org/wiki/OpenDocument_software#Text_documents_.28.odt.29
