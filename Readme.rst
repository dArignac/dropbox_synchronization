dropbox_synchronization
=======================

This TYPO3 extensions synchronizes a Dropbox app folder with a TYPO3 installation.
Currently you can only synchronize a single folder.

Getting started
~~~~~~~~~~~~~~~

Create a Dropbox app
--------------------

First, `register a new app at Dropbox`_ and note the app key and secret.
It should be a *Dropbox API app* with *Files and datastores* and you probably want to limit it to its own private folder:

.. image:: dropbox_api.png
    :scale: 60%
    :alt: Dropbox app creation
    :align: left

This app will be used to access the files of a Dropbox account.

Authorize the App
-----------------

Next, you have to authorize the newly created app to access your Dropbox files.
Therefore create a new TYPO3 page and add a new root template with the following TypoScript setup content (also see file github.com/dArignac/dropbox_synchronization/blob/master/Configuration/TypoScript/api_authorization.txt):

::

    # Workflow:
    # - create a new page
    # - add a new root template to the page, with the TypoScript below in the setup part
    # - open the page, follow instructions (https://yourdomain.com/index.php?id=<PID>&type=123456789)
    # - disable/delete the new page
    page = PAGE
    page {
	      typeNum = 123456789
        config {
            no_cache = 1
            disableAllHeaderCode = 1
        }
        headerData >
        123456789 = USER_INT
        123456789 {
            userFunc = Tx_DropboxSynchronization_UserFunction_AuthorizationCallback->respond
            dropbox_api {
                key = APP_KEY
                secret = APP_SECRET
            }
        }
    }

Afterwards open the page (https://yourdomain.com/index.php?id=<PID>&type=123456789) and click the Dropbox link. Dropbox asks you to authorize the app, accept it and note the authorization code.
Set this code to the TypoScript setup of the newly created page and reload the page:

::

    page {
	      typeNum = 123456789
        ...
        123456789 = USER_INT
        123456789 {
            ...
            dropbox_api {
                key = APP_KEY
                secret = APP_SECRET
                authorizationCode = AUTHORIZATION_CODE
            }
        }
    }

Then refresh the page and copy the TypoScript setup code marked in green into your default page TypoScript setup (not the setup of the newly created page):

::

    plugin.tx_dropboxsynchronization.settings.accessToken = ACCESS_TOKEN


What did just happen? The extension called Dropbox with the authorization code and the app credentials. Dropbox then created an access token. This token will be used to authenticate all calls of the TYPO3 extension to Dropbox.

**Important:** You have to be quick with settings the *authorizationCode* and reloading the page, the code is valid for about 5 minutes!

Now delete or disable the authorization page you just created.


Synchronizing the files
-----------------------

TODO
Scheduler



.. _register a new app at Dropbox: https://www.dropbox.com/developers/apps/create
