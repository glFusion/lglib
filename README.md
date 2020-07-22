lglib
=====

This plugin provides some javascript and css files that are used by other plugins:

- a DHTML popup calendar (c) Milhai Bazon, dynarch.com 2002-2005
  - see https://www.leegarner.com/jscalendar-1.0 for documentation
- The new Jquery datetime picker (c) 2016 Trent Richardson; Licensed MIT (to replace the DHTML claendar)
  - see http://trentrichardson.com/examples/timepicker
- image resizing and url creation for static image URLs
- PDF libraries
  * FPDF, by Olivier PLATHEY http://www.fpdf.org/
  * HTML2PDF, by Laurent MINGUET https://github.com/spipu/html2pdf/
  * TCPDF https://tcpdf.org/
- Message storage and retrieval for inter-plugin messaging
- Automatically resize images in articles (Experimental!). Enable the smartresizer
in the plugin configuration to test this feature.
- A color picker by Brian Grinstead (https://github.com/bgrins/spectrum)
- Name Parser class (c) Josh Fraser

These scripts, especially the DHTML calendar, may not work correctly if they are
loaded more than once, so this plugin becomes the single source for them.

Also provided are some common functions used across plugins.

A new cron.php has been added that can be called either via URL
(http://yoursite/lglib/cron.php?key=xxyyzz) or command line
(php -q cron.php xxyyzz). The security key (xxyyzz) is configured in the
plugin configuration and is optional but recommended. This cron executor
takes over the system cron function to prevent any long-running tasks such
as database backups from affecting site visitors.
