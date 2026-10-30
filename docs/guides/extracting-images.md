# Extracting images

To extract all images in a document and store them on your machine, you could do something like this:

```php
/** @var \PrinsFrank\PdfParser\Document\Document $document */
foreach ($document->getImages() as $index => $image) {
    if (($imageFileExtension = $image->getImageType()?->getFileExtension()) === null) {
        continue; // You could still save the file with a default file extension like 'jpg', but it is not clear what kind of image this is.
    }

    file_put_contents(sprintf('%s/image_%d.%s', __DIR__, $index, $imageFileExtension), $image->->getStream()->toString());
}
```
