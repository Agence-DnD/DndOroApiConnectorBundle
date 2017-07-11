Agence Dn'D - Oro API connector
====================================

Dn'D Oro Api Connector for Oro Commerce >= 1.0

This connector between OroCommerce REST API and Alexa Amazon Echo Dot

Requirements
-------------

| DndOroApiConnectorBundle        | OroCommerce ,Community Edition |
|:-------------------------------:|:------------------------------:|
| v1.0.*                          | v1.*                           |


Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ cd /my/orocommerce/installation/dir
$ curl -sS https://getcomposer.org/installer | php
```

Then, install DndOroApiConnectorBundle with composer:

```console
composer require agencednd/oro-api-connector-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.


Step 2: Enable the Bundle
-------------------------

The bundle is automatically enable.


Step 3: ReGenerate the API doc
------------------------------

The bundle expose a controller as API, so you have to regenerate the API doc

```console
php app/console oro:api:doc:cache:clear -e prod
```

Step 4: Copy the script folder into web folder
---------------------------------------

The Amazon Function needs to get a wsse header to be authenticated on the Oro's API. The WSSE header is generated from a script in your OroCommerce application.
To access of this script, you need to copy the files from vendor/ to web/ with this command line.

```console
cp -R vendor/agencednd/alexa-connector-bundle/Resources/public/scripts/ web/scripts
```

In web/scripts/generat-wsse-header.php, you have to put your API key : oroUrl/admin/user/profile/view generate key

Step 5: Protect the generate-wsse-header.php script
---------------------------------------------------

- Modify the .htpasswd in ./web/scripts with [this](https://www.web2generators.com/apache-tools/htpasswd-generator)


```apache
#genenerate your user and passwd
'user:passwd'
```
- Modify the .htaccess with the good path to .htpasswd

From the "scripts" folder in console

```console
echo $PWD
```

Copy the path prompted

Then modify the .htaccess AuthUserFile with the string pasted and .htaccess like 
/srv/www/orocommerce/web/scripts/.htpasswd

So, the OAuth2 is on the roadmap of the Oro team and will replace de WSSE.


Step 6: Create an AWS Lambda Function
--------------------------------------

[Part 1](https://www.codementor.io/blondiebytes/how-to-create-a-voice-interface-for-an-alexa-skill-oxpjxrl76)
[Part 2](https://medium.com/@blondiebytes/create-an-alexa-skill-part-2-ab83bf5f97f0)

In your intent model put the content of alexa/intents.json

In your AWS Lambda Function put the content of index.js

Step 7: Replace in AWS Function
--------------------------------

Line 23
```nodejs
var oroHost = 'example.com';
```
With your URL.

Line 28 :
```nodejs
    auth: 'user:passwd',
```

Replace user:passwd by your user password generated for .htpasswd


Let's Play
----------------


Roadmap
----------
Index.js: 
 * TODO Wording rewrite website/shop to store 
 * TODO Refactoring : call token with getApiOption like in getAverageShoppingCart
 
 API:
  * Convert revenue in one currency
  * Get new opportunities
  * Replace WSSE by OAuth2 when it is available
  * Push notifications of new orders

About us
---------
Founded by lovers of innovation and design, [Agence Dn'D](http://www.dnd.fr) assists companies for 11 years in the creation and development of customized digital (open source) solutions for web and E-commerce.