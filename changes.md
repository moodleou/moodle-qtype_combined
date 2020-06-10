# Change log for the Combined question type


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
