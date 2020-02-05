HTML rendering lib split into two (almost) non-coupled parts:

The El class provides pure methods to convert element properties to HTML. You can use this 
on it's own.

The Element class manages element properties and then uses El for rendering. You can easily
substitute your own rendering mechanism. 

### 1. The El class

Basic Usage:

```php
use JMasci\HtmlElement\El;

echo El::get( 'p', "inner", [
    'class' => 'class-1',    
] );
```

```html
<p class="class-1">...</p>
```

Demonstration of El::open, tags defining classes/id, and classes as a list:

```php
echo El::open( 'div.class-1#id', [
    'class' => [ 'class-2', 0 ? 'class-3' : '' ]
] );
echo "inner";
echo El::close( 'div' );
```

```html
<div id="id" class="class-1 class-2">inner</div>
```

Self closing tags, classes as a dictionary of boolean values, json encoding helper, and key-only attributes (ie. required):

```php
echo El::open( 'input', [
    'type' => 'number',
    'name' => 'number',            
    'class' => [ 'class-1' => true, 'class-2' => false ],    
    'min' => 0,    
    'step' => 1,    
    'data-json' => El::json_encode_for_html_attr( $some_array_or_object ),        
    'required' => true,
] );
```

```html
<input type="number" name="number" class="class-1" min="0" step="1" data-location="{json...}" required />
```

#### User Input / Sanitation / Extensibility

El::get() and El::open() will filter, validate, and sanitize all data that you pass in (except the inner HTML).

There is also El::get_strict() and El::open_strict() which does none of the above. It expects that:
- all data is sanitized
- all attributes are strings.
- the tag is a valid tag and does not contain an ID or classes

The methods for filtering/validating/sanitizing your data are also available to you. You can use
a subset of them and wrap the "strict" methods to define your own solution to better suit your needs. 
You may find this useful if your data is otherwise sanitized in a way that you do not prefer. Extending
the class is also possible. See the code for more info; it was built with extensibility in mind.

### 1. The Element class

Basic usage

```php
use JMasci\HtmlElement\Element;

$element = new Element( 'div', [ 'class' => 'container' ], [
    new Element( 'p', [], [
        "Text"
    ] )
] );

echo $element->render();
```

```html
<div class="container"><p>Text</p></div>
```

Non-basic Usage. Demonstrates most of the available methods but not all; see the code for more info.

```php
use JMasci\HtmlElement\Element;

$element = new Element( null );

// set methods normally return $element
$element->tag_set( 'div' )->attr_set( 'id', 'main' );

$element->attr_get( 'id' ); # "main"
$element->tag_get(); # "div"
$element->attr_exists( 'data-test' ); # false
$element->attr_set( 'data-test' );
$element->attr_reset( 'data-test' );
$element->attr_exists( 'data-test' ); # true
$element->attr_delete( 'data-test' );
$element->attr_exists( 'data-test' ); # false

// all methods are potentially variadic. add_class may accept multiple
// parameters or arrays.
$element->add_class( 'class-1' )->add_class( 'class-2' );
$element->has_class( 'class-1' ); # true
$element->remove_class( 'class-2' );
$element->attr_get( 'class' ); # "class-1"

// different attributes know how to handle different operations.
// the id attribute does not understand "add". The style attribute does.
$element->add_style('display: none;');

// there are many ways to do the same thing. This is not a feature,
// but a result of the extensibility that is built-in. You should
// generally avoid using these methods unless you know what you are doing.
// mostly, you'll use them when extending the class to add your own functionality.
$element->_compound_attr_add( 'style', 'color: red;');
$element->attr_get_instance( 'style' )->add( 'color: blue;' );
$element->attr_get_instance( 'style' )->_call_dynamic_method( 'add', 'color: green;' );

// this will result in an error, unless you extended the 'id' attribute to define 'has'. 
// $element->attr_get_instance( 'id' )->_call_dynamic_method( 'has', '...' );

// adding child elements. note: nesting depth of children is unlimited.
$element->child_append( new Element('p', [], [ "Paragraph 1" ] ));

// stores a string as a child. It is not converted into an Element.
$element->child_append( "<p>Paragraph 2</p>");

// prepend a child
$element->child_prepend( new Element('p', [], [ "Paragraph 0" ] ));

// a "fragment" contains only children.
// we created a new element containing 2 children which are the same.
// note: $element is not cloned, its a reference. Considering cloning if needed.
$fragment = Element::get_fragment_instance( [ $element, $element ] );
$fragment->child_prepend( '<p>Before...</p>' );
echo $fragment->render();
```

