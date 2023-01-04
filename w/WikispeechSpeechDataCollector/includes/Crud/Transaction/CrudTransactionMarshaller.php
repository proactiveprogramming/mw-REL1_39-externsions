<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud\Transaction;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

/**
 * @todo This is part of an internal development API. It should be removed before deployment.
 * @since 0.1.0
 */
interface CrudTransactionMarshaller {

	/**
	 * @param mixed $serializedRequest
	 * @return CrudTransactionRequest
	 * @throws CrudTransactionException
	 */
	public function deserializeRequest( $serializedRequest ): CrudTransactionRequest;

	/**
	 * @param CrudTransactionResponse $response
	 * @return mixed
	 * @throws CrudTransactionException
	 */
	public function serializeResponse( CrudTransactionResponse $response );

}
