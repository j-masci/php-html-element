<?php

namespace JMasci\HtmlElement;

/**
 * An HTML Element Attribute class that holds dynamic accessor
 * methods.
 *
 * This gives you flexibility to define "methods" based
 * on their attribute name.
 *
 * A 'class' or 'style' attribute could have an 'add' method,
 * whereas an 'id' attribute should only use 'set'.
 *
 * All instances must implement 'set' and 'get' in $this->methods.
 *
 * All instances should return string or scalar values from $this->methods['get'].
 *
 * Class HtmlElementAttributeSuperclaasdss
 * @package JMasci\HtmlElement
 */
Class ElementAttribute{

    /**
     * Ie. 'class', 'id', 'type'.
     *
     * Note that in the context of an Element, attribute names are also stored in the keys of an array.
     *
     * If you change this value here, you may want to see if Element even cares.
     *
     * @var
     */
    private $name;

    /**
     * The internally stored attribute.
     *
     * Invoke $this->call( 'get' ) to retrieve its value, which *should*
     * always produce a string or scalar value, even if this is an array
     * for certain attributes types (ie. class, style).
     *
     * If you decide to store the value internally as an array, please
     * ensure that $this->call( 'set', $this->call( 'get' ) ) is more
     * or less an immutable operation.
     *
     * @var
     */
    private $value;

    /**
     * An array of dynamic (overridable) methods, invoked via $this->call().
     *
     * Methods (generally) act upon $this->value.
     *
     * Methods in the array are determined by $this->name
     * upon building the class.
     *
     * Do not invoke any methods directly from within here.
     * Use the call method instead which will bind $this
     * to the closure.
     *
     * Might still leave this as public in case it offers any benefit.
     *
     * @var array
     */
    public $methods = [];

    /**
     * The constructor requires valid parameters.
     *
     * Build them before you pass them in, not after.
     *
     * Methods cannot be empty and requires 'get' and 'set'
     * callbacks. The 'get' callback must return a scalar
     * value.
     *
     * @see self::build_via_name()
     * @see self::__default_instance__()
     *
     * HtmlElementAttribute constructor.
     * @param $name
     * @param $value
     * @param array $methods
     */
    public function __construct( $name, $value, array $methods = [] ){

        $this->set_name( $name );

        // ensure we setup method before $this->call
        $this->methods = $methods;

        // I somewhat dislike this fail-silent sort of fallback logic,
        // but, I think it might be the best thing to do anyways.
        if ( $this->callback_exists( 'set' ) ) {
            $this->call( 'set', $value );
        } else {
            $this->value = $value;
        }
    }

    /**
     * Builds an instance with different accessors based on $name.
     *
     * $name could be class, id, name, type, etc.
     *
     * @param $name
     * @return mixed
     */
    public static function get_instance_via_name( $name ){

        $method = "__instance_for__" . $name;

        if ( $name && method_exists( static::class, $method ) ) {
            return call_user_func( [ static::class, $method ] );
        } else {
            return self::__default_instance__( $name );
        }
    }

    /**
     * An instance of self with trivial getters and setters
     * for $this->value. The default instance for most attribute
     * names.
     *
     * @param $name
     * @return ElementAttribute
     */
    public static function __default_instance__( $name ) {
        return new self( $name, null, [
            'get' => function(){
                return $this->value;
            },
            'set' => function( $value ){
                $this->value = $value;
            }
        ] );
    }

    /**
     * Builds an instance of self for handling the
     * 'class' attribute. Invoked dynamically.
     *
     * This particular instance stores its value as a string, but,
     * that fact should not be exposed to whomever invokes the methods.
     *
     * @return ElementAttribute
     */
    public static function __instance_for__class(){
        return new self( 'class', [], [
            'get' => function(){
                return $this->value;
            },
            'set' => function( $value ){
                $this->value = $value;
            },
            'add' => function( $class ) {
                $this->value .= trim( " " . trim( $class ) );
            },
            'remove' => function( $class ) {
                if ( strpos( " {$this->value} ", " $class " ) !== false ) {
                    $this->value = trim( str_replace( " $class ", " ", " {$this->value} " ) );
                    return true;
                } else {
                    return false;
                }
            },
            'has' => function( $class ) {
                return strpos( " {$this->value} ", " $class " ) !== false;
            },
        ] );
    }

    /**
     * Builds an instance of self for handling the style attribute.
     *
     * Invoked dynamically.
     *
     * This particular set of callables deals with styles as strings.
     *
     * @return ElementAttribute
     */
    public static function __instance_for__style(){
        return new self( 'style', "", [
            // calling set with get might not be an immutable operation if the initial value was null
            // or was missing a semi-colon at the end.
            'get' => function(){
                return $this->value ? rtrim( $this->value, ';' ) . ';' : '';
            },
            'set' => function( $value ){
                $this->value = $value ? rtrim( $value, ';' ) . ';' : '';
            },
            'add' => function( $value ) {

                // this simplistic approach should be good enough while not trying to stop
                // you from breaking things if you try hard enough to do so.

                // force semi-colon before new value
                if ( $this->value ) {
                    $this->value = rtrim( $this->value, ';' ) . ';';
                }

                if ( $value ) {
                    // force semi-colon after new value
                    $this->value .= rtrim( $value, ';' ) . ';';
                }
            },
        ] );
    }

    /**
     * @return mixed
     */
    public function get_name(){
        return $this->name;
    }

    /**
     * Update the attributes name.
     *
     * You'll not likely need this, but it may prove beneficial to clone
     * attributes once in a while and then change their name. I can hardly
     * think of a scenario for this, but i'm sure there is one.
     *
     * WARNING: There can be an inconsistency between the name stored
     * in this object, and the name stored in the array keys of Element::$atts.
     * When there is, you need to check your Element implementation to see
     * how this is handled.
     *
     * @param $name
     */
    public function set_name( $name ) {
        $this->name = $name;
    }

    /**
     * Invokes the 'add' callback, if it exists.
     *
     * Likely defined for 'class' and 'style'.
     *
     * @param mixed ...$args
     */
    public function add( ...$args ){
        if ( $this->callback_exists( 'add' ) ) {
            $this->_call_dynamic_method( 'add', ...$args );
        }
    }

    /**
     * Invokes the 'remove' callback, if it exists.
     *
     * Likely defined on 'class' only.
     *
     * @param mixed ...$args
     * @return mixed
     */
    public function remove( ...$args ){
        if ( $this->callback_exists( 'remove' ) ) {
            return $this->_call_dynamic_method( 'remove', ...$args );
        }
    }

    /**
     * Invokes the 'has' callback, if it exists.
     *
     * Likely defined on 'class' only.
     *
     * @param mixed ...$args
     * @return mixed
     */
    public function has( ...$args ){
        if ( $this->callback_exists( 'has' ) ) {
            return $this->_call_dynamic_method( 'has', ...$args );
        }
    }

    /**
     * Invokes the 'set' callback.
     *
     * @param mixed ...$args
     * @return mixed
     */
    public function set( ...$args ){
        // Required callback. Let it fail if it does not exist.
        return $this->_call_dynamic_method( 'set', ...$args );
    }

    /**
     * Invokes the 'get' callback.
     *
     * @return mixed
     */
    public function get(){
        // Required callback. Let it fail if it does not exist.
        return $this->_call_dynamic_method( 'get' );
    }

    /**
     * Returns true if a callback via name is registered.
     *
     * You can check this before calling $this->call(), which may
     * perform a no-op if the callback does not exist.
     *
     * @param $name
     * @return bool
     */
    public function callback_exists( $name ) {
        $cb = isset( $this->methods[$name] ) ? $this->methods[$name] : null;
        return $cb && is_object( $cb ) && is_callable( $cb );
    }

    /**
     * Invoke a function stored in $this->methods.
     *
     * You should always use the wrapper methods for those which are available.
     *
     * Since I cannot predict all possible methods you may want to dynamically include
     * in your attributes, this method is still public.
     *
     * @see self::get(), self::set(), self::add(), self::remove(), self::has()
     *
     * @param $name
     * @param mixed ...$args
     * @return mixed
     */
    public function _call_dynamic_method( $name, ...$args ){

        // We check $this->callback_exists() in some cases, before $this->_call().
        // If we get to here and the method is not defined, let it fail.
        \Closure::bind( $this->methods[$name], $this );
        return call_user_func( $this->methods[$name], ...$args );
    }

    /**
     * Magic method call is designed for dynamic "methods" registered
     * to attributes with names that we cannot predict.
     *
     * For the sake of auto completion, we manually defined
     * set, get, add, remove, has. But if your attribute has
     * something else, then implementing this magic method allows
     * you to call that method directly on the object.
     *
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call( $method, $args ) {

        if ( $this->callback_exists( $method ) ) {
            return $this->_call_dynamic_method( $method, ...$args );
        }

        throw new Exception( "Method or dynamic method does not exist" );
    }
}