# Change log for the Combined question type

## Changes in 2.3

* This version is compatible with Moodle 5.0 and 4.5.
* Fixed alignment issues for correct/incorrect icons in single-choice and multi-response sub-questions.
* Show Your Working: Now available as both a textarea and an editor (supports file uploads).
* Fixed backup and restore tests to run synchronously for Moodle 4.4.
* Combined PMatch:
  * First answer box now prefilled with match () to help authors.
  * Added option to enable/disable spellcheck.
  * Added missing help text for the model answer field.
  * Added option to treat curly/straight punctuation marks as interchangeable (default = on).
* Feedback textarea size increased to two lines.
* Combined Numeric:
  * Added Feedback for correct response field to the edit form.
  * Added validation to ensure the teacher’s answer is in the correct format.
* Combined:
  * Fixed fatal error when Show Working area is blank.
  * Fixed bug where Incorrect feedback displayed even when not needed.
  * Answer fields now highlighted when unanswered.
  * Improved display of incorrect feedback by replacing underscores with spaces in placeholders.
  * Fixed Undefined property: qtype_combined_showworking_fake_question::$id error when attaching an image to Show Working.
  * Fixed exception when editing a question with Show Working.
* Defined excluded hash fields and implemented legacy backup data conversion (MDL-83541).
* Fixed coding style issues and improved automated tests.


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
