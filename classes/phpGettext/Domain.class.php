<?php
/**
 * Class to hold a single domain for PHP-Gettext.
 *
 * @author      Danilo Segan <danilo at kvota dot net>
 * @author      Nico Kaiser <nico at siriux dot net>
 * @author      Steven Armstrong <sa at c-area dot ch>
 * @copyright   Copyright (c) 2005 Steven Armstrong <sa at c-area dot ch>
 * @copyright   Copyright (c) 2009 Danilo Segan <danilo at kvota dot net>
 * @package     lglib
 * @version     v1.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @see         https://launchpad.net/php-gettext
 * @filesource
 */
namespace LGLib\phpGettext;

/**
 *  Class to hold a single domain included in $text_domains.
 */
class Domain
{
    /** Reader object.
     * @var object */
    public $l10n;

    /** Locale path.
     * @var string */
    public $path;

    /** Codeset, e.g. "UTF-8".
     * @var string */
    public $codeset;
}
