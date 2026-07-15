# Contributing

## Starting the PHP docker container

For easier development, a bundled dockerfile can be started with:
```bash
docker compose up -d
```

It contains php-spx for easier profiling of memory/cpu usage, which can be enabled by prefixing any command in the PHP container with SPX_ENABLED=1

## Profiling specific PDFs

To profile how a specific PDF is being parsed, you can run the parser with SPX_ENABLED=1 for a specific sample from the Samples test suite. For example, to debug the file in `tests/Samples/files/gdocs-image-simple/file.pdf`, you can run the following command:
```bash
docker compose exec -e SPX_ENABLED=1 php php tests/Samples/profile.php gdocs-image-simple getText
```

If you want a more detailed profile, you can run the following command:

```bash
docker compose exec -e SPX_ENABLED=1 -e SPX_REPORT=full php php tests/Samples/profile.php gdocs-image-simple getText
```

you can then head to http://localhost, scroll down to the last run and click on it to view the full profile.

## Acquiring the specification document

Because the specification document is not freely available, it cannot be included in this repository directly. The specification document is downloadable on two different places:

1. To get the most recent version, head to https://www.pdfa-inc.org/product/iso-32000-2-pdf-2-0-bundle-sponsored-access/
2. If you are okay with a slightly older version without the need to share personal information, you can download it using this url: https://opensource.adobe.com/dc-acrobat-sdk-docs/standards/pdfstandards/pdf/PDF32000_2008.pdf

## Adding a sample to prevent regressions

In the `tests/Samples/files` directory, create a new directory with a descriptive title. If you don't know what title to use, you can use the issue number like this: `issue-1234`. Place the sample file in this directory, and make sure that the file is named `file.pdf`. To create the `content.yml` file with the expected output, run the following command:

```bash
docker compose exec php composer update-content
```

## Debugging file encryption keys

To retrieve information about passwords and file encryption keys, it's useful to debug against a working implementation. We can use qpdf for that like this:

```bash
qpdf --show-encryption --show-encryption-key --password=knownPassword file.pdf
```

## Use of comments

Code should be self-documenting, and if something can be clarified by giving a method, variable or class a clearer name or writing code more explicitly that should be done instead of adding comments.

Content in comments should not replicate content in code. There is no way to guarantee that the comment gets updated when the code gets updated.

If there are edge cases a piece of code accounts for, there should be a test with that explicit edge case instead of a comment. This way, the test case can be considered living documentation, and it has to be considered when the code under test is updated.

There are some acceptable reasons to add comments:
- Something is done in a specific way that is not intuitive. In this case, the considerations should be put in the comments.
- A unit of code can be referenced by multiple names. A comment can clarify the alternative name. 
    - For example: a parameter for `characterSpace` is named `Tc`. To clarify the name, this package uses `characterSpace` internally. A comment clarifying that this references `Tc` in the specification makes sense.

## Pull request size Guidelines

Pull requests should be **reviewable in under an hour**. This means:
- Less than 200 lines of actual code changes (excluding tests)
- Single logical change. Either one new feature or one bugfix per PR
