# Upgrading from 3.x to 4.x

1. Decorators have been moved from Objects to dictionaries. If you use any of them:

    A. Upgrade their namespace in your code:
    ```diff
    - PrinsFrank\PdfParser\Document\Object\Decorator\{Name}
    + PrinsFrank\PdfParser\Document\Dictionary\Decorator\{Name}
    ```

    B. Change object retrieval to dictionary retrieval:
    ```diff
    - $document->getObject(42, Page::class);
    
    + $document->getObject(42)
        ->getDictionary(Page::class);
    ```

2. The Namespaces of CompressedObject and UncompressedObject have been changed

```diff
- PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject
+ PrinsFrank\PdfParser\Document\Object\UncompressedObject

- PrinsFrank\PdfParser\Document\Object\Item\CompressedObject
+ PrinsFrank\PdfParser\Document\Object\CompressedObject
```

3. The `$document` and `$object` parameter have been removed in several places

To reduce the amount of passed around variables, all dictionaries now have a reference to their object, and all objects have a reference to the document. This means that you no longer have to pass the document or object instance to many methods.

4. Accessing the content of an object from a dictionary renamed from `getStream` to `getContent` to fix inconsistencies

To retrieve the contents of an object, the method `getContent` was available. But if you wanted to retrieve the contents of an object from its dictionary, the method was called `getStream`. This has been renamed to `getContent`.

# Upgrading from 2.x to 3.x

- Dropped support for PHP8.1. It already [hasn't got security updates since the start of the year](https://www.php.net/supported-versions.php), so please upgrade your PHP version if this affects you.
- All internal classes with readonly properties have [now become readonly classes](https://github.com/PrinsFrank/pdfparser/pull/339). If you extend any of these in your own code, the extending class should also be marked as readonly.
- `Dictionary::getValueForKey()` now has a new first argument: `Document $document`. This is needed to resolve any references and decrypt values in encrypted documents
- `Dictionary::getType()` and `Dictionary::getSubType()` now have a new first argument: `Document $document`.
- `ColorSpace::getComponents()` now requires an instance of `Document` as an argument
- `RasterizedImage::toPNG()` now requires an instance of `Document` as an argument
