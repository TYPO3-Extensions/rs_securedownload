.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt
.. include:: Images.txt

Administration
==============

The extension has "only" two configuration vars.

Both can be found in the extension manager.

|img-10|

The first option enables the IP-Logging of all tries to download the secured file. The IP of the requesting computer will be saved in the Database at access time.

The second option enables the Name-Logging of all tries to download the secured file.The resolved name of the requesting computer will be saved in the Database at access time.

**Attention:**
**Because of legal reasons, both options are disabled by default. In some states, you must explicitly indicate to the user, that his IP address will be saved, if he tries to download the file.**

\-

If you copy the download file "rssecuredownload.php" from the root folder of the extension to the root of your website folder this file will be shown for the download link.

Example::

	http://www.your-domain.net/typo3conf/ext/rs_securedownload/rssecuredownload.php

	will be changed to

	http://www.your-domain.net/rssecuredownload.php


