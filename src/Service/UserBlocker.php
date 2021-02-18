<?php

namespace Addwiki\Mediawiki\Api\Service;

use Addwiki\Mediawiki\Api\Client\SimpleRequest;
use Addwiki\Mediawiki\DataModel\User;
use InvalidArgumentException;

/**
 * @access private
 *
 * @author Addshore
 */
class UserBlocker extends Service {

	/**
	 * @since 0.3
	 *
	 * @param User|string $user
	 * @param array $extraParams
	 *
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	public function block( $user, array $extraParams = [] ) {
		if ( !$user instanceof User && !is_string( $user ) ) {
			throw new InvalidArgumentException( '$user must be either a string or User object' );
		}

		if ( $user instanceof User ) {
			$user = $user->getName();
		}

		$params = [
			'user' => $user,
			'token' => $this->api->getToken( 'block' ),
		];

		$params = array_merge( $extraParams, $params );

		$this->api->postRequest( new SimpleRequest( 'block', $params ) );
		return true;
	}

}
