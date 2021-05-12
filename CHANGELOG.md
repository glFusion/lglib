# lgLib utility plugin for glFusion

## 1.0.13
Release TBD
  * Only use session ID in message table for anonymous recipients.

## 1.0.12
Release 2021-05-09
  * SmartResizer: do not create thumbnail directory until needed.
  * Move Message and JobQueue functions into classes.
  * Enable SmartResizer to return full image urls and skip lightbox tags.
  * Lower PHP requirement to 7.2.0.
  * Allow plugins to register templates to be used by SmartResizer.
  * Standardize on `nosmartresize` to avoid smart resizing.
  * Add `success` as a message level.

## 1.0.11
Release 2020-07-27
  * Fix SmartResizer injecting HTML entities into UTF-8 pages.
  * Update PDF creation libraries for PHP 7.4
    * HTML2PDF: v5.2.2
    * FPDF: v1.8.2
    * TCPDF: v6.3.5

## 1.0.10
Release 2020-07-13
  * Fix SmartResizer which was breaking initial tags in the HTML.

## 1.0.9
Release  2019-07-28
  * Update PDF classes, trim included fonts to reduce download size.
  * Remove `plugin_outputfilter` function that doesn't work with DOMDocument.
  * Fix SmartResizer to ignore images that are already in a link.
  * Update job queue runner to use job name supplied by the plugins.
  * Add service functions to call NameParser class functions.
  * Enable web services so plugins can call `PLG_invokeService()`.
  * Fix getting the `notavailable` image when a local image is not found.
  * Fix SmartResizer to strip extra html, body and doctype tags

## 1.0.8
Release 2018-10-17
- Change session ID field in messages table to 80 characters
- Ensure that image cache directories are created before use
- Add `LGLIB_getVar()` to safely get array element values
- Error-handling fixes for html2pdf classes

## 1.0.7
Release 2018-04-14
- Fix language strings for colorpicker
- Add Jquery-based datetime picker

## 1.0.6
Release 2018-04-10
- Call `PLG_itemDeleted()` when plugin is removed
- Add `level` indicator in message handler
- Add `LGLIB_colorpicker()` to provide color picker
- Remove template var dependency in smart resizing
- Implement class autoloader for PDF classes

## 1.0.5
Released 2017-09-09
- Make sure image cache directories are created
- Add service function to resize images, return more information
- Add generic `LGLIB_getDocUrl()` to get documentation pages
- Add automatic image resizing and lightbox display
- Fixes to PDF classes for PHP 7

## 1.0.4
- Modify html2pdf with limited fonts to reduce plugin size

## 1.0.3
- Updates for glFusion 1.5+ and UIkit themes
- Update html2pdf to 4.5.1 (http://html2pdf.fr/)
- Update fpdf to 1.8.1 (http://www.fpdf.org/)
- Remove icon from Command and Control in recent glFusion versions

## 1.0.1
- Add option to use `lglib_messages` template variable for messages. Default
is to append messages to `info_block` in header.thtml

## 1.0.0
- Add "/index.php" to backup list form URL, delete checkboxes weren't working
