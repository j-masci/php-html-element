Renders HTML elements using PHP. 

### Usage

```php
use JMasci\HtmlElement\El;

echo El::get( 'div', 'Some content...', [
    'class' => 'container',
] );
```

Prints:

```html
<div class="container">Some content...</div>
```

Does the same thing:

```php
echo El::get( 'div.container', 'Some content...', [] );
```

Specify classes in 2 places (both classes are used)

```php
echo El::get( 'div.container', 'Some content...', [
    'class' => 'other-class'
] );
```

Class attribute as array:

```php
echo El::get( 'div.container', 'Some content...', [
    'class' => [ 'other-class', 'other-class-2' ]
] );
```

Class attribute as array (alternate):

```php
echo El::get( 'div.container', 'Some content...', [
    'class' => [
        'other-class' => true,
        'not-this-class' => false,
        'other-class-2' => true
    ]   
] );
```

All attributes are valid (not just classes...):

```php
// self-closing tag can be called with just the open method
echo El::open( 'input', [
    'type' => 'number',
    'id' => $name,
    'name' => $name,        
    'class' => [ 'class-1', $class_2 ? 'class-2' : '' ],    
    'min' => 0, 
    'max' => 99,
    'step' => 1,
    'data-location' => $location_id,
    'data-json' => El::json_encode_for_html_attr( $data ),
    // this will print just <input.... required> (todo: might re-think how we do this)    
    'required' => true,
] );
```

Demonstration of why El might be useful to you in the first place, if your code is structured like this:

```php
// input is a self-closing tag, we can just open it.
echo El::open( 'input', $form->fields->field_name->build_element_attributes( $form_rendering_context, [
    'class' => 'js-mask-type-1'
] ) );
```

Open the tag only (the harder way):

```php
echo El::get( 'form', '', [ 'action' => '...', 'method' => '...' ], false );
echo 'inner html...';
echo '</form>';
```

The easier way:

```php
echo El::open( 'form', [ 'action' => '...', 'method' => '...' ] );
echo 'inner html...';
echo El::close( 'form' );
```

El::get() will:

- Validate and sanitize the tag name and all attributes.
- Allow the tag to contain classes and/or an ID in string "selector" format.
- Allow $atts['class'] and $atts ['style'] to be an array (todo: styles array)
- (todo: currently, JSON encodes objects/arrays for attributes other than class/style, but, I believe 
this to be a security concern and plan on changing it).

### El::get_strict()

El::get_strict() expects all input to be in the correct format and will not sanitize or validate anything
that you pass in. It expects:

- The tag is a valid (and/or sanitized) HTML tag. No classes or ID.
- The attributes array is an array of scalar values. No arrays or objects.
- All attribute names AND values are properly sanitized.

Note: El::get() will validate your data and then call El::get_strict(). Many of the methods
that El::get() uses to validate your data are also available publicly to you. You may want to
use a subset of them if you decide to use El::get_strict().

Why use El::get_strict() at all then?

- Long story short: El::get() may over sanitize your data. I may look into this more and provide a more specific answer. 
- Todo: I may allow developers to override the default sanitation method(s) to set them up specifically
for your environment. 
- Todo: another possibility is to let the caller specify which attributes should not be sanitized
in El::get().
- However, I don't plan on removing El::get_strict() even if the above solutions are implemented.

### El::get_strict() example

```php
echo El::get_strict( 'div', '<p>Inner...</p>', [    
    'class' => El::parse_classes( [
        'user-wrapper' => true,        
        'logged-in' => true,
        'is-admin' => $user->is_admin(),
    ]),
    'style' => 'display: none;',
    'data-user' => (int) $user->get( 'id' ),
    'data-something' => esc_attr( $some_user_input_or_something ),     
    'data-preferences' => El::json_encode_for_html_attr( $preferences ),    
] );
```