# lgLib utility plugin for glFusion

## 1.0.8
Release TBD
- Ensure that image cache directories are created before use
- Add LGLIB_getVar() to safely get array element values
- Error-handling fixes for html2pdf classes

## 1.0.7
Release 2018-04-14
- Fix language strings for colorpicker
- Add Jquery-based datetime picker

## 1.0.6
Release 2018-04-10
- Call PLG_itemDeleted() when plugin is removed
- Add "level" indicator in message handler
- Add LGLIB_colorpicker() to provide color picker
- Remove template var dependency in smart resizing
- Implement class autoloader for PDF classes

## 1.0.5
Released 2017-09-09
- Make sure image cache directories are created
- Add service function to resize images, return more information
- Add generic LGLIB_getDocUrl() to get documentation pages
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
- Add option to use lglib_messages template variable for messages. Default
is to append messages to info_block in header.thtml

## 1.0.0
- Add "/index.php" to backup list form URL, delete checkboxes weren't working
