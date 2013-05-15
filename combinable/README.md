Making other question types work with combined question type
------------------------------------------------------------

The combined question type features a plug in system to allow you to write code to allow other question types to be embeddedable in
 a combined question. In order to make another question type 'combinable' you have to write some hook classes to tell the combined
 question type how to use the question types as sub questions.

Where we look for hook classes
==============================


The question type looks for the hooks that it needs to know how to use the embedded question type in one of two places. Either :

* in the other question type itself in a file called combinable.php in directory combinable/ in the question type directory ie. in
question/type/{questiontypename}/combinable/combinable.php (for when the question type is a non core plug in.) OR
* in this directory question/type/combined/combinable/{questiontypename}/combinable.php (for when the question type is in core
plug in, we didn't want to have to put the hook code in the core Moodle release while the combined question type is not.)


###Hook Class Base Classes

You can find the base classes and interfaces your hook class can extend and / or implement in :

question/type/combined/combinable/combinablebase.php

AND

question/type/combined/combinable/combinabletypebase.php


###Renderer

The renderer used for the embedding the question type is found in the same directory
as the combinable.php file that contains the hook classes.
