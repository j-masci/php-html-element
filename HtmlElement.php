<?php

namespace JMasci;

/**
 * Methods for rendering HTML elements (@see self::get(), self::get_strict())
 *
 * Class HtmlElement
 * @package JMasci
 */
Class HtmlElement{

    /**
     * When true, does <img />
     *
     * When false, just does <img>
     *
     * If you need to change, I recommend extending this class and having
     * 2 classes, one with this true, another with this false.
     *
     * @var bool
     */
    public static $do_self_closing_tags = true;

    /**
     * Returns HTML element from input parameters. Sanitizes the tag and
     * all attributes. A few notes:
     *
     * - if $tag and $atts both specify class, they are merged.
     * - if $tag and $atts both specify an ID, $atts is used.
     * - $tag can specify tag, class, ID, but nothing else.
     * - $atts['class'] and $atts['style'] are given special treatment if they are arrays.
     * - all other $atts that are arrays (or objects) are json encoded by default.
     *
     * Examples:
     *
     * self::get ( 'div.container', 'some html...', [ 'data-thing' => 123 ] )
     *
     * @param $tag - ie "div", "p", "div.container", etc.
     * @param string $inner - inner HTML
     * @param array $atts - array of HTML element attributes
     * @param bool $close - whether or not to close the tag
     * @return string
     */
    public static function get( $tag, $inner = '', array $atts = [], $close = true ){

        list( $tag, $id, $class ) = self::parse_tag_selector( $tag );

        self::merge_attribute( 'class', $class, $atts );
        self::merge_attribute( 'id', $id, $atts );

        foreach ( $atts as $attr_name => $attr_value ) {
            $atts[self::sanitize_attribute_name( $attr_name )] = self::parse_attribute_value( $attr_name, $attr_value );
        }

        return self::get_strict( self::sanitize_tag( $tag ), self::parse_inner_html( $inner ), $atts, $close );
    }

    /**
     * Renders an HTML element with very strict limitations on the format of input parameters.
     *
     * It also will not sanitize any data in (almost) any way, apart from possibly, failing
     * silently.
     *
     * $atts must be an array of strings (or boolean values) (no arrays for classes/styles etc.)
     *
     * $tag must be a sanitized tag name.
     *
     * $inner must be a string (not a callable).
     *
     * This is public because it might be possible that self::get() will occassionally
     * over sanitize your data. self::get() should normally get the preferred method.
     *
     * In case its not obvious, self::get() sanitizes and validates input and then calls this.
     *
     * @param $tag
     * @param string $inner
     * @param array $atts
     * @param bool $close
     * @return string
     */
    public static function get_strict($tag, $inner = '', array $atts = [], $close = true ) {

        $self_closing = static::$do_self_closing_tags && self::is_self_closing( $tag );

        $el = "<$tag";

        $pairs = $singles = [];
        foreach ( $atts as $k => $v ) {
            if ( is_string( $k ) && $k && $v === true ) {
                $singles[] = $k;
            } else {
                $pairs[] = "$k=\"$v\"";
            }
        }

        // add all attributes, with "singles" like required/checked at the end
        $el .= trim( " " . implode( " ", $pairs + $singles ) );

        // close the opening tag
        $el .= $self_closing ? " />" : ">";

        // inner html
        $el .= $inner;

        // maybe add the closing tag
        if ( $close && ! $self_closing ) {
            $el .= "</$tag>";
        }

        return $el;
    }

    /**
     * @param $tag
     * @param $atts
     * @return string
     */
    public static function open( $tag, $atts ) {
        return self::get( $tag, "", $atts, false );
    }

    /**
     * @param $tag
     * @param $atts
     * @return string
     */
    public static function open_strict( $tag, $atts ) {
        return self::get_strict( $tag, "", $atts, false );
    }

    /**
     * Seems redundant, but, we have self::open, so...
     *
     * @param $tag
     * @return string
     */
    public static function close( $tag ) {

        $_tag = self::sanitize_tag( $tag );

        if ( self::$do_self_closing_tags && self::is_self_closing( $_tag ) ) {
            return "";
        }

        return "</$_tag>";
    }

    /**
     * ie. "div.container.whatever#id' => [ 'div', 'id', 'container whatever' ]
     *
     * Supports HTML tag, classes, and ID. Does not support data-attributes etc.
     *
     * @param $tag
     * @return array
     */
    public static function parse_tag_selector( $tag ){

        // todo: regex is not implement yet because its hard.
        $class = "";
        $id = "";

        return[
            $tag,
            $id,
            $class,
        ];
    }

    /**
     * Get a sanitized CSS class string from an array or string.
     *
     * ie. "class_1 class_2 class_3"
     *
     * or [ 'class_1', 'class_2', 'class_3' ]
     *
     * or [ 'class_1' => true, 'class_2' => false, 'class_3' => true ]
     *
     * Never actually used the 3rd form but I suppose it might be useful.
     *
     * @param array|string $class
     * @return string
     */
    public static function parse_classes($class ) {

        if ( is_array( $class ) ) {

            $arr = [];

            foreach ( $class as $k => $v ) {

                if ( $v && is_string( $k ) && $k ) {
                    $arr[] = self::parse_classes( $k );
                } else if( $v ){
                    $arr[] = self::parse_classes( $v );
                }
            }

            // every array element is sanitized by this point.
            return trim( implode( " ", array_filter( $arr ) ) );

        } else if ( is_string( $class ) ) {
            return self::sanitize_class_str( $class );
        } else {
            return "";
        }
    }

    /**
     * Filters, validates, and sanitizes (to some degree) an attribute value,
     * based on the name. This is primarily what let's us be flexible with
     * input types. ie. array or string for styles/classes.
     *
     * @param $name
     * @param $value
     * @return string
     */
    public static function parse_attribute_value( $name, $value ) {

        switch( strtolower( $name ) ) {
            case 'class':
                return self::parse_classes( $value );
                break;
            case 'id':
                return self::sanitize_class_str( $value );
                break;
            case 'style':
                if ( is_array( $value ) ) {
                    // todo: THIS!
                    return "";
                } else {
                    return addslashes( $value );
                }
                break;
            default:

                if ( is_array( $value ) || is_object( $value ) ) {
                    return self::json_encode_for_html_attr( $value );
                } else if ( is_bool( $value ) || is_null( $value ) || is_int( $value ) ){
                    // have to be careful not to break certain types. this is very important
                    // for singular attribute values like required/checked.
                    return $value;
                } else {
                    // default sanitation...
                    return addslashes( $value );
                }

                break;
        }
    }

    /**
     * Allows $inner to be a string or a callable.
     *
     * If it's a callable, will return what it (prints/outputs/both?)
     *
     * If its a string which is the name of the function, the string
     * is returned and the function is not invoked.
     *
     * Allows you to pass an anonymous function to self::el()
     *
     * @param $inner
     * @return string
     */
    protected static function parse_inner_html( $inner ){

        $ret = '';

        // possible HTML in between the opening and closing tag.
        if ( $inner ) {

            // careful not to invoke strings like "some_function_name"
            if ( is_string( $inner ) ) {
                $ret .= $inner;
            } else if ( is_callable( $inner ) ) {

                // todo: should the callable return or echo its output or both? both seems weird,
                // todo: but is there any reason not to? it seems useful to consider both.
                ob_start();
                $ret .= call_user_func( $inner );
                $ret .= ob_get_clean();
            }
        }

        return $ret;
    }

    /**
     * For lack of a better name, this function handles the case where we
     * are provided the same parameter twice, once in the attributes array
     * and once elsewhere.
     *
     * This really annoying scenario occurs because we want to be able to use
     * selector tags like 'div.container', but also have $atts = [ 'class' => 'class' ]
     *
     * The unfortunate result is not only that we have to take care of this, but
     * that we have to take care of it in the same way in multiple places.
     *
     * It gets even more annoying for class because class in 2 places can be a
     * string or an array.
     *
     * The default behaviour is to give precedence to the non false-like value,
     * if any is provided (this makes sense for the ID attribute for example).
     * If 2 non false-like values exist, then $atts takes precedence.
     *
     * Only classes and styles should have an append type functionality. Everything else
     * will choose one value or the other.
     *
     * @param $name - ie. 'class'
     * @param $value - ie. 'class_1' or [ 'class_1' ]
     * @param array $atts - ie. [ 'class' => 'class_2', 'id' => 'id123' ]
     */
    public static function merge_attribute($name, $value, array &$atts = [] ){

        switch( $name ) {
            case 'class':

                // this gets pretty ugly but I guess this is just what we have to do.
                if ( $value ) {
                    if ( isset( $atts['class'] ) ) {
                        if ( is_array( $atts['class'] ) ) {
                            $atts['class'][] = $value;
                        } else {
                            $atts['class'] .= trim( " " . self::parse_classes( $value ) );
                        }
                    } else {
                        $atts['class'] = $value;
                    }
                }

                break;
            case 'style':

                // todo: styles....

                break;
            // default will often occur with 'id'.
            default:

                // override $atts only if $attribute_value exists and $atts[$attribute_name] does not.
                if ( $value ) {
                    if ( ! isset( $atts[$name] ) || ! $atts[$name] ) {
                        $atts[$name] = $value;
                    }
                }

                break;
        }
    }

    /**
     * Sanitize HTML tag.
     *
     * @param $tag
     * @return string
     */
    public static function sanitize_tag( $tag ) {
        return preg_replace( "/[^A-Za-z]/", "", $tag );
    }

    /**
     * Sanitize HTML class attribute.
     *
     * Note that we use this function for several other things that follow
     * similar rules.
     *
     * @param $class
     * @return string
     */
    public static function sanitize_class_str($class ) {
        return preg_replace( "/[^A-Za-z0-9_\-\s]/", "", $class );
    }

    /**
     * Sanitize the key portion of an HTML attribute, ie.
     * "class", "id", "data-something"
     *
     * @param $name - ie. "data-something", or "id", etc.
     * @return string
     */
    public static function sanitize_attribute_name( $name ) {
        return preg_replace( "/[^A-Za-z0-9_\-]/", "", $name );
    }

    /**
     * @param $tag
     * @return bool
     */
    public static function is_self_closing( $tag ) {
        // todo: maybe make this a static var or check for other self closing tags
        return in_array( $tag, [ 'input','img','hr','br','meta','link' ] );
    }

    /**
     * Safely JSON encode anything into an HTML attribute, including arrays, objects, or even html.
     *
     * @param $thing
     * @return string
     */
    public static function json_encode_for_html_attr( $thing ) {
        return htmlspecialchars( \json_encode( $thing ), ENT_QUOTES, 'UTF-8' );
    }
}