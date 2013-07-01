Combined question type
----------------------

A combined question type which allows the embedding of the response fields for
various available sub questions in the question text.

So the student can enter a numeric or short text answer or choose an answer or
answer(s) from  using a select box, check boxes or radio boxes.

This question type was written by Jamie Pratt (http://jamiep.org/) for the
Open University (http://www.open.ac.uk/).

This question type is compatible with Moodle 2.5+.


###Requirements

You will need to install at least one question type that can be used as a sub question, you can use any of the latest versions of
these question types as sub questions:

* [pmatch](https://github.com/moodleou/moodle-qtype_pmatch/)
* [gapselect](https://github.com/moodleou/moodle-qtype_gapselect/)
* [oumultiresponse](https://github.com/moodleou/moodle-qtype_oumultiresponse/)
* [varnumeric](https://github.com/moodleou/moodle-qtype_varnumeric/)


####Making other question types combinable

You can make other question types work with this combined question type by adding the directory combinable/ to your question
type. See examples of the files which are required in the combinable directory in the question types above. Or for a built in
question type or where you don't want to change the code in the other question type plug ins directory you can put the required
files in question/type/combined/combinable/{questiontypename}/

See question/type/combined/combinable/README.md for more information.


###Installation

####Installation Using Git 

To install using git type these commands in the root of your Moodle install:
    git clone git://github.com/moodleou/moodle-qtype_combined.git question/type/combined
    echo '/question/type/combined/' >> .git/info/exclude


####Installation From Downloaded zip file

Alternatively, download the zip from
https://github.com/moodleou/moodle-qtype_combined/zipball/master
unzip it into the question/type folder, and then rename the new folder to combined.
