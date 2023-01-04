# RecentActivity

Adds parser functions for listing recently created and edited articles

This fork is created to contribute with a modernized version of the extension RecentActivity that solves https://gitlab.com/organicdesign/extensions/issues/41 and other issues.

# Installation

Easiest way to install the extension is using _Composer_: it will automatically resolve all the dependencies and install them as well.
Alternatively, it is possible to manually donwload the package and decompress it.
Choose one of the two and proceed according the following instructions.

## Installation via _Composer_

Add the `require` configuration as in the following example to the `composer.local.json` at the root of your mediawiki installation, or create the file if it does not exist yet:

```JSON
{
    "require": {
        "lucamauri/recentactivity": "~1.0"
    },
    "extra": {
        "merge-plugin": {
            "include": [
            ]
        }
    },
    "config": {
    }
}
```

and, in a command prompt, run Composer in the root of your mediawiki installation:

```
composer install --no-dev
```

## Manual download

Download the source code with the link in the extension's main page https://gitlab.com/lucamauri/RecentActivity then uncompress it a folder named `RecentActivity` in the `extensions` directory of the MediaWiki installation.

## Activate the extension

Add the following code near the rest of the extensions loading in the site's `LocalSettings.php`:

```PHP
wfLoadExtension('RecentActivity');
```

Below this line, add the configuration parameters as explained below in _Configuration_ section.

# Usage

## How to debug

`RecentActivity` group

# Why the fork

# Credits

## Icon

[The project icon](https://commons.wikimedia.org/wiki/File:Breezeicons-actions-22-view-calendar-upcoming-days.svg) © 2014 Andreas Kainz & Uri Herrera & Andrew Lake & Marco Martin & Harald Sitter & Jonathan Riddell & Ken Vermette & Aleix Pol & David Faure & Albert Vaca & Luca Beltrame & Gleb Popov & Nuno Pinheiro & Alex Richardson & Jan Grulich & Bernhard Landauer & Heiko Becker & Volker Krause & David Rosca & Phil Schaf / KDE
