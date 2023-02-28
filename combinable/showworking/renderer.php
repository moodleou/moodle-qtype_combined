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

        $answerstring = 'answer';
        $currentanswer = $qa->get_last_qt_var($subq->step_data_name($answerstring));
        $inputname = $qa->get_qt_field_name($subq->step_data_name($answerstring));
        $context = $options->context;
        $step = $qa->get_last_step_with_qt_var($subq->step_data_name($answerstring));
        $filearea = $subq->get_identifier() . $answerstring;
        if ($options->readonly) {
            $inputinplace = html_writer::tag('div', $this->prepare_response($currentanswer, $qa, $step, $context, $filearea), [
                'role' => 'textbox',
                'aria-readonly' => 'true',
                'aria-labelledby' => $inputname . '_label',
                'class' => 'qtype_combined_response readonly clearfix',
            ]);
        } else {
            [$draftitemid, $currentanswer] = $this->prepare_response_for_editing($currentanswer, $filearea, $step, $context);
            // Setup editor.
            $id = $inputname . '_id';
            $coreeditor = editors_get_preferred_editor(FORMAT_HTML);
            $coreeditor->use_editor($id, question_utils::get_editor_options($options->context),
                question_utils::get_filepicker_options($context, $draftitemid));
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
            $inputinplace .= html_writer::tag('label', get_string($answerstring) . ' ' . $subq->get_identifier(), [
                'class' => 'accesshide',
                'for' => $id,
            ]);
            $inputinplace .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => $inputname . ':itemid',
                'value' => $draftitemid,
            ]);
        }
        $result = html_writer::tag('div', $inputinplace, ['class' => 'qtext']);
        return html_writer::div($result, 'combined-showworking-input');
    }

    /**
     * rewrite url and format the text for read-only.
     *
     * @param string|null $text current answer text.
     * @param question_attempt $qa the question attempt to display.
     * @param question_attempt_step $step the current step.
     * @param context $context the context the attempt belongs to.
     * @param string $filearea file area identifier.
     * @return string Formatted text with rewrite url.
     */
    protected function prepare_response(?string $text, question_attempt $qa,
        question_attempt_step $step, context $context, string $filearea): string {
        $text = $qa->rewrite_response_pluginfile_urls($text, $context->id, $filearea, $step);
        return format_text($text, FORMAT_HTML);
    }

    /**
     * Rewrite and format the text for editing.
     *
     * @param string|null $text current response text.
     * @param string $filearea file area identifier.
     * @param question_attempt_step $step the current step.
     * @param context $context the context the attempt belongs to.
     * @return array [int, string] the draft itemid and the text with URLs rewritten.
     */
    protected function prepare_response_for_editing(?string $text, string $filearea,
        question_attempt_step $step, context $context): array {
        return $step->prepare_response_files_draft_itemid_with_text(
            $filearea, $context->id, $text);
    }
}
