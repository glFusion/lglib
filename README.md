# lglib Utility Plugin

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
- A general job queue where plugins can submit jobs to be run later.

These scripts, especially the DHTML calendar, may not work correctly if they are
loaded more than once, so this plugin becomes the single source for them.

Also provided are some common functions used across plugins.

## Job Queue Processing
The job queue allows plugins to submit jobs with parameters to be processed later.
Since jobs may take some time (hence the benefit of queuing them) they should not
normally be processed along with the normal schedule task processor. There are a
couple of options to improve performance.
- If the main glFusion scheduled tasks are run by calling cron.php with page views,
set `Run Queue with scheduled tasks` to `No` in the lgLib plugin configuration.
Then call `your_site/lglib/cron.php` periodically to run the jobs.
- If the main glFusion cron.php is called via command line only then set
`Run Queue with scheduled tasks` to `Yes` and let the normal scheduled tasks
include the job queue processing.

### Internal Scheduled Task Processor
A new cron.php has been added that can be called either via URL
(http://yoursite/lglib/cron.php?key=xxyyzz) or command line
(php -q cron.php xxyyzz). The security key (xxyyzz) is configured in the
plugin configuration and is optional but recommended. This cron executor
takes over the system cron function to prevent any long-running tasks such
as database backups from affecting site visitors.
