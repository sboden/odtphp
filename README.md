
# ODTPHP

A PHP library for creating and manipulating ODT (OpenDocument Text) files.

## Requirements

- PHP 8.1 or higher
- PHP ZIP Extension or PclZip library. PHP ZIP Extension is recommended as we will probably
  remove the PclZip library sometime in the future.

## Installation

Install via Composer:

```bash
composer require sboden/odtphp:^3
```

## Basic Usage

### Simple Variable Substitution

```php
<?php
use Odtphp\Odf;

// Create a new ODT document from a template
$odt = new Odf("template.odt");

// Set simple text variables
$odt->setVars('company_name', 'ACME Corporation');
$odt->setVars('current_date', date('Y-m-d'));

// Save the modified document
$odt->saveToDisk('output.odt');
```

### Advanced Usage with Images and Segments

```php
<?php
use Odtphp\Odf;

// Create ODT document with custom configuration
$config = [
    'DELIMITER_LEFT' => '{',
    'DELIMITER_RIGHT' => '}',
];
$odt = new Odf("invoice_template.odt", $config);

// Insert a company logo
$odt->setImage('company_logo', 'path/to/logo.png');

// Create a repeatable segment for line items
$lineItems = $odt->setSegment('invoice_items');
foreach ($invoiceData['items'] as $item) {
    $lineItems->setVars('item_name', $item->name);
    $lineItems->setVars('item_price', number_format($item->price, 2));
    $lineItems->setVars('item_quantity', $item->quantity);
    $lineItems->merge();
}

// Set summary variables
$odt->setVars('total_amount', number_format($invoiceData['total'], 2));
$odt->setVars('invoice_number', $invoiceData['number']);

// Save the completed invoice
$odt->saveToDisk('invoice.odt');
```

### Error Handling

```php
<?php
use Odtphp\Odf;
use Odtphp\Exceptions\OdfException;

try {
    $odt = new Odf("template.odt");
    
    // This will throw an exception if the variable doesn't exist
    $odt->setVars('non_existent_variable', 'Some Value');
} catch (OdfException $e) {
    // Handle template-related errors
    error_log("ODT Processing Error: " . $e->getMessage());
}
```

### Testing

1. Install dependencies:
```
composer install
```

2. Run the tests:

On Linux:
```bash
# Go to the root of odtphp, then:
./run-tests.sh
```

On Windows:
```bash
# Go to the root of odtphp, then:
run-tests.bat

# Note that depending on your PHP installation you may have to edit the
# script to include the path to php.exe
```

You can also run the PHPUnit tests e.g. in PHPStorm, but you have to exclude 
the vendor directory to avoid warnings about the PHPUnit framework itself.


#### Test Structure

The test suite covers various aspects of the ODT templating functionality:

1. **Basic Tests** (`Basic1Test.php` and `Basic2Test.php`):
   - Verify basic variable substitution in ODT templates
   - Test both PhpZip and PclZip ZIP handling methods
   - Demonstrate simple text replacement and encoding

2. **Configuration Tests** (`ConfigTest.php`):
   - Validate configuration handling
   - Test custom delimiters
   - Check error handling for invalid configurations

3. **Edge Case Tests** (`EdgeCaseTest.php`):
   - Handle complex scenarios like:
     * Large variable substitution
     * Nested segment merging
     * Special character encoding
     * Advanced image insertion
     * Invalid template handling

4. **Image Tests** (`ImageTest.php`):
   - Verify image insertion functionality
   - Test image resizing
   - Check error handling for invalid image paths

5. **Variable Tests** (`VariableTest.php`):
   - Test variable existence checks
   - Verify special character handling
   - Check multiline text substitution

Each test is designed to ensure the robustness and reliability of the ODT 
templating library across different use cases and configurations.

Each test is run twice:
- Once using PclZip library
- Once using PHP's native Zip extension

The tests generate ODT files and compare them with gold standard files located in:
- `tests/src/files/gold_phpzip/` - Gold files for PHP Zip tests
- `tests/src/files/gold_pclzip/` - Gold files for PclZip tests


### Linting

On Linux:
```bash
# Go to the root of odtphp, then:
composer install
vendor/bin/phpcs --standard="Drupal,DrupalPractice" -n --extensions="php,module,inc,install,test,profile,theme" src
```


## Features

- Template variable substitution
- Image insertion and manipulation
- Segment management for repeatable content
- Support for custom delimiters
- Type-safe implementation
- Modern PHP 8.1+ features

## Configuration

```php
$config = [
    'ZIP_PROXY' => \Odtphp\Zip\PhpZipProxy::class, // or PclZipProxy
    'DELIMITER_LEFT' => '{',
    'DELIMITER_RIGHT' => '}',
    'PATH_TO_TMP' => '/tmp'
];

$odt = new Odf("template.odt", $config);
```

## Type Safety

This library is built with PHP 8.1's type system in mind:
- Strict typing enabled
- Property type declarations
- Return type declarations
- Parameter type hints
- Improved error handling

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-3.0 License - see the LICENSE file for details.

The GPL-3.0 license is because of what the cybermonde/odtphp author put it 
to, I (sboden) don't want to change it. I don't consider odtphp to be "my" software, I 
only tried to make the cybermonde/odtphp fork work properly in PHP v8.3 
and beyond. 

Personally (sboden) I would have used an MIT or BSD license for odtphp, but I 
will happily abide with the original author's intent.

