<?php

namespace EmailLogin;

use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\LocalPasswordPrimaryAuthenticationProvider;
use MediaWiki\Auth\PasswordAuthenticationRequest;

class EmailPasswordAuthenticationProvider extends LocalPasswordPrimaryAuthenticationProvider
{
    public function beginPrimaryAuthentication(array $reqs)
    {
        $req = AuthenticationRequest::getRequestByClass($reqs, PasswordAuthenticationRequest::class);

        $dbr = wfGetDB(DB_MASTER);
        $rows = $dbr->select(
            'user',
            ['user_email', 'user_name'],
            ['user_email' => $req->username],
            __METHOD__
        );

        foreach ($rows as $row) {
            $req->username = $row->user_name;

            $result = parent::beginPrimaryAuthentication([$req]);
            if ($result->status == 'PASS') {
                return $result;
            }
        }

        return AuthenticationResponse::newAbstain();
    }
}
