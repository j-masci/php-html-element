<?php

namespace JMasci\HtmlElement\Tests;
use JMasci\HtmlElement\El;

Class ElTest extends \PHPUnit_Framework_TestCase{

    public function test_get(){
        $html = El::get( 'div', '', [], true );
        $this->assertTrue( "<div></div>" === trim( $html ) );
    }

    public function test_tag_containing_classes(){
        $this->assertEquals( [ "div", "class-1 class-2"], El::parse_tag_and_classes( "div.class-1.class-2" ) );
    }

    public function test_tag_containing_an_id(){
        $this->assertEquals( [ "div", "the-id" ], El::parse_tag_and_id( "div#the-id" ) );
    }

    public function test_tag_containing_classes_and_id(){
        $this->assertEquals( [ "div", "the-id", "class-1 class-2" ], El::parse_tag_selector( "div.class-1#the-id.class-2" ) );
    }

    public function test_tag_with_id_ignores_more_than_1_id(){
        $this->assertEquals( [ "div", "the-id" ], El::parse_tag_and_id( "div#the-id#another-id" ) );
    }

    public function test_inner_html(){

        $actual = El::get( 'div', 'Hello...', [], true );

        // ignore line breaks for this test
        $_actual = trim( str_replace( "\r\n", "", $actual ) );

        $this->assertEquals( "<div>Hello...</div>", $_actual );
    }

    public function test_parse_class_sanitizes_some_things(){

        $unclean = "class-<script>";

        $this->assertNotSame( $unclean, El::parse_classes( $unclean ));

        // checks that sanitation is applied regardless of input format
        $this->assertSame( El::parse_classes( $unclean ), El::parse_classes( [ $unclean ] ) );
        $this->assertSame( El::parse_classes( $unclean ), El::parse_classes( [ $unclean => true ] ) );
    }

    public function test_parse_classes(){

        $input_1 = "class-1 class-2";

        $input_2 = [ "class-1", "class-2", "", false ];

        $input_3 = [
            'class-1' => true,
            'class-2' => 1,
            'class-3' => false,
        ];

        $expected = "class-1 class-2";

        $this->assertEquals( $expected, El::parse_classes( $input_1 ));
        $this->assertEquals( $expected, El::parse_classes( $input_2 ));
        $this->assertEquals( $expected, El::parse_classes( $input_3 ));
    }

    public function testClassAttr1(){

        $html = El::get( 'div', '', [ 'class' => 'class-1' ], true );

        // I'm not expecting single quotes, but will not dis-allow it.
        $_html = str_replace( "'", "\"", $html );
        $this->assertTrue( "<div class=\"class-1\"></div>" === trim( $_html ) );
    }

    public function testKeyOnlyAttributes(){
        $this->assertEquals( "<thing required>", trim( El::open( "thing", [ "required" => true ] ) ) );
    }
}