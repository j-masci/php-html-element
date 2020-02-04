<?php

function eg(){

    // i wouldn't recommend writing html like this but it is possible
    $el = new Element( 'div', [], [
        new Element( 'p', [], [
            new Element( 'span', [], [ "Some text..."] )
        ])
    ]);

    // perhaps a more likely scenario.
    $get_suggested_html_template = function(){

        $container = new Element( 'div', [ 'class' => 'container' ] );
        $wrapper = new Element( 'div', [ 'class' => 'wrapper' ] );

        // use or inject params? injecting is more flexible, so we will not use "use".
        $render = function( $wrapper, $container ){
            ob_start();
            ?>
            <div class="outer-wrapper">
                <?= $wrapper->child_append( $container )->render(); ?>
            </div>
            <?php
            return ob_get_clean();
        };

        return [ $wrapper, $container, $render ];
    };

    list( $wrapper, $container, $render ) = $get_suggested_html_template();

    // didnt like the original wrapper, replace it entirely.
    $_wrapper = new Element( 'div', [ 'class' => 'my-wrapper' ] );

    // modify the suggested container
    $container->add_class( 'extra-wide' );

    echo $render( $_wrapper, $container );
}



