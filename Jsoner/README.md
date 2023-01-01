# TL;DR (MW 1.25+)

Install `curl`, `fileinfo`, `intl` and `mbstring` for PHP. Put this in your `composer.local.json`:

    {
        "require": {
            "noris/jsoner": "~1.0"
        }
    }
    
and run `composer update`. Then, append this to your LocalSettings.php:

    wfLoadExtension( 'Jsoner' );
    $jsonerBaseUrl = 'https://example.com/api/';
    $jsonerUser = '<your_user>';
    $jsonerPass = '<your_pass>';

# Jsoner

This is a MediaWiki extension that allows one to embed external JSON data (i.e. from
a REST API) into an article.

## Requirements

This extension requires at least `PHP >= 5.6` and the following PHP extensions:

* curl
* fileinfo
* intl
* mbstring

Using Debian / Ubuntu you can install the extensions like this:

    sudo apt-get install php5-curl php5-intl
    sudo service apache2 restart

To test if they are enabled (use your php.ini):

    $ php5 --php-ini /etc/php5/apache2/php.ini -m | grep -E 'fileinfo|mbstring|intl|curl'
    curl
    fileinfo
    intl
    mbstring

## Installation

### Download (recommended, with Composer)

Put this to your `composer.local.json`:

    {
        "require": {
            "noris/jsoner": "~1.0"
        }
    }
    
