<?php
/**
 * Custom Exceptions for the CloudFiles API
 *
 * Requres PHP 5.x (for Exceptions and OO syntax)
 *
 * See COPYING for license information.
 *
 * @author Eric "EJ" Johnson <ej@racklabs.com>
 * @copyright Copyright (c) 2008, Rackspace US, Inc.
 * @package php-cloudfiles-exceptions
 */

/**
 * Custom Exceptions for the CloudFiles API
 * @package php-cloudfiles-exceptions
 */
class SyntaxException extends Exception { }
class AuthenticationException extends Exception { }
class InvalidResponseException extends WikiaException { }
class NonEmptyContainerException extends Exception { }
class NoSuchObjectException extends Exception { }
class NoSuchContainerException extends Exception { }
class NoSuchAccountException extends Exception { }
class MisMatchedChecksumException extends Exception { }
class IOException extends Exception { }
class CDNNotEnabledException extends Exception { }
class BadContentTypeException extends Exception { }
class InvalidUTF8Exception extends Exception { }
class ConnectionNotOpenException extends Exception { }

class SwiftRetryException extends Exception {} # Wikia change