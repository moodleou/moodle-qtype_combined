Combined question type
----------------------

A combined question type which allows the embedding of the response fields for various available
sub questions in the question text.

So the student can enter a numeric or short text answer or choose an answer or answer(s) from
 using a select box, check boxes or radio boxes.

###Requires

It is not required to install these contrib question types but if you have the following contrib question types installed then
you can use these as sub questions :

* [pmatch](https://github.com/moodleou/moodle-qtype_pmatch/)
* [gapselect](https://github.com/moodleou/moodle-qtype_gapselect/)
* [oumultiresponse](https://github.com/moodleou/moodle-qtype_oumultiresponse/)

It also uses the inbuilt 'numeric' question type.

And you can make other question types work with this combined question type by adding the directory combinable/ to your question
type. See examples of combinable.php in the root folder of the question types above or for the built in numeric question type
see question/type/combined/combinable/numerical/


This question type was written by Jamie Pratt (http://jamiep.org/).

This question type is compatible with Moodle 2.4+  (master branch).

###Installation

####Installation Using Git 

To install using git for a 2.4+ Moodle installation, type this command in the
root of your Moodle install:

    git clone git://github.com/moodleou/moodle-qtype_combined.git question/type/combined
    echo '/question/type/combined' >> .git/info/exclude

####Installation From Downloaded zip file

Alternatively, download the zip from :

* Moodle 2.4+ - https://github.com/moodleou/moodle-qtype_combined/zipball/master

unzip it into the question/type folder, and then rename the new folder to combined.