Results in:

```html
<p>Before...</p>
<div id="main" class="class-1" style="display: none; color: red; color:blue; color: green;">
    <p>Paragraph 0</p>
    <p>Paragraph 1</p>
    <p>Paragraph 2</p>
</div>
<div id="main" class="class-1" style="display: none; color: red; color:blue; color: green;">
    <p>Paragraph 0</p>
    <p>Paragraph 1</p>
    <p>Paragraph 2</p>
</div>
```

### Real World Example

Note: I'm not recommending that you always use Element to write HTML; doing so may result
in spaghetti code.

However, there are some cases where the lack of flexibility is just not an option. So, we
can trade a bit of simplicity for a lot of flexibility.

```php
use JMasci\HtmlElement\Element;

// a function that returns Element instances and a function that knows how to render them.
// this lets the Element's be modified before rendering. You can use the same idea
// in a lot of different ways.
function get_the_thing(){
    
    $wrapper = new Element( 'div', [ 'class' => 'wrapper' ] );
    $container = new Element( 'div', [ 'class' => 'container' ] );    

    $render = function( $wrapper, $container ){
        ob_start();                        
        echo $wrapper->open_tag();
        echo $container->open_tag();
        echo '...';
        echo $container->close_tag();    
        echo $wrapper->close_tag();        
        // this would have the same effect
        // echo $wrapper->append_child( $container->append_child( "..." )->render();
        return ob_get_clean();                    
    };
    
    return [ $wrapper, $container, $render ];
}

list( $wrapper, $container, $render ) = get_the_thing();

$wrapper->add_class( 'wide-wrapper' );

$container->before( "<h1>Above Container Title</h1>");

echo $render( $wrapper, $container );
```
```html
<div class="wrapper wide-wrapper">
    <h1>Above Container Title</h1>
    <div class="container">...</div>
</div>
```

### Limitations / Not Supported

You'll notice many methods look similar to those found in jQuery. While you can update all of the
elements properties, you cannot query for element's children.

For example, there is no $element->querySelector( 'input[name="first_name"]' ). While this is certainly
possible to implement, I don't plan on doing this as of now.

Also, there is no way to create an Element instance from a string of HTML. This process is not a simple one.

### ElementAttribute / Extensibility 

Every Element contains an array of ElementAttribute instances.

ElementAttribute's have overridable anonymous functions for accessing their values.

For example, the 'class' attribute defines 'get', 'set', 'add', 'remove', 'has'.

The style attribute (by default) defines 'get', 'set', 'add'.

All other attributes use the default instance (but you can change this). The default
instance only defines 'set' and 'get'.

Note: The dynamic 'get' method should always return a scalar value. It is invoked
during rendering. 

Also, all dynamic methods can accept a variable number of parameters.

In general, it's hard to know which attributes define which dynamic methods. And it's hard
to know the accepted parameters for those dynamic methods. This is not ideal, but it's a 
necessary trade-off for flexibility. To deal with this fact, you can extend ElementBaseClass
or Element to create your own well-defined wrapper methods.

```php
use JMasci\HtmlElement\Element;
use JMasci\HtmlElement\ElementAttribute;

Class MyElement extends Element{
    
    // override this to modify attribute instances
    protected function build_attribute_instance($name){
    
        $attr = parent::build_attribute_instance( $name );        
        
        // define your own 'remove' method on the 'style' attribute.
        if ( $name === 'style' ) {
            $attr->methods['remove'] = function( ...$args ) {
                // note: "$this" will be the ElementAttribute instance.                               
            };                          
        }
               
        return $attr;    
    }
}

$e = new MyElement( 'p' );

// "style" now understands "remove"
$e->attr_get_instance( 'style' )->_call_dynamic_method( 'remove', 'display' );
```