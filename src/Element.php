<?php

namespace JMasci\HtmlElement;

/**
 * TODO: INCOMPLETE
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
     * todo: instances also store the name. which name is the single source of truth?
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
     * todo: this is somewhat experimental. I need to put some thought into how we handle object references.
     *
     * Inserts an element before this element, causing "this" to now be a fragment. The pointer
     * to the new self which was copied as a child element is now lost, although, technically
     * exists in $this->children[1].
     *
     * The thing to consider is if we do $this->before( $new )->add_class(), what are
     * we adding the class to, the new fragment or the previous self which is now a
     * child of the fragment?
     *
     * todo: make public if we decide to use this
     *
     * @param $new_ele
     * @return $this
     */
    private function before( $new_ele ){

        $this->children = [
            $new_ele,
            new Element( $this->tag, $this->atts, $this->children )
        ];

        // order matters
        $this->tag = null;
        $this->atts = [];
        return $this;
    }

    /**
     * @return bool
     */
    public function is_fragment(){
        return empty( $this->tag ) && empty( $this->atts );
    }

    /**
     * @param array $atts
     * @return array
     */
    protected static function get_atts_array( array $atts ) {
        return array_map( function( $att ){
            if ( is_scalar( $att ) ) {
                return $att;
            } else {
                // letting it fail for now if $att is an array or something
                return $att->call( 'get' );
            }
        }, $atts );
    }

    /**
     * @param array $children
     * @return false|string
     */
    protected static function render_children( array $children ){

        ob_start();

        // todo: "\r\n" and all that shit? Doing so could break non-html string children
        foreach ( $children as $child ){

            // storing children as strings of HTML is fine
            if ( is_scalar( $child ) ) {
                echo $child;
            } else {
                // recursive call.
                // if $child does not have a render method, letting it fail.
                echo $child->render();
            }
        }

        return ob_get_clean();
    }

    /**
     * Now for the fun part...
     *
     * ok that was easier than I thought.
     */
    public function render(){
        if ( $this->is_fragment() ) {
            return static::render_children( $this->children );
        } else {
            return El::get( $this->tag, static::render_children( $this->children ), self::get_atts_array( $this->atts ), true );
        }
    }

    /**
     * Empties the inner contents of the element.
     */
    public function empty(){
        $this->children = [];
    }

    /**
     * Returns an instance of self that has no tag or attributes, just
     * children. Supporting fragments will no doubt make some operations
     * a bit harder, but I think its still good.
     *
     * @param array $children
     * @return static
     */
    public static function fragment( array $children ){
        return new static( null, [], $children );
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
     * Gets the attribute instance, creating it if it does not already exist.
     *
     * @param $name
     * @return ElementAttribute
     */
    public function attr_get_instance( $name ) {
        return isset( $this->atts[$name] ) ? $this->atts[$name] : static::build_attribute_instance( $name );
    }

    /**
     * You could override this in your subclass.
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
        return $this->attr_get_instance( $name )->call( 'get' );
    }

    /**
     * Set the value of an attribute. In most or all cases, just pass
     * in a $name and a $value. Some attributes may have variadic
     * setter functions.
     *
     * @param $name
     * @param mixed ...$args
     * @return mixed
     */
    public function attr_set( $name, ...$args ){
        return $this->attr_get_instance( $name )->call( 'set',  ...$args );
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
     * @param $name
     * @param mixed ...$args
     */
    public function compound_attr_add( $name, ...$args ){
        $this->attr_get_instance( $name )->call( 'add', ...$args );
    }

    /**
     * Ie. remove class.
     *
     * @see self::compound_attr_add()
     *
     * @param $name
     * @param mixed ...$args
     */
    public function compound_attr_remove( $name, ...$args ){
        $this->attr_get_instance( $name )->call( 'remove', ...$args );
    }

    /**
     * Ie. has class.
     *
     * @see self::compound_attr_add()
     *
     * @param $name
     * @param mixed ...$args
     */
    public function compound_attr_has( $name, ...$args ){
        $this->attr_get_instance( $name )->call( 'has', ...$args );
    }

    /**
     * Add a class to the list of classes.
     *
     * todo: it seems redundant to have both this method and the method which it wraps.
     *
     * @param mixed ...$args
     */
    public function add_class( ...$args ) {
        $this->attr_get_instance( 'class' )->call( 'add', ...$args );
    }

    /**
     * Remove a class from the list of classes.
     *
     * @param mixed ...$args
     */
    public function remove_class( ...$args ) {
        $this->compound_attr_remove( 'class', ...$args );
    }

    /**
     * @param mixed ...$args
     */
    public function has_class( ...$args ) {
        $this->compound_attr_has( 'class', ...$args );
    }

    /**
     * @param mixed ...$args
     */
    public function add_style( ...$args ){
        $this->compound_attr_add( 'style', ...$args );
    }
}


/**
 * Interface is useless to us due to method arguments which
 * often are variadic but we should not force anyone to use
 * them as such. There is no reason why attr_set should not
 * be able to accept just ( $name, $value ) in the function
 * declaration.
 *
 * todo: remove this if we're sure we'll never use it.
 *
 * Interface ElementInterface
 * @package JMasci\HtmlElement
 */
//Interface ElementInterface{
//
//    function tag_get();
//    function tag_set( $value );
//
//    function attr_get( $name );
//    function attr_set( $name, ...$args );
//
//    function attr_delete( $name ); # ie. <input> vs. attr_delete_value
//    function attr_reset( $name ); # ie. <input name="">
//
//    function attr_exists( $name ); # true if <input name="">
//
//    function compound_attr_add( $name, ...$args );
//    function compound_attr_remove( $name, ...$args );
//    function compound_attr_has( $name, ...$args );
//
//    function add_class( ...$args );
//    function remove_class( ...$args );
//    function has_class( ...$args );
//
//    function add_style( ...$args );
//}