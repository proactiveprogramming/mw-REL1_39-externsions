Property Chains Helper Extension

        Version 0.1.1
        Marco Falda

This is free software licenced under the GNU General Public
Licence. Please see http://www.gnu.org/copyleft/gpl.html
for further details, including the full text and terms of
the licence.

# Overview

Property Chains Helper is an extension to MediaWiki
to help completing property chains in queries involving
multiple categories.

For more information, see the extension homepage at:
https://www.mediawiki.org/wiki/Extension:PropChainsHelper

# Requirements

This version of the Property Chains Helper extension requires
MediaWiki 1.34 or higher.

# Installation

To install the extension, place the entire 'PropChainsHelper'
directory within your MediaWiki 'extensions' directory, then
add the following line to your 'LocalSettings.php' file:

     wfLoadExtension( 'PropChainsHelper' );

# Configuration

Populate the global variables ``$pchCatLevels``, ``$pchPropLevels``, 
``$pchLinkProps``, and ``$pchDomains``.

``$pchCatLevels`` is an associative array with the level of each category, 
for example:

``
$pchCatLevels = [
     'Patients' => 0,
     'Positives' => 1,
     'Samplings' => 1
];
``

``$pchPropLevels`` is an associative array with the chain number and their
level, for example if there are two chains Patients -> Positives and
Patients -> Samplings and the properties are

- Patient(Age_of_patient, Gender_of_patient)
- Positives(Contact_of_positive, Type_of_contact)
- Samplings(Date_of_sampling, Result_of_sampling)

The variable will be written as:

```php
$pchPropLevels = [
     "Age_of_patient" => [0, 0],
     "Gender_of_patient" => [0, 0],
     "Contact_of_positive" => [0, 1],
     "Type_of_contact" => [0, 1],
     "Date_of_sampling" => [1, 1],
     "Result_of_sampling" => [1, 1],
];
```

``$pchLinkProps`` is an associative array with the link properties
for each chain, for example in the previous case both the Positives
and Samplings chains are linked with the property 'Has Patient':

```php
$pchLinkProps = [
    ['Has Patient'],
    ['Has Patient']
];
```

``$pchDomains`` is useful for helping with categorical domains, for example:

```php
 $pchDomains = [
     "Gender_of_patient" => ["M", "F"]
 ];
```