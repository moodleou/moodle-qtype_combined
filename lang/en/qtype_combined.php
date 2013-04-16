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
 * Strings for component 'qtype_combined', language 'en'
 *
 * @package    qtype
 * @subpackage combined
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['correct'] = 'Correct';
$string['correct_choice_embed_code'] = '[[{$a->qid}:{$a->qtype}:{correct choice}]]';
$string['err_accepts_vertical_or_horizontal_layout_param'] = '<p>The \'{$a}\' question type allows you to specify the layout
of your question
type as follows :<ul>
 <li>[[{question identifier}:{$a}:v]] vertical OR</li>
  <li>[[{question identifier}:{$a}:h]] horizontal.</li></ul>
  <p>You should not enter anything else after the second colon.</p>';
$string['err_correctanswerblank'] = 'You have marked this choice as correct but it is blank!';
$string['err_fillinthedetailsforsubq'] = 'You need to fill in the details to describe the sub question \'{$a}\'.';
$string['err_fillinthedetailshere'] = 'You need to fill in the details for this sub question.';
$string['err_nonecorrect'] = 'You have not marked any choices as correct.';
$string['err_notavalidnumberinanswer'] = 'You need to enter a valid number here in the answer field.';
$string['err_notavalidnumberinerrortolerance'] = 'You have entered an invalid number in the error response field.';
$string['err_insufficientnoofcodeparts'] = 'Error, your code to embed a question control \'{$a->fullcode}\' has too few colon
separated
parts. You should have at least a question indentifier id, followed by a question type identifier.';
$string['err_invalid_number'] = 'The \'{$a}\' question type expects a number after the question type identifier, your embed code
should be [[{your question id}:{$a}:{a number here}]]';
$string['err_invalid_width_specifier_postfix'] = '<p>The \'{$a}\' question type allows you to specify the width of your question
type as
follows
 :<ul>
 <li>[[{question identifier}:{$a}:____]] where the width of the input box will depend on
  the number of underscores or</li>
  <li>[[{question identifier}:{$a}:__10__]] where the width of the input box will depend on
  the number.</li></ul>
  <p>You should not enter anything else after the second colon.</p>';
$string['err_invalidquestionidentifier'] = 'Your question identifier code consist of one or more alphanumeric characters.';
$string['err_providepmatchexpression'] = 'You must provide a pmatch expression here.';
$string['err_subq_not_included_in_question_text'] = 'Oops. Seems you have removed this question from the question text. Either :
<ul><li>Delete content from the form section below and then save to remove this question.</li><li>Or embed this question in the
 form with the code {$a}.</li></ul>';
$string['err_thisqtypecannothavemorethanonecontrol'] = 'You have tried to embed more than one control for question type
\'{$a->qtype}\' with question
instance name \'{$a->qid}\'. This question type only allows you to embed one control per question instance.';
$string['err_thisqtypedoesnotacceptextrainfo'] = 'This question type is embedded with the code [[{your question id}:{$a}]].
You should not include anything after the qtype identifier, even a second colon.';
$string['err_twodifferentqtypessameidentifier'] = 'You seem to have two different question instances embedded
with the same identifier \'{$a->qid}\'. This is not allowed.';
$string['err_unrecognisedqtype'] = 'The question type identifier \'{$a->qtype}\' you entered in embedded code
\'{$a->fullcode}\'is not known.';
$string['err_weightingsdonotaddup'] = 'Weightings for sub questions do not add up to 1.';
$string['err_you_must_provide_third_param'] = 'You must provide a third param for question type {$a}.';
$string['err_youneedmorechoices'] = 'You need to enter two or more choices.';
$string['incorrectfeedback'] = 'Feedback for any incorrect response';
$string['noembeddedquestions'] = 'You have deleted all embedded sub question elements from the question text!';
$string['pluginname'] = 'Combined';
$string['pluginname_help'] = 'Create a cloze question type with embedded response fields in the question text to enter a numeric
or text value or select a value from a number of options.';
$string['pluginname_link'] = 'question/type/combined';
$string['pluginnameadding'] = 'Adding a combined question';
$string['pluginnameediting'] = 'Editing a combined question';
$string['pluginnamesummary'] = 'A combined question type which allows the embedding of the response fields for various available
sub questions in the question text.

So the student can enter a numeric or short text answer or choose an answer or answer(s) from
 using a select box, check boxes or radio boxes.';
$string['scinotation'] = 'Scientific notation';
$string['subqheader'] = '\'{$a->qtype}\' input \'{$a->qid}\'';
$string['subqheader_not_in_question_text'] = '\'{$a->qtype}\' input \'{$a->qid}\' (not embedded in question text).';
$string['updateform'] = 'Verify the question text and update the form';
$string['validationerror'] = 'Please attempt all parts of question.';
$string['vertical_or_horizontal_embed_code'] = '[[{$a->qid}:{$a->qtype}:v]] or [[{$a->qid}:{$a->qtype}:h]] depending on if you
want
the options layed out vertically or horizontally.';
$string['weighting'] = 'Weighting';
$string['widthspecifier_embed_code'] = '[[{$a->qid}:{$a->qtype}:{width specifier}]] or just [[{$a->qid}:{$a->qtype}]]';
$string['yougotnright'] = 'You have correctly answered {$a->num} parts of this question.';
