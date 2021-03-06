<?php
/**
 * webtrees: online genealogy
 * Copyright (C) 2019 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Fisharebest\Webtrees\Module;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Stats;
use Fisharebest\Webtrees\Tree;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TopGivenNamesModule
 */
class TopGivenNamesModule extends AbstractModule implements ModuleBlockInterface
{
    // Default values for new blocks.
    private const DEFAULT_NUMBER = '10';
    private const DEFAULT_STYLE  = 'table';

    /** {@inheritdoc} */
    public function getTitle(): string
    {
        /* I18N: Name of a module. Top=Most common */
        return I18N::translate('Top given names');
    }

    /** {@inheritdoc} */
    public function getDescription(): string
    {
        /* I18N: Description of the “Top given names” module */
        return I18N::translate('A list of the most popular given names.');
    }

    /**
     * Generate the HTML content of this block.
     *
     * @param Tree     $tree
     * @param int      $block_id
     * @param string   $ctype
     * @param string[] $cfg
     *
     * @return string
     */
    public function getBlock(Tree $tree, int $block_id, string $ctype = '', array $cfg = []): string
    {
        $num       = $this->getBlockSetting($block_id, 'num', self::DEFAULT_NUMBER);
        $infoStyle = $this->getBlockSetting($block_id, 'infoStyle', self::DEFAULT_STYLE);

        extract($cfg, EXTR_OVERWRITE);

        $stats   = new Stats($tree);

        switch ($infoStyle) {
            case 'list':
                $content = view('modules/top10_givnnames/block', [
                    'males'   => $stats->commonGivenMaleListTotals('1', $num),
                    'females' => $stats->commonGivenFemaleListTotals('1', $num),
                ]);
                break;
            default:
            case 'table':
                $content = view('modules/top10_givnnames/block', [
                    'males'   => $stats->commonGivenMaleTable('1', $num),
                    'females' => $stats->commonGivenFemaleTable('1', $num),
                ]);
                break;
        }

        if ($ctype !== '') {
            $num = (int) $num;

            if ($num === 1) {
                // I18N: i.e. most popular given name.
                $title = I18N::translate('Top given name');
            } else {
                // I18N: Title for a list of the most common given names, %s is a number. Note that a separate translation exists when %s is 1
                $title = I18N::plural('Top %s given name', 'Top %s given names', $num, I18N::number($num));
            }

            if ($ctype === 'gedcom' && Auth::isManager($tree)) {
                $config_url = route('tree-page-block-edit', [
                    'block_id' => $block_id,
                    'ged'      => $tree->name(),
                ]);
            } elseif ($ctype === 'user' && Auth::check()) {
                $config_url = route('user-page-block-edit', [
                    'block_id' => $block_id,
                    'ged'      => $tree->name(),
                ]);
            } else {
                $config_url = '';
            }

            return view('modules/block-template', [
                'block'      => str_replace('_', '-', $this->getName()),
                'id'         => $block_id,
                'config_url' => $config_url,
                'title'      => $title,
                'content'    => $content,
            ]);
        }

        return $content;
    }

    /** {@inheritdoc} */
    public function loadAjax(): bool
    {
        return false;
    }

    /** {@inheritdoc} */
    public function isUserBlock(): bool
    {
        return true;
    }

    /** {@inheritdoc} */
    public function isGedcomBlock(): bool
    {
        return true;
    }

    /**
     * Update the configuration for a block.
     *
     * @param Request $request
     * @param int     $block_id
     *
     * @return void
     */
    public function saveBlockConfiguration(Request $request, int $block_id)
    {
        $this->setBlockSetting($block_id, 'num', $request->get('num', self::DEFAULT_NUMBER));
        $this->setBlockSetting($block_id, 'infoStyle', $request->get('infoStyle', self::DEFAULT_STYLE));
    }

    /**
     * An HTML form to edit block settings
     *
     * @param Tree $tree
     * @param int  $block_id
     *
     * @return void
     */
    public function editBlockConfiguration(Tree $tree, int $block_id)
    {
        $num       = $this->getBlockSetting($block_id, 'num', self::DEFAULT_NUMBER);
        $infoStyle = $this->getBlockSetting($block_id, 'infoStyle', self::DEFAULT_STYLE);

        $info_styles = [
            /* I18N: An option in a list-box */
            'list'  => I18N::translate('list'),
            /* I18N: An option in a list-box */
            'table' => I18N::translate('table'),
        ];

        echo view('modules/top10_givnnames/config', [
            'infoStyle'   => $infoStyle,
            'info_styles' => $info_styles,
            'num'         => $num,
        ]);
    }
}
