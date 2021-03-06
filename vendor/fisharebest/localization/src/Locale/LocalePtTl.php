<?php namespace Fisharebest\Localization\Locale;

use Fisharebest\Localization\Territory\TerritoryTl;

/**
 * Class LocalePtTl
 *
 * @author    Greg Roach <fisharebest@gmail.com>
 * @copyright (c) 2018 Greg Roach
 * @license   GPLv3+
 */
class LocalePtTl extends LocalePt
{
    public function territory()
    {
        return new TerritoryTl();
    }
}
