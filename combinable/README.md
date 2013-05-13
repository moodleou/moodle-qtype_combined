Making other question types work with combined question type
------------------------------------------------------------


Where we look for hook class
============================

The combined question type features a plug in system to allow you to write code to allow other question types to be embeddedable in
 a combined question.

The question type looks for the hooks that it needs to know how to use the embedded question type in one of two places. Either :

* in the other question type itself in a file called combinable.php in the root of the question type directory ie. in
question/type/{questiontypename}/combinable.php (for when the question type is a non core plug in.) OR
* in this directory question/type/combined/combinable/{questiontypename}/combinable.php (for when the question type is in core
plug in, we didn't want to have to put the hook code in the core Moodle release while the combined question type is not.)


###Hook Class Base Classes

You can find the base classes and interfaces your hook class can extend and / or implement in :

question/type/combined/combiner.php

Renderer
========

The location the plug in looks for the renderer sub type used for the embedding the question type is found in either :

* question/type/{questiontypename}/renderer.php for non core plug ins.
* question/type/combined/renderer.php for core plug ins.

Actually the combined plug in looks in the question type directory within which it found the hook class.

During Developement
===================

During developement of the question type I am going to keep both the renderers and hook classes in the question type in order to
simplify versionning of the code.
