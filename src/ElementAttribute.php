<?php

namespace JMasci\HtmlElement;

/**
 * Class HtmlElementAttributeSuperclaasdss
 * @package JMasci\HtmlElement
 */
Class ElementAttribute{

    /**
     * todo: storing name in the instance is broken if the name is also stored in an array key of Element::$atts (no logical thing to do if they do not match).
     *
     * @var
     */
    public $name;

    /**
     * encapsulated for special handling based on the
     * attribute name.
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
     * Not meant to be called directly in most cases.
     *
     * @see self::build_via_name()
     *
     * HtmlElementAttribute constructor.
     * @param $name
     * @param $value
     * @param array $methods
     */
    public function __construct( $name, $value, array $methods = [] ){

        $this->name = $name;
        $this->methods = $methods;

        // todo: use the setter here or no?
        $this->call( 'set', $value );
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
     * Builds an instance of self for handling the
     * 'class' attribute. Invoked dynamically.
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
                return $this;
            },
            'add' => function( $class ) {
                $this->value .= trim( " " . trim( $class ) );
                return $this;
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
                return $this;
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
                return $this;
            }
        ] );
    }

    /**
     * Returns true if a callback via name is registered.
     *
     * You can check this before calling $this->call(), although,
     * I don't know if we normally will.
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
     * @param $name
     * @param mixed ...$args
     * @return mixed
     */
    public function call( $name, ...$args ){
        // let it fail.
        \Closure::bind( $this->methods[$name], $this );
        return call_user_func( $this->methods[$name], ...$args );
    }
}