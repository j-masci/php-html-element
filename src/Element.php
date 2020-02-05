<?php

namespace JMasci\HtmlElement;

/**
 * An HTML element object, containing a tag, attributes, and children.
 *
 * Build and mutate the object as needed, and then call ->render().
 *
 * children can contain strings or other instances.
 *
 * Instances can also be fragments, which, generally have just
 * children and no attributes or tag.
 *
 * The process of turning attributes and a tag into HTML is not
 * the concern of this class. It hands off that work to another
 * class. @see Element::open_tag(), @see Element::close_tag()
 *
 * Class Element
 * @package JMasci\HtmlElement
 */
Class Element{

    /**
     * The elements tag name, 'div', 'p', etc.
     *
     * In the future, possibly will support selectors such as
     * 'div.container#main', but, it's messy to do so and
     * possibly a bad idea.
     *
     * @var
     */
    private $tag;

    /**
     * An array of ElementAttribute instances, indexed by
     * the attribute name (ie. 'class', 'id', 'type').
     *
     * @var ElementAttribute[] array
     */
    private $atts = [];

    /**
     * An array of strings or instances of self.
     *
     * @var array
     */
    private $children = [];

    /**
     * Element constructor.
     * @param $tag
     * @param array $atts
     * @param array $children
     */
    public function __construct( $tag, array $atts = [], array $children = [] ) {

        $this->tag_set( $tag );

        foreach ( $atts as $a1 => $a2 ) {
            if ( $a2 instanceof ElementAttribute ) {
                $this->atts[$a1] = $a2;
            } else {
                $this->attr_set( $a1, $a2 );
            }
        }

        foreach ( $children as $child ) {
            $this->child_append( $child );
        }
    }

    /**
     * Returns an instance of self that has no tag or attributes, just
     * children. Supporting fragments will no doubt make some operations
     * a bit harder, but I think its still good.
     *
     * @param array $children
     * @return static
     */
    public static function get_fragment_instance( array $children ){
        return new static( null, [], $children );
    }

    /**
     * True when $this does not have a tag.
     *
     * Normally, if we don't have a tag, it means we also don't have
     * attributes.
     *
     * A "fragment" renders only its children, as adjacent HTML tags.
     *
     * ie. <p></p><p></p>
     *
     * @return bool
     */
    public function is_fragment(){
        return empty( trim( $this->tag ) );
    }

    /**
     * Generates and returns an HTML string.
     *
     * Feel free to only use $this->open_tag() and $this->close_tag() if
     * you want to write the inner HTMl separately.
     *
     * @return string
     */
    public function render(){

        $op = '';

        if ( ! $this->is_fragment() ) {
            $op .= $this->open_tag();
        }

        // can recursively invoke $this->render() on elements of $this->children
        $op .= $this->inner_html();

        if ( ! $this->is_fragment() ) {
            $op .= $this->close_tag();
        }

        return $op;
    }

    /**
     * Render the HTML inside the tag (or the entirety of a fragment) by
     * rendering each one of $this->children.
     *
     * $this->children is (should be) an array consisting of only scalar
     * values and instances of self.
     *
     * @return false|string
     */
    public function inner_html(){

        ob_start();

        // todo: "\r\n" and all that shit? Doing so could break non-html string children
        foreach ( $this->children as $child ){

            // storing children as strings of HTML is fine
            if ( is_scalar( $child ) ) {
                echo $child;
            } else if ( method_exists( $child, 'render' ) ){
                // likely a recursive call
                echo $child->render();
            } else {
                // I guess a no-op here.
            }
        }

        return ob_get_clean();
    }

    /**
     * Returns the opened HTML tag unless this is a fragment.
     *
     * @return string
     */
    public function open_tag(){
        if ( ! $this->is_fragment() ) {
            return El::open( $this->tag, $this->compile_attributes_array() );
        }
    }

    /**
     * Returns the closed HTML tag unless this is a fragment.
     *
     * Should return nothing if $this->tag is a self closing tag, like 'img' or 'br'.
     *
     * @return string
     */
    public function close_tag(){
        if ( ! $this->is_fragment() ) {
            return El::close( $this->tag );
        }
    }

    /**
     * Converts an array of attribute instances into an
     * array of scalar values, still indexed by attribute name.
     *
     * 2 subtle but important things are accomplished in the process:
     *
     * 1. Attribute names stored in the keys of $this->atts are the single source of
     * truth. (normally, $attr_name === $this->atts[$attr_name]->get_name())
     *
     * 2. We call $this->attr_get(), not $this->atts[$attr_name]->get(). Normally,
     * these have the same result, but they might not if Element is extended.
     *
     * @return array
     */
    public function compile_attributes_array(){

        $ret = [];

        foreach ( $this->atts as $name => $value ) {
            $ret[$name] = $this->attr_get( $name );
        }

        return $ret;
    }

    /**
     * Empties the inner contents of the element.
     */
    public function empty(){
        $this->children = [];
    }

    /**
     * @param string|element $thing
     * @return $this
     */
    public function child_append( $thing ){
        $this->children[] = $thing;
        return $this;
    }

    /**
     * @param string|element $thing
     * @return $this
     */
    public function child_prepend( $thing ){
        $this->children = [ $thing ] + $this->children;
        return $this;
    }

    /**
     * Inserts an element after this one.
     *
     * $this becomes a fragment in the process, with (at least) 2 children, one of them
     * being a copy of the old $this.
     *
     * @see self::before()
     *
     * @param $new_ele
     * @return $this
     */
    public function after( $new_ele ){

        if ( $this->is_fragment() ) {
            $this->child_append( $new_ele );
        } else {

            // must do children first.
            $this->children = [
                new Element( $this->tag, $this->atts, $this->children ),
                $new_ele,
            ];

            // turn into fragment, after children operations.
            $this->tag = null;
            $this->atts = [];
        }

        return $this;
    }

    /**
     * todo: methods 'before' and 'after' should both be evaluated for efficacy.
     *
     * Inserts an element before this one, mutating and returning $this (the returned
     * value is not a reference to a new object).
     *
     * As a result, $this becomes a fragment. Meaning that you should no longer be updating
     * the attributes of $this. If you need to modify $this while prepending another element,
     * you can use $this->get_fragment_instance() to keep a reference to the original
     * object which can still be modified.
     *
     * In jQuery, .before() will insert a new domNode and then return the domNode it was
     * called upon, so you can do ele.before().addClass() and that's fine. In our case,
     * there is no DOM, and there is nowhere to put the new element except in $this->children,
     * and the only (logical) way to ensure that the new element is adjacent to $this is
     * to make a copy of $this in $this->children as well, which is why $this becomes
     * a fragment.
     *
     * @param $new_ele
     * @return $this
     */
    public function before( $new_ele ){

        // todo: is this behaviour on fragments robust?
        if ( $this->is_fragment() ) {
            $this->child_prepend( $new_ele );
        } else {

            // must do children first.
            $this->children = [
                $new_ele,
                new Element( $this->tag, $this->atts, $this->children )
            ];

            // turn into fragment, after children operations.
            $this->tag = null;

            // todo: refs to atts remaining in $this->children[1]->atts ???? is this going to cause issues?
            $this->atts = [];
        }

        return $this;
    }

    /**
     * Gets the attribute instance, creating it if it does not already exist.
     *
     * @param $name
     * @return ElementAttribute
     */
    public function attr_get_instance( $name ) {
        return isset( $this->atts[$name] ) ? $this->atts[$name] : static::build_attribute_instance( $name );
    }

    /**
     * You can manually set attribute instances but normally you won't
     * need to do this. Use $this->attr_set() to set the attributes value.
     *
     * @param ElementAttribute $attr
     * @param null $name
     */
    public function attr_set_instance( ElementAttribute $attr, $name = null ){
        $_name = $name !== null ? $name : $attr->get_name();
        $this->atts[$_name] = $attr;
    }

    /**
     * Builds an attribute instance via name. Overriding this method
     * in a subclass is one way to change the behaviour of attribute
     * instances.
     *
     * @param $name
     * @return mixed
     */
    protected static function build_attribute_instance( $name ) {
        return ElementAttribute::get_instance_via_name( $name );
    }

    /**
     * @param $value
     */
    public function tag_set( $value ) {
        $this->tag = $value;
    }

    /**
     * @return mixed
     */
    public function tag_get(){
        return $this->tag;
    }

    /**
     * Gets the value of an attribute by name.
     *
     * @param $name
     * @return mixed
     */
    public function attr_get( $name ){
        return $this->attr_get_instance( $name )->get();
    }

    /**
     * Set the value of an attribute. In most or all cases, just pass
     * in a $name and a $value. Some attributes may have variadic
     * setter functions.
     *
     * @param $name
     * @param mixed ...$args
     * @return $this
     */
    public function attr_set( $name, ...$args ){
        $this->attr_get_instance( $name )->set( ...$args );
        return $this;
    }

    /**
     * Removes an attribute entirely.
     *
     * @param $name
     * @return $this
     */
    public function attr_delete( $name ) {
        unset( $this->atts[$name] );
        return $this;
    }

    /**
     * Reset an attribute to its default value.
     *
     * This seems like a possibly redundant function, but we include it
     * due to the possibility that some attributes store their underlying
     * data as arrays. The class attribute can do this (it can also store
     * it as a string however).
     *
     * @param $name
     */
    public function attr_reset( $name ) {
        // we could invoke a reset function on the instance but then we
        // expect all instances to define this method. For now, re-building
        // the instance should have the same effect.
        $this->attr_delete( $name );
        $this->attr_get_instance( $name );
    }

    /**
     * Whether the attribute name exists, returns true regardless of its value.
     *
     * ie. <input type="text" name="">, type/name exist, id does not.
     *
     * @param $name
     * @return bool
     */
    public function attr_exists( $name ) {
        return isset( $this->atts[$name] );
    }

    /**
     * Add a value to an attribute by name.
     *
     * You can add classes and maybe styles, but you don't add ID's,
     * you set ID's.
     *
     * Calling this with unsupported attribute names will result
     * in something between a no-op and a fatal error. It depends
     * on the attribute class.
     *
     * Lastly, it sucks that I cannot decide whether this should
     * be a public method. We have wrapper methods for adding
     * styles/classes. This is public so that it's possible for you
     * to define your own compound attribute instances and give them
     * an 'add' method, which is a scenario likely to not occur, but
     * I don't want to prevent the possibility.
     *
     * @see self::add_class(), self::add_style()
     *
     * @param $name
     * @param mixed ...$args
     * @return $this
     */
    public function compound_attr_add( $name, ...$args ){
        $this->attr_get_instance( $name )->add( ...$args );
        return $this;
    }

    /**
     * Ie. remove class.
     *
     * @see self::remove_class()
     *
     * @param $name
     * @param mixed ...$args
     * @return $this
     */
    public function _compound_attr_remove( $name, ...$args ){
        // note: attr->remove() may return bool for whether or not the thing
        // was removed. we're returning $this instead to support fluent.
        $this->attr_get_instance( $name )->remove( ...$args );
        return $this;
    }

    /**
     * Ie. has class
     *
     * @see self::has_class()
     *
     * @param $name
     * @param mixed ...$args
     * @return bool|null|mixed
     */
    public function _compound_attr_has( $name, ...$args ){
        return $this->attr_get_instance( $name )->has( ...$args );
    }

    /**
     * Add a class to the list of classes.
     *
     * @param mixed ...$args
     * @return $this
     */
    public function add_class( ...$args ) {
        return $this->compound_attr_add( 'class', ...$args );
    }

    /**
     * Remove a class from the list of classes.
     *
     * @param mixed ...$args
     * @return $this
     */
    public function remove_class( ...$args ) {
        return $this->_compound_attr_remove( 'class', ...$args );
    }

    /**
     * Checks it the class list contains a class.
     *
     * @param mixed ...$args
     * @return bool|mixed|null
     */
    public function has_class( ...$args ) {
        return $this->_compound_attr_has( 'class', ...$args );
    }

    /**
     * Add style(s)
     *
     * @param mixed ...$args
     */
    public function add_style( ...$args ){
        $this->compound_attr_add( 'style', ...$args );
    }
}