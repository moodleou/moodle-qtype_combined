# Change log for the Combined question type

## Changes in 2.3
* This version works with Moodle 5.0.
* Automation test failures are fixed.
* Cherry-picked commits since Dec 2022 till now:
  * Misalignment of correct and incorrect icons in singlechoice and multiresponse sub-question with horizontal option.
  * Show Your Working should editor not textarea.
  * Fix tests which submit a quiz.
  * Show Your Working should allow uploaded files.
  * Answer box to have 'match ()' prefilled.
  * Behat fix question/type/combined.
  * Fix feedback boxes size.
  * Remove bad abstractions in the form-building code.
  * Ability to pre-fill/pre-seed answer box.
  * Fix Combined qtype test.
  * Long answer field break theme columns when preview.
  * Stop empty 'show working' getting too small on review.
  * PHP8.2: Fix pmatch, pmatchexamples, pmatchjme behat failures.
  * Add "Feedback for correct response" field.
  * Add "Feedback for correct response" field.
  * Fatal error if you leave a show working area blank in a combined question.
  * Update btn to 'Save preview options and start again'.
  * Fix tests in Combined qtype.
  * All spellchecks disabled, rather than only if pre-fill text given.
  * Missing Helptext.
  * question/type/pmatch (tt) - failures due to js error.
  * Undefined property: qtype_combined_showworking_fake_question::$id.
  * Displaying Exception after editing question.
  * Editing form should validate that the teacher's answer is of the expected form.
  * qtype_combined & qtype_varnumunit fix text question config.
  * Fix backup and restore tests to run synchronously M4.4.
  * Numeric subquestion feedback text should not be displayed inline.
  * Incorrect feedback displayed irrespective of answering correctly.
  * Option to treat all curly/straight punctuation marks as interchangeable
  * Add required for answer field when user not submit an answer.
  * The empty fields with subscript/superscript are not highlighted.
  * Placenames should convert all underscores (_) to spaces.
  * Do not replace underscore in the quesiton text #subq identifier.
  * Technical debt - remove IE-specific rules.
  * 'Show working' plain text mode (defaults to editor mode).
  * Sub-question help text tweak.
  * "10 x 1" means 10 by one line.
  * Define excluded hash fields and convert legacy backup data to new question data.
  * Multiple choice/OU multiple respons/Combined: Fix alignment issue for feedback.
* Upgrade the CI to support Moodle 5.0 (PHP 8.3), and update the branch to support branch MOODLE_405_STABLE, and MOODLE_500_STABLE.

## Changes in 2.2

* This version is compatible with Moodle 4.0 (and 3.11).
* There is a new type of 'text input' (showworking) that can be used within this question type. It is not a
  graded item, but rather an un-graded text box where you can ask students to 'show their working',
  or type anything else you ask them to.
* 'Clear incorrect responses' option added in 'Interactive with multiple tries' mode.
* What used to be referred to as 'Sub-questions' are now called 'Parts'. We thought that was clearer.
* Improved display of sample placeholders that you might want to copy on the editing form.
* Display of embedded multi-choice questions update to match the styling of the stand-alone
  versions of those question types, at least we did that once. I think Moodle core changed layout again,
  so we might need to redo this.
* If the student has not answered all parts of the question, the validation message they get is now clearer.


## Changes in 2.1

* Fix layout issue for multiple choice choices.


## Changes in 2.0

* Multiple-choice, single-response sub-questions are now available using the
  standard Moodle multiple choice question type.
* Multiple-choice (single or multiple) sub-questions now have an option for
  whether the choices should be numbered.
* Admins can control some default settings. The defaults start as:
  multiple-choice options should be shuffled and should not be numbered.
* The wording was changed to consistently use 'sub-question', not 'sub question'.
* Fixed a minor bug that happened when you tried to save an invalid question.
* Fixed the automated tests to pass on the latest Moodle version.


## Changes in 1.9

* Usability improvements in the editing form for question authors.
* Fixes Moodle XML import with Moodle 3.6 and later.
* Fixes in the automated tests due to changes in the other question types used.
* Fix Behat tests to work with Moodle 3.8.


## Changes in 1.8

* Fix a nasty bug where editing a question while duplicating it could break the original question.


## Changes in 1.7

* Privacy API implementation.
* Uses HTML editor when editing choices for OU-multi-response subquestions.
* Better form error message if OU-multi-response subquestions does not have enough choices.
* Allows setting synonyms and convert characters options when editing pattern-match subquestions. 
* Update to use the newer editor_ousupsub, instead of editor_supsub.
* Setup Travis-CI automated testing integration.
* Fix some automated tests to pass with newer versions of Moodle.
* Fix some coding style.
* Due to privacy API support, this version now only works in Moodle 3.4+
  For older Moodles, you will need to use a previous version of this plugin.


## 1.6 and before

Changes were not documented here.
