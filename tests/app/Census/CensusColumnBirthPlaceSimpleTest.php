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

namespace Fisharebest\Webtrees\Census;

use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Place;
use Mockery;

/**
 * Test harness for the class CensusColumnBirthPlaceSimple
 */
class CensusColumnBirthPlaceSimpleTest extends \Fisharebest\Webtrees\TestCase
{
    /**
     * Delete mock objects
     *
     * @return void
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * Get place mock.
     *
     * @param string $place Gedcom Place
     *
     * @return Place
     */
    private function getPlaceMock($place): Place
    {
        $placeMock = Mockery::mock(Place::class);
        $placeMock->shouldReceive('getGedcomName')->andReturn($place);

        return $placeMock;
    }

    /**
     * @covers \Fisharebest\Webtrees\Census\CensusColumnBirthPlaceSimple
     * @covers \Fisharebest\Webtrees\Census\AbstractCensusColumn
     *
     * @return void
     */
    public function testForeignCountry(): void
    {
        $individual = Mockery::mock(Individual::class);
        $individual->shouldReceive('getBirthPlace')->andReturn($this->getPlaceMock('Westminster, London, England'));

        $census = Mockery::mock(CensusInterface::class);
        $census->shouldReceive('censusPlace')->andReturn('United States');

        $column = new CensusColumnBirthPlaceSimple($census, '', '');

        $this->assertSame('England', $column->generate($individual, $individual));
    }

    /**
     * @covers \Fisharebest\Webtrees\Census\CensusColumnBirthPlaceSimple
     * @covers \Fisharebest\Webtrees\Census\AbstractCensusColumn
     *
     * @return void
     */
    public function testJustCountry(): void
    {
        $individual = Mockery::mock(Individual::class);
        $individual->shouldReceive('getBirthPlace')->andReturn($this->getPlaceMock('United States'));

        $census = Mockery::mock(CensusInterface::class);
        $census->shouldReceive('censusPlace')->andReturn('United States');

        $column = new CensusColumnBirthPlaceSimple($census, '', '');

        $this->assertSame('', $column->generate($individual, $individual));
    }

    /**
     * @covers \Fisharebest\Webtrees\Census\CensusColumnBirthPlaceSimple
     * @covers \Fisharebest\Webtrees\Census\AbstractCensusColumn
     *
     * @return void
     */
    public function testKnownState(): void
    {
        $individual = Mockery::mock(Individual::class);
        $individual->shouldReceive('getBirthPlace')->andReturn($this->getPlaceMock('Maryland, United States'));

        $census = Mockery::mock(CensusInterface::class);
        $census->shouldReceive('censusPlace')->andReturn('United States');

        $column = new CensusColumnBirthPlaceSimple($census, '', '');

        $this->assertSame('Maryland', $column->generate($individual, $individual));
    }

    /**
     * @covers \Fisharebest\Webtrees\Census\CensusColumnBirthPlaceSimple
     * @covers \Fisharebest\Webtrees\Census\AbstractCensusColumn
     *
     * @return void
     */
    public function testKnownStateAndTown(): void
    {
        $individual = Mockery::mock(Individual::class);
        $individual->shouldReceive('getBirthPlace')->andReturn($this->getPlaceMock('Miami, Florida, United States'));

        $census = Mockery::mock(CensusInterface::class);
        $census->shouldReceive('censusPlace')->andReturn('United States');

        $column = new CensusColumnBirthPlaceSimple($census, '', '');

        $this->assertSame('Florida', $column->generate($individual, $individual));
    }
}
