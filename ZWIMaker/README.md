# ZWIMaker

This extension exports MediaWiki articles to the ZWI file format.
Currently, Mediawiki version 1.37 is fully supported. Version 1.34, 1.35 has a limited support (ZWI files will not include previous revisions of articles).

This plugins allows downloading ZWI files, as well as a direct submission to the Encyclosphere
network.

## Installation

Go to the MediaWiki install directory, and then go to the directory "extensions".

```bash
cd extensions
git clone https://gitlab.com/ks_found/ZWIMaker.git
```

You will see the directory "ZWIMaker".
Then you should make sure that the web server can write into the directory "ZWIMaker/tmp".
If you run Apache under Linux, make sure the owner and group is "www-data".

```bash
cd ZWIMaker 
chown www-data tmp
chgrp www-data tmp
chmod 755 tmp
```

## Configuration

Add these lines at the bottom of LocalSettings.php of your MediaWiki configuration file:

```php 
wfLoadExtension( 'ZWIMaker' );
# What is the name of your Wiki 
# THis corresponds to the network "Publisher" name
$wgMzwiName=$wgSitename;
# action tab to make "Export to ZWI"
$wgMzwiTab=true;
# if you set this parameter to 0 it will trigger download of ZWI file 
$wgMzwiSubmit=0;
#
# if $wgMzwiSubmit>0, you should specify the password for network submission
$wgMzwiPassword='10 character password from KSF'; 
#
# should the author (contributors) approve the submission? 
$wgMzwiAuthorApprove=true;
#
# What namespaces should be excluded from the export
# Use the syntax: Name: and | separator.
$wgMzwExcludedNamespaces = "Special:|Module:|Template:|User:|Discussion:|MediaWiki:|Help:";
#
# what about the license? This info will be included inside ZWI file. 
$wgMzwiLicense='CC BY-SA 3.0';
#
# Minimum number of words for a good article.
$wgMzwMinNumberOfWords=10;
#
# Encyclosphere submission URL 
$wgMzwiSubmitUrl="https://encycloreader.org/upload/";
```

You will see an additional button "ZWI export" above the edit area (this depends on the MediaWiki skin).

Generally, $wgMzwiName can be set to any name, but you will need to obtain the correct password from the KSF which associates it with the password. 

If you use the default password "$wgMzwiPassword=0123456789il", then you should always set $wgMzwiName="sandbox".
This means that your article will appear in the directory "sandbox" of the Encyclosphere network. 
Note it can be overwritten by any other installation with the same "sandbox" name.

Here are other parameters:

 * `$wgMzwiSubmit=0` -  ZWI file will be  downloaded (it will not be submitted to the Encyclosphere network).
 * `$wgMzwiSubmit=1` -  the ZWI file will be submitted to the Encyclosphere network. Note only the authors who contributed to the text of this article can submit it 
to the Encyclosphere. Also you need to be properly registered and login on this MediaWiki. In addition, you will need to specify the password that will allow to submit the article to the network. The password can be obtained from the KSF. 
 * `$wgMzwiSubmit=2` -  the article will be created in the directory "extensions/ZWIMaker/tmp/" without any further actions. It will be removed after new request.  

The option 2 should never be used for public MediaWiki installations.
This option is only useful for publishers who want to create ZWI files locally. One can trigger ZWI file creation using "wget". For example, this command: 

If `$wgMzwiAuthorApprove` is set to false, any user (after login) can submit an article to Encyclosphere. If this parameter is set to true, only authors (contributors) can do the submission.  
In this this option scans all revisions, make sure you do not remove older revisions of the articles.

One can set `$wgMzwMinNumberOfWords` to some integer values to disallow export of short articles. Also, `$wgMzwExcludedNamespaces` can reject exports of some namespaces.


```bash 
wget -q --post-data 'title=Encycloreader&action=mzwi' https://enhub.org/mediawiki/ -O  /dev/null
```

creates the file `Encycloreader.zwi` inside `extensions/ZWIMaker/tmp/`. The file should be copied to a local disk before calling wget for the next article.

## Image configuration

This ZWI builder works only if you configure Mediawiki such that all images are cached inside the "/thumb/". If you do not do this, only images that have been uploaded to MediaWiki will be included. Any external images from Wikimedia Commons will be ignored. In order to include external images, please add these lines to "LocalSettings.php ": 

```php
# InstantCommons allows wiki to use images from https://commons.wikimedia.org
$wgUseInstantCommons = true;
# This is importnat to build ZWI files with external images. 
$wgForeignFileRepos[] = [
        'class' => 'ForeignAPIRepo',
        'name' => 'imagescommonswiki', // Must be a distinct name
        'apibase' => 'https://commons.wikimedia.org/w/api.php',
        'hashLevels' => 2,
        'fetchDescription' => true, // Optional
        'descriptionCacheExpiry' => 8640000, // 24*100 hours, optional (values are seconds)
        'apiThumbCacheExpiry' => 8640000, // 24*100 hours, optional, but required for local thumb caching
];
# not found? Use english wikipedia
$wgForeignFileRepos[] = [
        'class' => 'ForeignAPIRepo',
        'name' => 'imagesenwiki',
        'apibase' => 'https://en.wikipedia.org/w/api.php',
        'hashLevels' => 2,
        'fetchDescription' => true,
        'descriptionCacheExpiry' => 8640000,  // 24*100 hours, optional (values are seconds)
        'apiThumbCacheExpiry' => 8640000, // 24*100 hours, optional, but required for local thumb caching
];
```

##  Tuning HTML output

In order to create clean HTML file article.html inside the ZWI file, make this change:

(1) If you are using the default (vector) skin, replace the line in inside skins/SkinMustache.php (line 75):


```php
$bodyContent = $out->getHTML() . "\n" . $printSource;
 
```

with the line:

```php
$bodyContent =  "\n<!-- == START-BODY == -->\n" . $out->getHTML() . "\n" . $printSource . "\n<!-- == END-BODY == -->\n";
```

This adds tags needed to extract clean HTML.


(2) If you are using "Timeless" skin, 
make the following modification in Timeless/includes/TimelessTemplate.php at the line close to 166: 

```php

 Html::rawElement( 'div', [ 'id' => 'bodyContent' ],
 $this->getContentSub() .
 "\n<!-- == START-BODY == -->\n" . $this->get( 'bodytext' ) . "\n<!-- == END-BODY == -->\n" .
 $this->getClear()
  )


```

This code adds tags START-BODY and END-BODY which are used to obtain a clean HTML file.


S.Chekanov 


