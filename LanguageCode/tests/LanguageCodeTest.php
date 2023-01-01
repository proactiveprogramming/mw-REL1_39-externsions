<?php

namespace LanguageCode\Test;

use LanguageCode\LanguageCode;
use Mockery;

class LanguageCodeTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        Mockery::mock('overload:Language')
            ->shouldReceive('getCode')
            ->andReturn('en');
        Mockery::mock('overload:Title')
            ->shouldReceive('getPageLanguage')
            ->andReturn(new \Language());
        Mockery::mock('overload:RequestContext')
            ->shouldReceive('getLanguage')
            ->andReturn(new \Language());
        Mockery::mock('overload:Parser')
            ->shouldReceive('getTitle')
            ->andReturn(new \Title());
        Mockery::mock('overload:Article')
            ->shouldReceive('getContext')
            ->andReturn(new \RequestContext());
    }

    protected function tearDown()
    {
        Mockery::close();
    }

    public function testRegisterMagicWord()
    {
        $variableIds = [];
        LanguageCode::registerMagicWord($variableIds);
        $this->assertContains('userlanguage', $variableIds);
        $this->assertContains('pagelanguage', $variableIds);
    }

    public function testGetMagicWord()
    {
        $ret = '';
        LanguageCode::getMagicWord(new \Parser(), [], 'userlanguage', $ret);
        $this->assertEquals($ret, 'en');
        LanguageCode::getMagicWord(new \Parser(), [], 'pagelanguage', $ret);
        $this->assertEquals($ret, 'en');
    }
}
