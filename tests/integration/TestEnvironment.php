<?php

namespace Mediawiki\Api\Test;

use Exception;
use Mediawiki\Api\Guzzle\ClientFactory;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\MediawikiFactory;
use Mediawiki\Api\SimpleRequest;

/**
 * @author Addshore
 */
class TestEnvironment {

	public static function newDefault() {
		return new self();
	}

	/** @var MediawikiFactory */
	private $factory;

	/**
	 * Set up the test environment by creating a new API object pointing to a
	 * MediaWiki installation on localhost (or elsewhere as specified by the
	 * MEDIAWIKI_API_URL environment variable).
	 */
	public function __construct() {
		$this->factory = new MediawikiFactory( $this->getApi() );
	}

	/**
	 * Get the MediawikiApi to test against, based on the MEDIAWIKI_API_URL environment variable.
	 * @return MediawikiApi
	 * @throws Exception If the MEDIAWIKI_API_URL environment variable does not end in 'api.php'
	 */
	public function getApi() {
		$apiUrl = getenv( 'MEDIAWIKI_API_URL' );
		if ( empty( $apiUrl ) ) {
			$apiUrl = 'https://deployment.wikimedia.beta.wmflabs.org/w/api.php';
		} elseif ( substr( $apiUrl, -7 ) !== 'api.php' ) {
			$msg = "URL incorrect: $apiUrl"
				." (the MEDIAWIKI_API_URL environment variable should end in 'api.php')";
			throw new Exception( $msg );
		}
		return new MediawikiApi( $apiUrl );
	}

	/**
	 * Get the MediaWiki factory.
	 *
	 * @return MediawikiFactory The factory instance.
	 */
	public function getFactory() {
		return $this->factory;
	}

	/**
	 * Run all jobs in the queue. This only works if the MediaWiki installation has $wgJobRunRate
	 * set to greater than zero.
	 * @todo This and TestEnvironment::getJobQueueLength() should probably not live here.
	 * @return void
	 */
	public function runJobs( $maxJobs = 10 ) {
		$reqestProps = [ 'meta'=>'siteinfo', 'siprop'=>'general' ];
		$siteInfoRequest = new SimpleRequest( 'query', $reqestProps );
		$out = $this->getApi()->getRequest( $siteInfoRequest );
		$mainPageUrl = $out['query']['general']['base'];

		$jobsRun = 0;
		$initialLength = $this->getJobQueueLength( $this->getApi() );
		do {
			$jobsRun++;
			$cf = new ClientFactory();
			$cf->getClient()->get( $mainPageUrl );

			$currentLength = $this->getJobQueueLength( $this->getApi() );
		} while (
			$currentLength > 0 &&
			$jobsRun < $maxJobs &&
			$currentLength < $initialLength - $maxJobs
		);
	}

	/**
	 * Get the number of jobs currently in the queue.
	 * @todo This and TestEnvironment::runJobs() should probably not live here.
	 * @param MediawikiApi $api
	 * @return integer
	 */
	public function getJobQueueLength( MediawikiApi $api ) {
		$req = new SimpleRequest( 'query', [
				'meta'=>'siteinfo',
				'siprop'=>'statistics',
			]
		);
		$out = $api->getRequest( $req );
		return (int) $out['query']['statistics']['jobs'];
	}

}
