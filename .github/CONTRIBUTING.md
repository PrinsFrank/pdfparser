# Contributing

## Acquiring the specification document

Because the specification document is not freely available, it cannot be included in this repository directly. The specification document is downloadable on two different places:

1. To get the most recent version, head to https://www.pdfa-inc.org/product/iso-32000-2-pdf-2-0-bundle-sponsored-access/
2. If you are okay with a slightly older version without the need to share personal information, you can download it using this url: https://opensource.adobe.com/dc-acrobat-sdk-docs/standards/pdfstandards/pdf/PDF32000_2008.pdf

## Adding a sample to prevent regressions

In the `tests/Samples/files` directory, create a new directory with a descriptive title. If you don't know what title to use, you can use the issue number like this: `issue-1234`. Place the sample file in this directory, and make sure that the file is named `file.pdf`. To create the `content.yml` file with the expected output, run the following command:

```bash
composer update-content
```
