# Readability Addon for FreshRSS

This extension uses Readability to fetch the article content for selected feeds. 

Readability is from [fivefilters](https://github.com/fivefilters/readability.php)

## Installation

Clone this repository in the "extensions" folder of FreshRSS.

Or download the latest release and unzip in the "extensions" folder of FreshRSS.

## Usage

Go in the plugin configuration, select the feeds that need Readability. 

## Development

It's all in the `extension.php` file and the `configure.phtml` file.

To update the Readability library, launch `composer update fivefilters/readability.php`.

## Notes

This plugin is heavily based on [Readable](https://github.com/printfuck/xExtension-Readable), it was my starting point for the developement, so thanks _printfuck_.
