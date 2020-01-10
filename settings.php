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

/**
 * Admin settings for the multichoice question type.
 *
 * @package   qtype_combined
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Default settings for singlechoice subquestions.
    $settings->add(new admin_setting_heading('singlechoiceheading',
        get_string('singlechoice', 'qtype_combined'), ''));

    $settings->add(new admin_setting_configcheckbox('qtype_combined/shuffleanswers_singlechoice',
        new lang_string('shuffleanswers', 'qtype_multichoice'),
        new lang_string('shuffleanswers_desc', 'qtype_multichoice'), '1'));

    $settings->add(new qtype_multichoice_admin_setting_answernumbering('qtype_combined/answernumbering_singlechoice',
        new lang_string('answernumbering', 'qtype_multichoice'),
        new lang_string('answernumbering_desc', 'qtype_multichoice'), 'none', null ));

    // Default settings for multiresponse subquestions.
    $settings->add(new admin_setting_heading('multiresponseheading',
        get_string('multiresponse', 'qtype_combined'), ''));

    $settings->add(new admin_setting_configcheckbox('qtype_combined/shuffleanswers_multiresponse',
        new lang_string('shuffleanswers', 'qtype_multichoice'),
        new lang_string('shuffleanswers_desc', 'qtype_multichoice'), '1'));

    $settings->add(new qtype_multichoice_admin_setting_answernumbering('qtype_combined/answernumbering_multiresponse',
        new lang_string('answernumbering', 'qtype_multichoice'),
        new lang_string('answernumbering_desc', 'qtype_multichoice'), 'none', null ));
}
