<picture>
    <source srcset="https://github.com/PrinsFrank/pdfparser/raw/main/docs/images/banner_dark.png" media="(prefers-color-scheme: dark)">
    <img src="https://github.com/PrinsFrank/pdfparser/raw/main/docs/images/banner_light.png" alt="Banner">
</picture>

# PDF Parser

[![GitHub](https://img.shields.io/github/license/prinsfrank/pdfparser)](https://github.com/PrinsFrank/pdfparser/blob/main/LICENSE)
[![PHP Version Support](https://img.shields.io/packagist/php-v/prinsfrank/pdfparser)](https://github.com/PrinsFrank/pdfparser/blob/main/composer.json)
[![codecov](https://codecov.io/gh/PrinsFrank/pdfparser/branch/main/graph/badge.svg?token=2KXO43MCIC)](https://codecov.io/gh/PrinsFrank/pdfparser)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-level%2010-brightgreen.svg?style=flat)](https://github.com/PrinsFrank/pdfparser/blob/main/phpstan.neon)
[![](https://img.shields.io/static/v1?label=Sponsor&message=%E2%9D%A4&logo=GitHub&color=%23fe8e86)](https://github.com/sponsors/PrinsFrank)

A low-memory, fast and maintainable conforming PDF Parser

## Why this library?

Previously, there wasn't a PDF library that allows parsing of PDFs that was open source, MIT licensed and under active development. The PDFParser by smalot, while having been very useful over the years isn't under active development anymore. The parser of Setasign is not MIT licensed and not open source. And several other packages rely on java/js/python dependencies being installed that are called by PHP behind the scenes, losing any type information and underlying structure.

Instead, this package allows for parsing of a wide variety of PDF files while not relying on external dependencies, all while being MIT licensed!

## Setup

To start right away, run the following command in your composer project;

```bash
composer require prinsfrank/pdfparser
```

<details>
    <summary>Installation without Composer</summary>
    <p>If you don't want to install this package using Composer, or cannot due to some constraints, you can still download the contents of the latest release and use this package directly.</p>
    <p>As you don't have Composer to handle autoloading for you, you'll need to register the custom autoloader from this project. To do so, simply add the following line at the top of your custom bootstrap script or the file you want to parse PDFs in:</p>
    <code>require 'path/to/package/directory/.al-custom.php';</code>
    <p>This needs to point to the <code>.al-custom.php</code> file in the directory that the contents of this package is in.</p>
</details>

## Opening a PDF

To open a PDF file, you'll first need to load it and retrieve a `Document` object. That can be done by either parsing a file directly, or parsing a PDF from a string variable.

### Parsing a PDF file

Parsing a PDF from a file directly is the easiest option. To do so, simply call the `parseFile` method on a `PdfParser` instance:

```php
use PrinsFrank\PdfParser;

$document = (new PdfParser())
    ->parseFile(dirname(__DIR__, 3) . '/path/to/file.pdf');
```

By default, this loads the file into memory while parsing. This greatly improves parsing speed, at the cost of a bigger memory footprint. If you want to reduce the base memory footprint and use a file handle instead, you can set the second parameter `useInMemoryStream` to `false`.

### Parsing PDF from string

It is also possible to parse a PDF from a string in a variable. To do so, pass the string as an argument for the `parseFile` method on a `PdfParser` instance.

```php
use PrinsFrank\PdfParser;

$pdfAsString = file_get_contents(dirname(__DIR__, 3) . '/path/to/file.pdf');

$document = (new PdfParser())
    ->parseString($pdfAsString);
```

If you want to decrease the average memory footprint, you can also do so here, by setting the `useFileCache` parameter to `true`. This will result in the file content being written to a temporary file and the parser using the handle to that file from then on. This will be at the cost of speed.

## The `Document`

Once you have opened a file from the filesystem with `parseFile` or from a string variable using `parseString`, you'll get back an instance of a `Document`.

While initially parsing the document, a small number of variables are populated in the `Document` instance that allow for further accessing of that document. This includes:
- The public `$stream` property: a PHP stream handle to the file in memory or on the filesystem.
- The public `$version` property: Information about the PDF version of the file.
- the public `$crossReferenceSource` property: A parsed crossReference table or stream, containing several crossReference(Sub)Sections that contain information about objects stored in the document and where to find them.
- The private `$pages` property to cache any pages that have already been retrieved. This property is only set when the pages are actually retrieved using the `getPages` method. (See below)

The document also contains several methods to retrieve specific objects from it. Those are discussed below.

If you want to quickly retrieve all text from a document, you can use the `getText` method.

## Objects in a `Document` and their decorators

A PDF is organized in objects. Not all objects are created equally. Some objects might be a Page, while others a Font. Some objects might be Generic and without a specific type. There are currently 18 specific types, and a generic object type. Some of those will be specified below.

Code specific for certain object types lives in that object types' decorator. Retrieving text for a Page makes sense, retrieving the text from a Font not so much, so the Page decorator contains the `getText` method. Below you'll find some documentation for specific object decorators.

If you want to retrieve an object by its number, you can call the `$document->getObject($objectNumber)` method. If you know that the object with that number is supposed to be of a specific type, you can supply the second argument. For example, if you want to get object 42 which you know is of type Page, you can call the method like this:

```php
$page = $document->getObject(42, Page::class);
```

If the object is not of the correct type, this will result in an exception. If you don't care about the object type, pass null as the second argument or don't supply the second argument at all.

### Decorated `InformationDictionary` objects

If a PDF has a title, producer, author, creator, creationDate or modificationDate, it is stored in an InformationDictionary.

If a PDF has an InformationDictionary, it can be retrieved using the `$document->getInformationDictionary()` method. Not All PDFs have this available, so this method might return null.

To access information from the InformationDictionary, there are several methods available:

```php
/** @var \PrinsFrank\PdfParser\Document\Document $document */
$title = $document->getInformationDictionary()?->getTitle(); 
$producer = $document->getInformationDictionary()?->getProducer(); 
$author = $document->getInformationDictionary()?->getAuthor(); 
$creator = $document->getInformationDictionary()?->getCreator(); 
$creationDate = $document->getInformationDictionary()?->getCreationDate(); 
$modificationDate = $document->getInformationDictionary()?->getModificationDate(); 
```

If you want to access non-standard data from the information dictionary, you can also retrieve the entire dictionary from the object:

```php
/** @var \PrinsFrank\PdfParser\Document\Document $document */
$dictionary = $document->getInformationDictionary()?->getDictionary();
```

### Decorated `Page` objects

Page objects can be retrieved from a document by calling the `$document->getPage($pageNumber)` method for a single page, or `$document->getPages()` for all pages. Note that `$pageNumber` is zero-indexed, so even if different format page numbers are displayed at the bottom of a page, the first page in a document is still page 0, etc.

Once you have a `Page` object, there are several methods available to retrieve information from that page. The main method of interest here is the `$page->getText()` method. To retrieve all text from all pages, you could do something like this:

```php
use PrinsFrank\PdfParser\PdfParser;

$document = (new PdfParser())->parseFile('/path/to/file.pdf');

foreach ($document->getPages() as $index => $page) {
    echo 'Text on page ' . $index . ' : ' . $page->getText();
}
```

There is also a `getText` method available on the Document to retrieve all text at once without even having to retrieve pages.

There are also methods available to get the underlying textObjectCollection using `$page->getTextObjectCollection()`, the resource dictionary for a page using `$page->getResourceDictionary()` and the font dictionary using `$page->getFontDictionary()`.
