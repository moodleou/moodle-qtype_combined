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
final class utils_test extends \advanced_testcase {

    public function test_number_in_style(): void {
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

    public function test_replace_embed_placeholder(): void {
        global $CFG;
        require_once($CFG->dirroot.'/question/type/combined/combiner/base.php');

        $placeholderstrings = [
            'Normal' => 'Part_1:multiresponse',
            'Multiple underscores' => 'Part_1_c:multiresponse',
            'Duplicate place holder' => 'Part_1_Part_1_c:multiresponse',
        ];
        $expected = ['Part 1:multiresponse', 'Part 1 c:multiresponse', 'Part 1 Part 1 c:multiresponse'];
        $expectedplaceholderemoved = ['1:multiresponse', '1 c:multiresponse', '1 Part 1 c:multiresponse'];
        $i = 0;
        foreach ($placeholderstrings as $key => $value) {
            $actual = utils::replace_embed_placeholder($value, true);
            $actual2 = utils::replace_embed_placeholder($value);
            $this->assertEquals($expectedplaceholderemoved[$i], $actual);
            $this->assertEquals($expected[$i], $actual2);
            $i++;
        }
    }
}
