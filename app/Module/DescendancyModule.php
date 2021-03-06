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

use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\FontAwesome;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Services\SearchService;
use Fisharebest\Webtrees\Tree;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DescendancyModule
 */
class DescendancyModule extends AbstractModule implements ModuleSidebarInterface
{
    /** {@inheritdoc} */
    public function getTitle(): string
    {
        /* I18N: Name of a module/sidebar */
        return I18N::translate('Descendants');
    }

    /** {@inheritdoc} */
    public function getDescription(): string
    {
        /* I18N: Description of the “Descendants” module */
        return I18N::translate('A sidebar showing the descendants of an individual.');
    }

    /**
     * @param Request       $request
     * @param Tree          $tree
     * @param SearchService $search_service
     *
     * @return Response
     */
    public function getSearchAction(Request $request, Tree $tree, SearchService $search_service): Response
    {
        $search = $request->get('search', '');

        $html = '';

        if (strlen($search) >= 2) {
            $html = $search_service
                ->searchIndividualNames([$tree], [$search])
                ->map(function (Individual $individual): string {
                    return $this->getPersonLi($individual);
                })
                ->implode('');
        }

        if ($html !== '') {
            $html = '<ul>' . $html . '</ul>';
        }

        return new Response($html);
    }

    /**
     * @param Request $request
     * @param Tree    $tree
     *
     * @return Response
     */
    public function getDescendantsAction(Request $request, Tree $tree): Response
    {
        $xref = $request->get('xref', '');

        $individual = Individual::getInstance($xref, $tree);

        if ($individual !== null && $individual->canShow()) {
            $html = $this->loadSpouses($individual, 1);
        } else {
            $html = '';
        }

        return new Response($html);
    }

    /** {@inheritdoc} */
    public function defaultSidebarOrder(): int
    {
        return 30;
    }

    /** {@inheritdoc} */
    public function hasSidebarContent(Individual $individual): bool
    {
        return true;
    }

    /**
     * Load this sidebar synchronously.
     *
     * @param Individual $individual
     *
     * @return string
     */
    public function getSidebarContent(Individual $individual): string
    {
        return view('modules/descendancy/sidebar', [
            'individual_list' => $this->getPersonLi($individual, 1),
        ]);
    }

    /**
     * Format an individual in a list.
     *
     * @param Individual $person
     * @param int        $generations
     *
     * @return string
     */
    public function getPersonLi(Individual $person, $generations = 0): string
    {
        $icon     = $generations > 0 ? 'icon-minus' : 'icon-plus';
        $lifespan = $person->canShow() ? '(' . $person->getLifeSpan() . ')' : '';
        $spouses  = $generations > 0 ? $this->loadSpouses($person, 0) : '';

        return
            '<li class="sb_desc_indi_li">' .
            '<a class="sb_desc_indi" href="' . e(route('module', [
                'module' => 'descendancy',
                'action' => 'Descendants',
                'ged'    => $person->tree()->name(),
                'xref'   => $person->xref(),
            ])) . '">' .
            '<i class="plusminus ' . $icon . '"></i>' .
            $person->getSexImage() . $person->getFullName() . $lifespan .
            '</a>' .
            FontAwesome::linkIcon('individual', $person->getFullName(), ['href' => $person->url()]) .
            '<div>' . $spouses . '</div>' .
            '</li>';
    }

    /**
     * Format a family in a list.
     *
     * @param Family     $family
     * @param Individual $person
     * @param int        $generations
     *
     * @return string
     */
    public function getFamilyLi(Family $family, Individual $person, $generations = 0): string
    {
        $spouse = $family->getSpouse($person);
        if ($spouse) {
            $spouse_name = $spouse->getSexImage() . $spouse->getFullName();
            $spouse_link = FontAwesome::linkIcon('individual', $spouse->getFullName(), ['href' => $person->url()]);
        } else {
            $spouse_name = '';
            $spouse_link = '';
        }

        $marryear = $family->getMarriageYear();
        $marr     = $marryear ? '<i class="icon-rings"></i>' . $marryear : '';

        return
            '<li class="sb_desc_indi_li">' .
            '<a class="sb_desc_indi" href="#"><i class="plusminus icon-minus"></i>' . $spouse_name . $marr . '</a>' .
            $spouse_link .
            FontAwesome::linkIcon('family', $family->getFullName(), ['href' => $family->url()]) .
            '<div>' . $this->loadChildren($family, $generations) . '</div>' .
            '</li>';
    }

    /**
     * Display spouses.
     *
     * @param Individual $person
     * @param int        $generations
     *
     * @return string
     */
    public function loadSpouses(Individual $person, $generations)
    {
        $out = '';
        if ($person && $person->canShow()) {
            foreach ($person->getSpouseFamilies() as $family) {
                $out .= $this->getFamilyLi($family, $person, $generations - 1);
            }
        }
        if ($out) {
            return '<ul>' . $out . '</ul>';
        }

        return '';
    }

    /**
     * Display descendants.
     *
     * @param Family $family
     * @param int    $generations
     *
     * @return string
     */
    public function loadChildren(Family $family, $generations)
    {
        $out = '';
        if ($family->canShow()) {
            $children = $family->getChildren();
            if ($children) {
                foreach ($children as $child) {
                    $out .= $this->getPersonLi($child, $generations - 1);
                }
            } else {
                $out .= '<li class="sb_desc_none">' . I18N::translate('No children') . '</li>';
            }
        }
        if ($out) {
            return '<ul>' . $out . '</ul>';
        }

        return '';
    }
}
