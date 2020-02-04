## El.php

Pure static methods for rendering HTML from parameters.

## Element.php

Object representation of an HTML element. Can be mutated before calling render, which will invoke El,
unless you extend it to hook it up to a different rendering mechanism (it's quite easy to do so).

## ElementAttribute.php
Element's have an array of these, which are objects containing accessors based on their name (ie. class/id/etc).