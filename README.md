<h1 align="center">mastir/php-dump</h1>

<p align="center">
    <strong>Tool to create small dump of your php application</strong>
</p>
<!--
TODO: Make sure the following URLs are correct and working for your project.
      Then, remove these comments to display the badges, giving users a quick
      overview of your package.

<p align="center">
    <a href="https://github.com/mastir/php-dump"><img src="https://img.shields.io/badge/source-mastir/php--dump-blue.svg?style=flat-square" alt="Source Code"></a>
    <a href="https://packagist.org/packages/mastir/php-dump"><img src="https://img.shields.io/packagist/v/mastir/php-dump.svg?style=flat-square&label=release" alt="Download Package"></a>
    <a href="https://php.net"><img src="https://img.shields.io/packagist/php-v/mastir/php-dump.svg?style=flat-square&colorB=%238892BF" alt="PHP Programming Language"></a>
    <a href="https://github.com/mastir/php-dump/blob/main/LICENSE"><img src="https://img.shields.io/packagist/l/mastir/php-dump.svg?style=flat-square&colorB=darkcyan" alt="Read License"></a>
    <a href="https://github.com/mastir/php-dump/actions/workflows/continuous-integration.yml"><img src="https://img.shields.io/github/actions/workflow/status/mastir/php-dump/continuous-integration.yml?branch=main&style=flat-square&logo=github" alt="Build Status"></a>
    <a href="https://codecov.io/gh/mastir/php-dump"><img src="https://img.shields.io/codecov/c/gh/mastir/php-dump?label=codecov&logo=codecov&style=flat-square" alt="Codecov Code Coverage"></a>
    <a href="https://shepherd.dev/github/mastir/php-dump"><img src="https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fshepherd.dev%2Fgithub%2Fmastir%2Fphp-dump%2Fcoverage" alt="Psalm Type Coverage"></a>
</p>
-->


<h3>Use cases</h3>
<ul>
    <li>Visualise application exceptions</li>
    <li>Save exception dump for later processing</li>
    <li>Attach dump in bug tracking system</li>
    <li>Dump request data to reproduce bugs</li>
    <li>Review and compare dumps</li>
</ul>

<h3>Basic usage</h3>
<ol>
    <li>Create dump (binary string) using PhpDumpBuilder </li>
    <li>Save/transfer dump (optional)</li>
    <li>Read dump using php-dump.js</li>
    <li>Render dump using react components in php-dump.jsx</li>
</ol>


See example in [public/index.php](public/index.php)

![](public/php-dump.gif)

## About

<!--
TODO: Use this space to provide more details about your package. Try to be
      concise. This is the introduction to your package. Let others know what
      your package does and how it can help them build applications.
-->


This project adheres to a [code of conduct](CODE_OF_CONDUCT.md).
By participating in this project and its community, you are expected to
uphold this code.


## Installation

Install this package as a dependency using [Composer](https://getcomposer.org).

``` bash
composer require mastir/php-dump
```

<!--
## Usage

Provide a brief description or short example of how to use this library.
If you need to provide more detailed examples, use the `docs/` directory
and provide a link here to the documentation.

``` php
use Mastir\PhpDump\Example;

$example = new Example();
echo $example->greet('fellow human');
```
-->


## Contributing

Contributions are welcome! To contribute, please familiarize yourself with
[CONTRIBUTING.md](CONTRIBUTING.md).

## Coordinated Disclosure

Keeping user information safe and secure is a top priority, and we welcome the
contribution of external security researchers. If you believe you've found a
security issue in software that is maintained in this repository, please read
[SECURITY.md](SECURITY.md) for instructions on submitting a vulnerability report.






## Copyright and License

mastir/php-dump is copyright © [Yevhen](mailto:themastir@gmail.com)
and licensed for use under the terms of the
MIT License (MIT). Please see [LICENSE](LICENSE) for more information.


