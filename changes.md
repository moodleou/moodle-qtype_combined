# Change log for the Variable numeric question type


## Changes in 1.8

* Fix a nasty bug where editing a question while duplicating it could break the original question.
* Fix Behat tests to work with Moodle 3.6.


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
