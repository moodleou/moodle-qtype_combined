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
 * Combined question embedded sub question renderer class.
 *
 * @package   qtype_showworking
 * @copyright  2022 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined_showworking_embedded_renderer extends qtype_renderer
    implements qtype_combined_subquestion_renderer_interface {

    public function subquestion(question_attempt $qa, question_display_options $options, qtype_combined_combinable_base $subq,
            $placeno) {
        $currentanswer = format_text($qa->get_last_qt_var($subq->step_data_name('answer')), FORMAT_HTML);
        $inputname = $qa->get_qt_field_name($subq->step_data_name('answer'));
        if ($options->readonly) {
            $inputinplace = html_writer::tag('div', $currentanswer, [
                    'role' => 'textbox',
                    'aria-readonly' => 'true',
                    'aria-labelledby' => $inputname . '_label',
                    'class' => 'qtype_combined_response readonly',
                ]);
        } else {
            // Setup editor.
            $id = $inputname . '_id';
            $coreeditor = editors_get_preferred_editor(FORMAT_HTML);
            // TODO: We will add the third param after MDL-76582 is finished to allow upload files in editor.
            $coreeditor->use_editor($id, question_utils::get_editor_options($options->context));
            // Set value.
            $coreeditor->set_text($currentanswer);
            [$rows, $cols] = $subq->get_size();
            $inputinplace = html_writer::tag('div', html_writer::tag('textarea', s($currentanswer), [
                'id' => $id,
                'name' => $inputname,
                'class' => 'form-control',
                'rows' => $rows,
                'cols' => $cols,
            ]));
            $inputinplace .= html_writer::tag('label', get_string('answer') . ' ' . $subq->get_identifier(), [
                'class' => 'accesshide',
                'for' => $id,
            ]);
        }
        $result = html_writer::tag('div', $inputinplace, ['class' => 'qtext']);
        return html_writer::div($result, 'combined-showworking-input');
    }
}
