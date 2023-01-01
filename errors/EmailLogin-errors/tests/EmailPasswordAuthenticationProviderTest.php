<?php

namespace EmailLogin\Test;

use EmailLogin\EmailPasswordAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\LocalPasswordPrimaryAuthenticationProvider;
use MediaWiki\Auth\PasswordAuthenticationRequest;
use Mockery;
use phpmock\mockery\PHPMockery;

define('DB_MASTER', 0);

class EmailPasswordAuthenticationProviderTest extends \PHPUnit_Framework_TestCase
{
    private $provider;

    protected function setUp()
    {
        Mockery::mock('overload:'.AuthenticationResponse::class)
            ->shouldReceive('newAbstain')
            ->andReturn(new AuthenticationResponse());
        $result = new AuthenticationResponse();
        $result->status = 'PASS';
        Mockery::mock('overload:'.LocalPasswordPrimaryAuthenticationProvider::class)
            ->shouldReceive('beginPrimaryAuthentication')
            ->andReturn($result);
        Mockery::mock('overload:'.PasswordAuthenticationRequest::class);
        $req = new PasswordAuthenticationRequest();
        $req->username = 'foo';
        Mockery::mock('overload:'.AuthenticationRequest::class)
            ->shouldReceive('getRequestByClass')
            ->andReturn($req);
        $row = new \StdClass();
        $row->user_name = 'foo';
        Mockery::mock('overload:DatabaseMysqli')
            ->shouldReceive('select')
            ->andReturn([], [$row]);
        PHPMockery::mock('EmailLogin', 'wfGetDB')->andReturn(new \DatabaseMysqli());
        $this->provider = new EmailPasswordAuthenticationProvider();
    }

    protected function tearDown()
    {
        Mockery::close();
    }

    public function testBeginPrimaryAuthentication()
    {
        //With no row
        $this->assertInstanceOf(AuthenticationResponse::class, $this->provider->beginPrimaryAuthentication([]));
        //With a row
        $this->assertInstanceOf(AuthenticationResponse::class, $this->provider->beginPrimaryAuthentication([]));
    }
}
