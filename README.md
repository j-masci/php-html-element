Renders HTML elements using PHP. 

### Usage

```php
use JMasci\HtmlElement as El;

El::get( 'div', 'Some content...', [
    'class' => 'container',
] );
```

Will return a string of HTML:

```html
<div class="container">Some content...</div>
```

This will do the same thing:

```php
El::get( 'div.container', 'Some content...', [] );
```

This will add 2 classes to the container:

```php
El::get( 'div.container', 'Some content...', [
    'class' => 'other-class'
] );
```

The "class" attribute can also be an array like this:

```php
El::get( 'div.container', 'Some content...', [
    'class' => [ 'other-class', 'other-class-2' ]
] );
```

Or an array like this:

```php
El::get( 'div.container', 'Some content...', [
    'class' => [
        'other-class' => true,
        'not-this-class' => false,
        'other-class-2' => true
    ]   
] );
```

In some cases you'll want to only open the tag so you don't have to pass the inner HTML to the function. (set
4th parameter to false; its true by default). 

```php
El::get( 'form', '', [ 'action' => '...', 'method' => '...' ], false );
echo 'inner html...';
echo '</form>';
```

But, there's also an El::open() method to do the same thing...

```php
El::open( 'form', [ 'action' => '...', 'method' => '...' ] );
echo 'inner html...';
echo El::close( 'form' );
```

El::get() will validate and sanitize the tagname and all attributes and then call El::strict().

El::get() will:

- Allow the tag to contain classes and/or an ID in string "selector" format.
- Allow $atts['class'] and $atts ['style'] to be an array (todo: styles array)
- JSON encode other attributes when they are passed as arrays or objects (todo: would this be a potential security 
risk? If you accidentally provided a user object instead of a user ID then we'll silently just go ahead and print the 
entire user, which is unlike the behaviour if you tried to print an object as a string. I may change this.)

El::get_strict() will not sanitize or validate any of the data you pass in. It expects that:
- The tag is a valid HTML tag only.
- The attributes array is an array of scalar values (no arrays or objects for any reasons).
- The tag name and all attributes must be properly sanitized.

For these reasons, many of the the methods that El::get() uses to filter and validate your input
are also available publicly to you. You may want to use some of them in combination with El::get_strict().

```php
El::get_strict( 'div', '', [ 
    'user_id' => (int) $user->get( 'id' ),
    'data-something' => esc_attr( $some_user_input_or_something ), 
    'class' => 'user-info', 
    'style' => 'display: none;'
], false );
```