The original version/source code of odtphp is at [https://sourceforge.
net/projects/odtphp/](https://sourceforge.net/projects/odtphp/). The 
cybermonde/odtphp fork I started from is at [https://github.
com/cybermonde/odtphp](https://github.com/cybermonde/odtphp).


### Usage justification
How and why we use odtphp in our projects.

Some of our projects send out letters (either PDF or on physical paper) to people: 
the idea is to have a nice template, fill in some data, create a PDF file from the
filled-in template, and send the resulting PDF to the recipient. 

The template is an ODT file, the data is stored in a database. We use odtphp to fill 
in the template. The filled-in ODT file is then transformed into a PDF file using 
(originally) jodconverter, and in newer projects using gotenberg.dev (which is 
essentially jodconverter using golang). The PDF converters run in a different 
container than the main applications.

The reason for using gotenberg over jodconverter is that gotenberg is better
supported. And jodconverter was initially not able to handle large volumes since
the most commonly used Docker container did not clean up after itself, so I had to 
build this in myself.

Why not generate PDFs directly from PHP? We tried, but it's difficult to 
have nice-looking PDFs using the existing PHP libraries. One PHP library has 
problems with images, another PHP PDF library faces issues with margins and 
headers/footers, ... We tried a lot of different setups and finally settled 
for odtphp/gotenberg to have the PDF output "pixel perfect". Another 
limitation was that we needed about 50,000 PDFs per day for which we run 
parallel queues to the PDF converters. 


### Technical tidbits

#### Why do my variables sometimes not get filled in?

Because LibreOffice adds metadata to what you change. E.g. when you have 
something as follows in your ODT file:

```
{variable_text}
```

And you make a small change to the previous text it may very well end up as:

```
{variable<text:span text:style-name="T4">s</text:span>_text}]
```
where you don't see the "text:span" part visually, but it is there. And since 
odtphp plainly matches variables in an ODT file, the variable name will not match 
anymore and will not be replaced.

How to get around that: When you change variable texts, select the variable 
text (including the begin and end delimiters), copy it, and then use "Paste 
special/Paste unformatted text" to copy the text in the same place without any
invisible metadata.


#### Why does "Catégorie 1" sometimes appear as "CatÃ©gorie 1" in the output?

Usually this is a double-encoding problem. When UTF-8 text is encoded again 
as UTF-8, special characters like 'é' can appear as 'Ã©'.  This typically  
happens when the text is already in UTF-8 but gets encoded again.

While setting variables you can specify the encoding as needed, e.g. as:

```php
$categorie->setVars('TitreCategorie', 'Catégorie 1', true, 'UTF-8');
```


#### The default charset has changed?

One of the "major" changes I made is to put UTF-8 as the default charset when 
setting variables. Before the default was ISO-8859-1, but UTF-8 currently 
makes sense to me.

I always recommend using UTF-8 internally within the application and 
handling encoding/decoding at the boundaries.

#### How do I upgrade from v1/v2 to v3?

While it is a breaking change, as long as you only use `odtphp` and don't use
any of its internals you should be fine by just upgrading the odtphp library 
to v3.

If you inherit from Odf or you use some internal things in the odtphp library,
then all bets are off.


### History

The odtphp project was initially started by Julien Pauli, Olivier Booklage, 
Vincent Brouté and published at http://www.odtphp.com (the website no longer 
exists).

As DXC Technology working for the Flemish government we started using 
the `cybermonde/odtphp` fork ([source](https://github.com/cybermonde/odtphp)) 
in a couple of projects to fill in template ODT files with data. 
We ran into a couple of problems with the `cybermonde/odtphp` library for 
which it was easier to just create my own fork, hence `sboden/odtphp`: we 
sometimes generate 50,000 forms daily, and `cybermonde/odtphp` would 
occasionally overwrite outputs when processing a lot of ODT files 
simultaneously because of non-random random numbers.

Why do I try to keep this "corpse" alive? Simply because I found no 
replacement for it. The projects I work that use odtphp are now (= end 2024) on 
PHP 8.2 moving to PHP 8.3, and some pieces of the original odtphp library 
are starting to spew warnings. During my 2024 Christmas holidays I was 
writing some unit test cases for odtphp, and while testing the AI tool 
"Windsurf", I tried to have Windsurf automatically update odtphp to a newer 
PHP version, and sboden/odtphp v3 is the result of that (after some extra 
manual human changes).

While this fork `sboden/odtphp` is not officially supported, maintenance 
and bug fixes are provided on a best-effort basis. The `sboden/odtphp` library 
is actively used in production applications with planned lifecycles extending 
to 2030.

This software is **not** by DXC Technology, else it would have been called 
`dxc/odtphp`. This software is **not** by the Flemish government, else it 
would probably have been called `vo/odtphp`. I have always worked on odtphp 
during my personal time.

### Version History
- v3.0.3 - 29Dec2024: odtphp version for PHP 8.x 
- v2.2.1 - 07Dec2022: Parallel processing by odtphp does not overwrite outputs.

### Disclaimer

This software is provided "as is" without warranty of any kind, either 
expressed or implied. The entire risk as to the quality and performance of 
the software is with you.

In no event will the authors, contributors, or licensors be liable for any
damages, including but not limited to, direct, indirect, special, incidental,
or consequential damages arising out of the use or inability to use this 
software, even if advised of the possibility of such damage.

By using this software, you acknowledge that you have read this disclaimer, 
understand it, and agree to be bound by its terms.


### Links:

* http://sourceforge.net/projects/odtphp/ Sourceforge Project of the initial library (stale)

[1]: http://vikasmahajan.wordpress.com/2010/12/09/odtphp-bug-solved/
[2]: https://web.archive.org/web/20120531095719/http://www.odtphp.com/index.php?i=home
[3]: https://en.wikipedia.org/wiki/OpenDocument_software#Text_documents_.28.odt.29
