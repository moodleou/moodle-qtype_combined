# Combined question type [![Build Status](https://travis-ci.org/moodleou/moodle-qtype_combined.svg?branch=master)](https://travis-ci.org/moodleou/moodle-qtype_combined)

A combined question type which allows the embedding of the response fields for
various available sub questions in the question text.

So the student can enter a numeric or short text answer or choose an answer or
answer(s) from  using a select box, check boxes or radio boxes.


## Requirements

You will need to install at least one question type that can be used as a sub question, you can use any of the latest versions of
these question types as sub questions:

* Select missing words - which is now part of the standard Moodle release.
* [Pattern match](https://moodle.org/plugins/qtype_pmatch)
* [OU multiple response](https://moodle.org/plugins/qtype_oumultiresponse)
* [Variable numeric](https://moodle.org/plugins/qtype_varnumericset)


## Acknowledgements

This question type was written by Jamie Pratt (http://jamiep.org/) for the
Open University (http://www.open.ac.uk/).

This version of this question type is compatible with Moodle 3.4+. There are
other versions available for Moodle 2.5+.


## Installation

### Installation Using from the Moodle plugins directory

Works as usual starting here
* https://moodle.org/plugins/qtype_combined

### Installation Using Git 

To install using git type these commands in the root of your Moodle install:

    git clone https://github.com/moodleou/moodle-qtype_combined.git question/type/combined
    echo '/question/type/combined/' >> .git/info/exclude

Then run the moodle update process
Site administration > Notifications


## Making other question types combinable

You can make other question types work with this combined question type by
adding the directory combinable/ to your question type. See examples of the
files which are required in the combinable directory in the question types above.
Or for a built in question type or where you don't want to change the code in
the other question type plug ins directory you can put the required
files in question/type/combined/combinable/{questiontypename}/

See question/type/combined/combinable/README.md for more information.
