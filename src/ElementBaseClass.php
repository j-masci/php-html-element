<?php

namespace JMasci\HtmlElement;

/**
 * Uses the trait and defines the constructor.
 *
 * It's possible to use this directly, but we will extend it to add
 * common helper methods that we will normally want to use, while
 * allowing for the class to be used without those methods.
 *
 * Class ElementBaseClass
 * @package JMasci\HtmlElement
 */
Class ElementBaseClass{

    /**
     * Majority of functionality is in here.
     */
    use T_ElementBaseClass;

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
}