and run `composer update` (or `composer install` if you don't have a composer.lock yet). 

### Download (not recommended, manually)

Download the extension and put it in your `extension/` folder.

### Add to MediaWiki

To enable this extension, add this to your `LocalSettings.php`:

    wfLoadExtension( 'Jsoner' );

This will enable the Jsoner extension and add the following functions to the MediaWiki parser:

* `#jsoner` with parameters `url` and filters, [see below](#available-filters).

## Configuration

The extension has multiple settings. Please put them after the `wfLoadExtension( 'Jsoner' );`. 

### $jsonerBaseUrl (default = null)

    $jsonerBaseUrl = 'https://example.com/api/';

This can be used to prefix all `#jsoner` calls (the `url` argument specifically) with this url
so that you don't have to repeat yourself, if you only consume data from one domain. If omitted,
you have to provide complete domains in `url`.

### $jsonerUser / $jsonerPass (default = null)

    $jsonerUser = '<your_user>';
    $jsonerPass = '<your_pass>';

If both are set, this is passed to cURL to authenticate. If omitted, cURL tries unauthenticated.

## Usage

Jsoner has a pipes and filters architecture. First, data is fetched, then filters are applied and
finally, the data is transformed in a representation.

    Fetch → [Filter ...] → Transformer
    
This looks like this in MediaWiki syntax:

    // Fetch         → Filter              → Filter                  → Transformer
    {{ #jsoner:url=… | f-SelectSubtree=foo | f-SelectKeys=name,email | t-JsonDump }}

Lets run something interesting:

    {{ #jsoner:url=http://pokeapi.co/api/v2/pokemon/1/ | f-SelectSubtree=stats | t-JsonDump }}
    
    ↓
    
    [
        {
            "base_stat": 45,
            "effort": 0,
            "stat": {
                "name": "speed",
                "url": "http://pokeapi.co/api/v2/stat/6/"
            }
        },
        {
            "base_stat": 65,
            "effort": 0,
            "stat": {
                "name": "special-defense",
                "url": "http://pokeapi.co/api/v2/stat/5/"
            }
        },
        {
            "base_stat": 65,
            "effort": 1,
            "stat": {
                "name": "special-attack",
                "url": "http://pokeapi.co/api/v2/stat/4/"
            }
        },
        {
            "base_stat": 49,
            "effort": 0,
            "stat": {
                "name": "defense",
                "url": "http://pokeapi.co/api/v2/stat/3/"
            }
        },
        {
            "base_stat": 49,
            "effort": 0,
            "stat": {
                "name": "attack",
                "url": "http://pokeapi.co/api/v2/stat/2/"
            }
        },
        {
            "base_stat": 45,
            "effort": 0,
            "stat": {
                "name": "hp",
                "url": "http://pokeapi.co/api/v2/stat/1/"
            }
        }
    ]

As you can see, Filters are prefixed with `f-` and Transformers are prefixed with `t-`.

## Available Filters

A typical call looks like this

    {{ #jsoner:url=… | f-SelectSubtree=foo | }}

### CensorKeysFilter (`f-CensorKeys`)

Runs on a list and returns a list. Usage: [`f-CensorKeys=key(,key)*,replacement`](http://regexr.com/3d0vn)

Example: `f-CensorKeys=email,--protected--`

    [
      {
        "name": "Bob",
        "email": "bob@example.com"
      },
      {
        "name": "Tom",
        "email": "tom@example.com"
      }
    ]   
     
    ↓
        
    [
      {
        "name": "Bob",
        "email": "--protected--"
      },
      {
        "name": "Tom",
        "email": "--protected--"
      }
    ]

### ReduceKeysFilter (`f-Reduce`)

Runs on a list and returns a list. Usage: [`f-Reduce=(\w+),(\w+)(\.\w+)*`](http://regexr.com/3d5kp)

Example: `f-Reduce=mail,data.email`

    [
      {
        "id": "1",
        "data": {
          "email": "bob@example.com",
          "city": "Berlin"
        }
      },
      {
        "id": 2,
        "data": {
          "email": "tom@example.com",
          "city": "Hamburg"
        }
      }
    ]

    ↓
    
    [
      {
        "id": "1",
        "data": {
          "email": "bob@example.com",
          "city": "Berlin"
        },
        "mail": "bob@example.com"
      },
      {
        "id": 2,
        "data": {
          "email": "tom@example.com",
          "city": "Hamburg"
        },
        "mail": "tom@example.com"
      }
    ]

### RemoveKeysFilter (`f-RemoveKeys`)

Runs on a list and returns a list. Usage: [`f-RemoveKeys=key(,key)*`](http://regexr.com/3d0vt)

Example: `f-RemoveKeys=email`

    [
      {
        "name": "Bob",
        "email": "bob@example.com"
      },
      {
        "name": "Tom",
        "email": "tom@example.com"
      }
    ]
     
    ↓
        
    [
      {
        "name": "Bob"
      },
      {
        "name": "Tom"
      }
    ]

### SelectKeysFilter (`f-SelectKeys`)

Runs on a list and returns a list. Usage: [`f-SelectKeys=key(,key)*`](http://regexr.com/3d100)

Example: `f-SelectKeys=email`

    [
      {
        "name": "Bob",
        "email": "bob@example.com"
      },
      {
        "name": "Tom",
        "email": "tom@example.com"
      }
    ]

    ↓

    [
      {
        "email": "bob@example.com"
      },
      {
        "email": "tom@example.com"
      }
    ]

### SelectSubtreeFilter (`f-SelectSubtree`)

Runs on an object and returns a list. Usage: [`f-SelectSubtree=key`](http://regexr.com/3d106)

Example: `f-SelectSubtree=records`

    {
      "recordCount": 2,
      "records": [
        {
          "name": "Bob",
          "email": "bob@example.com"
        },
        {
          "name": "Tom",
          "email": "tom@example.com"
        }
      ]
    }

    ↓

    [
      {
        "name": "Bob",
        "email": "bob@example.com"
      },
      {
        "name": "Tom",
        "email": "tom@example.com"
      }
    ]

### SelectRecordFilter (`f-SelectRecord`)

Runs on a list and returns a list. Usage: [`f-SelectRecord=key:value`]

Example: `f-SelectRecord=email:test2@example.com`

    [
	  {
	    "name": "Bob",
	    "email": "test1@example.com"
	  },
	  {
	    "name": "Tom",
	    "email": "test2@example.com"
	  }
	]

    ↓

    [
      {
		"name": "Tom",
		"email": "test2@example.com"
	  }
    ]

## Available Transformers

There must be always a transformer at the end of the pipeline.

### InlineListTransformer (`t-InlineList`)

Creates a comma-separated list of values from a list.

Usage: `t-InlineList=key`

With a list as input, calling `t-InlineList=email`

    [
      {
        "name": "Bob",
        "email": "bob@example.com"
      },
      {
        "name": "Tom",
        "email": "tom@example.com"
      }
    ]
    
    ↓
    
    bob@example.com, tom@example.com
    
Good for, you guessed it: lists!

### JsonDumpTransformer (`t-JsonDump`)
Dumps the JSON data into a `<pre>` tag. Nice for debugging.

### SingleElementTransformer (`t-SingleElement`)

Returns a single JSON value out of an object or a list. If the input is a list,
the SingleElementTransformer will use the first element in the list to display something.

Usage: `t-SingleElement=key`

With a list as input, calling `t-SingleElement=name`

    [
      {
        "name": "Bob",
        "email": "bob@example.com"
      },
      {
        "name": "Tom",
        "email": "tom@example.com"
      }
    ]
    
    ↓
    
    Bob
    
With an object as input, calling `t-SingleElement=name`

    {
        "name": "Bob",
        "email": "bob@example.com"
    }
    
    ↓
    
    Bob
    
Nice for single values like IDs.

### StackedElementTransformer (`t-StackedElement`)
Creates a `<br />` separated (on top of each other) stack out of an object or a list. If the input
is a list, the StackedElementTransformer uses the first element in the list and displays that.

With a list as input:

    [
      {
        "name": "Bob",
        "email": "bob@example.com"
      },
      {
        "name": "Tom",
        "email": "tom@example.com"
      }
    ]
    
    ↓
    
    Bob
    bob@example.com
    
With an object as input:

    {
        "name": "Tom",
        "email": "tom@example.com"
    }

    ↓
    
    Tom
    tom@example.com

Useful for address data.

### WikitextTableTransformer (`t-WikitextTable`)
Creates a nice and sortable Wikitext table out of a list of objects.

    [
      {
        "name": "Bob",
        "email": "bob@example.com"
      },
      {
        "name": "Tom",
        "email": "tom@example.com"
      }
    ]
    
    ↓
    
    ╔════════╦═════════════════╗
    ║ name ▼ ║ email         ▼ ║
    ╠════════╬═════════════════╣
    ║ Bob    ║ bob@example.com ║
    ║ Tom    ║ tom@example.com ║
    ╚════════╩═════════════════╝

### MediaWikiTemplateTransformer (`t-mwTemplate`)
Creates Wikitext depending on the given template.
You probably have to create a suiting template for the query.
Uses key=value pairs.

Wiki-String: {{ template |key=value  }}

Usage: `t-mwTemplate=template`

With `t-mwTemplate=jsoner-template`



    [
      {
        "name": "Bob",
        "email": "bob@example.com"
        "username": "bobexample"
      },
      {
        "name": "Tom",
        "email": "tom@example.com"
        "username": "tomexample"
      }
    ]

    ↓

    ╔════════╦═════════════════╦══════════════╗
    ║ name ▼ ║ email         ▼ ║username    ▼ ║
    ╠════════╬═════════════════╣══════════════╣
    ║ Bob    ║ bob@example.com ║ bobexample   ║
    ║ Tom    ║ tom@example.com ║ tomexample   ║
    ╚════════╩═════════════════╩══════════════╝

The output is depending on the template you use.

### MediaWikiTemplateTransformerAnonymous (`t-mwTemplateAnonymous`)
Creates Wikitext depending on the given template.
You probably have to create a suiting template for the query.
Doesn't use key=value pairs, uses the Anonymous templating in the Mediawiki.
Template in this use case :
  template= {{{1}}} {{{2}}}

Usage: `t-mwTemplateAnonymous=template`

    [
      {
        "name": "Bob",
        "email": "bob@example.com"
        "username": "bobexample"
      },
      {
        "name": "Tom",
        "email": "tom@example.com"
        "username": "tomexample"
      }
    ]

    ↓

    Bob bob@example.com Tom tom@example.com 

The output is depending on the template you use.

## Limitations

* If you set `$jsonerUser` and `$jsonerPass`, the authentification is used for every request. There
  is currently no per-domain or per-request level setting for username and password (and maybe
  rightfully so). One possibility would be to put a separate call, like `{{ #jsoner-unauth:url=… }}`
  or something like that.

## Development

This extension is under development. Anything may change.

You can clone is using

    git clone git@github.com:noris-network/Jsoner.git && cd Jsoner
    make devenv

To install it into your development MediaWiki, just symlink it to your `extensions` folder

    # Assuming you are in Jsoner folder
    cd /path/to/your/extensions/folder
    ln -s /path/to/the/Jsoner/extension Jsoner

Then, install it [like described above](#installation).

To see what you can do run one of

    make
    make help

To test, you can run

    make test
    
To fix warnings etc. from `make test`, you can run:

    make fix
    
To clean, you can run
    
    make clean

## License
GPL v3
