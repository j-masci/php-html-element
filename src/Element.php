<?php

namespace JMasci\HtmlElement;

/**
 * Extends the base class to add several common helper
 * methods.
 *
 * Class Element
 * @package JMasci\HtmlElement
 */
Class Element extends ElementBaseClass {

    /**
     * Add a class to the list of classes.
     *
     * @param mixed ...$args
     * @return $this
     */
    public function add_class( ...$args ) {
        return $this->_compound_attr_add( 'class', ...$args );
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
        $this->_compound_attr_add( 'style', ...$args );
    }
}