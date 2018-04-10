lglib
=====

This plugin provides some javascript and css files that are used by other
plugins:

- a Jquery-based datetime picker (http://trentrichardson.com/examples/timepicker)
- a popup calendar (see https://www.leegarner.com/jscalendar-1.0)
- image resizing and url creation for static image URLs
- PDF libraries
  * FPDF, by Olivier PLATHEY
  * HTML2PDF, by Laurent MINGUET
- Message storage and retrieval for inter-plugin messaging
- Automatically resize images (Experimental!). Enable the smartresizer
in the pluginc configuration to test this feature.
- A color picker

These scripts, especially the calendar, may not work correctly if they are
loaded more than once, so this plugin becomes the single source for them.

Also provided are some common functions used across plugins.

A new cron.php has been added that can be called either via URL
(http://yoursite/lglib/cron.php?key=xxyyzz) or command line
(php -q cron.php xxyyzz). The security key (xxyyzz) is configured in the
plugin configuration and is optional but recommended. This cron executor
takes over the system cron function to prevent any long-running tasks such
as database backups from affecting site visitors.

In addition to the FPDF and HTML2PDF libraries mentioned above, this
plugin also includes NameParser.class.php ((c) Josh Fraser) and the DHTML
calendar by Milhai Bazon, (c) dynarch.com 2002-2005

The new Jquery datetime picker is Copyright (c) 2016 Trent Richardson; Licensed MIT
