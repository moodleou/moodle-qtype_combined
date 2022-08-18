<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace qtype_combined;

/**
 * Unit tests for qtype_combined utills.
 *
 * @package   qtype_combined
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \qtype_combined\utils;
 */
class utils_test extends \advanced_testcase {

    public function test_number_in_style() {
        $numbers = [0, 1, 2, 3, 4];

        $expected = ['a. ', 'b. ', 'c. ', 'd. ', 'e. '];
        foreach ($numbers as $num) {
            $actual = utils::number_in_style($num, 'abc');
            $this->assertEquals($expected[$num], $actual);
        }

        $expected = ['A. ', 'B. ', 'C. ', 'D. ', 'E. '];
        foreach ($numbers as $num) {
            $actual = utils::number_in_style($num, 'ABCD');
            $this->assertEquals($expected[$num], $actual);
        }

        $expected = ['1. ', '2. ', '3. ', '4. ', '5. '];
        foreach ($numbers as $num) {
            $actual = utils::number_in_style($num, '123');
            $this->assertEquals($expected[$num], $actual);
        }
        $expected = ['i. ', 'ii. ', 'iii. ', 'iv. ', 'v. '];
        foreach ($numbers as $num) {
            $actual = utils::number_in_style($num, 'iii');
            $this->assertEquals($expected[$num], $actual);
        }

        $expected = ['I. ', 'II. ', 'III. ', 'IV. ', 'V. '];
        foreach ($numbers as $num) {
            $actual = utils::number_in_style($num, 'IIII');
            $this->assertEquals($expected[$num], $actual);
        }

        // Wrong strings as numbering style.
        $actual = utils::number_in_style($num, 'III');
        $this->assertEquals('ERR', $actual);

        // Wrong strings as numbering style.
        $actual = utils::number_in_style($num, 'ABC');
        $this->assertEquals('ERR', $actual);
    }
}
