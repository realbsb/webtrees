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

namespace Fisharebest\Webtrees;

use Fisharebest\Localization\Locale\LocaleEnUs;
use Fisharebest\Webtrees\Http\Controllers\ListController;
use Fisharebest\Webtrees\Services\IndividualListService;
use Fisharebest\Webtrees\Services\LocalizationService;
use Fisharebest\Webtrees\Theme\WebtreesTheme;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test the individual lists.
 *
 * @coversNothing
 */
class IndividualListTest extends \Fisharebest\Webtrees\TestCase
{
    protected static $uses_database = true;

    /**
     * @covers \Fisharebest\Webtrees\Http\Controllers\ListController
     * @return void
     */
    public function testIndividualList(): void
    {
        // Needed for Date::display()
        global $tree;

        $tree = $this->importTree('demo.ged');
        $user = Auth::user();

        $localization_service    = new LocalizationService(new LocaleEnUs());
        $individual_list_service = new IndividualListService($localization_service, $tree);
        $controller              = new ListController($individual_list_service, $localization_service);

        $request = new Request(['route' => 'individual-list']);
        Theme::theme(new WebtreesTheme())->init(new Request, $tree);
        $response = $controller->individualList($request, $tree, $user);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $request = new Request(['route' => 'individual-list', 'alpha' => 'B']);
        Theme::theme(new WebtreesTheme())->init(new Request, $tree);
        $response = $controller->individualList($request, $tree, $user);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $request = new Request(['route' => 'individual-list', 'alpha' => ',']);
        Theme::theme(new WebtreesTheme())->init(new Request, $tree);
        $response = $controller->individualList($request, $tree, $user);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $request = new Request(['route' => 'individual-list', 'alpha' => '@']);
        Theme::theme(new WebtreesTheme())->init(new Request, $tree);
        $response = $controller->individualList($request, $tree, $user);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $request = new Request(['route' => 'individual-list', 'surname' => 'BRAUN']);
        Theme::theme(new WebtreesTheme())->init(new Request, $tree);
        $response = $controller->individualList($request, $tree, $user);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
