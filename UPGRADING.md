# Upgrading from 2.x to 3.x

- Dropped support for PHP8.1. It already [hasn't got security updates since the start of the year](https://www.php.net/supported-versions.php), so please upgrade your PHP version if this affects you.
- All internal classes with readonly properties have [now become readonly classes](https://github.com/PrinsFrank/pdfparser/pull/339). If you extend any of these in your own code, the extending class should also be marked as readonly.
