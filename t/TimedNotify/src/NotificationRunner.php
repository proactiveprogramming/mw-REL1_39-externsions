<?php

namespace TimedNotify;

use DeferredUpdates;
use MWException;

/**
 * This class is responsible for running notifications.
 */
class NotificationRunner {
	/**
	 * @var NotifierStore The notifier store to use
	 */
	private NotifierStore $notifierStore;

	/**
	 * @var PushedNotificationBucket The bucket of pushed notifications
	 */
	private PushedNotificationBucket $pushedNotificationBucket;

	/**
	 * @var EchoEventCreator Class used for creating events
	 */
	private EchoEventCreator $echoEventCreator;

	/**
	 * @var float The run rate, as configured through $wgTimedNotifyRunRate
	 */
	private float $runRate;

	/**
	 * @param NotifierStore $notifierStore The notifier store to use
	 * @param PushedNotificationBucket $pushedNotificationBucket The pushed notification bucket
	 * @param EchoEventCreator $echoEventCreator Class used for creating events
	 * @param float $runRate The run rate, as configured through $wgTimedNotifyRunRate
	 */
	public function __construct(
		NotifierStore $notifierStore,
		PushedNotificationBucket $pushedNotificationBucket,
		EchoEventCreator $echoEventCreator,
		float $runRate
	) {
		$this->notifierStore = $notifierStore;
		$this->pushedNotificationBucket = $pushedNotificationBucket;
		$this->echoEventCreator = $echoEventCreator;
		$this->runRate = $runRate;
	}

	/**
	 * Run the notifications immediately.
	 *
	 * @return void
	 * @throws MWException
	 */
	public function run(): void {
		foreach ( $this->notifierStore->getNotifiers() as $notifier ) {
			foreach ( $notifier->getNotifications() as $notification ) {
				$notificationId = isset( $notification['id'] ) ?
					sprintf( '%s-%s', $notifier->getName(), $notification['id'] ) :
					null;

				if ( $notificationId !== null && $this->pushedNotificationBucket->isPushed( $notificationId ) ) {
					// If the notification has already been pushed to Echo, we skip it
					continue;
				}

				$data = $notification['data'] ?? [];
				$data['type'] = $notifier->getName();

				// Create and send the notification
				$this->echoEventCreator->create( $data );

				if ( $notificationId !== null ) {
					// Make sure we don't push the notification twice by storing that we have sent it
					$this->pushedNotificationBucket->setPushed( $notificationId );
				}
			}
		}

		// Purge any old pushed pages from the bucket (is there a better place to do this?)
		$this->pushedNotificationBucket->purgeOld();
	}

	/**
	 * Run the notifications in a deferred request.
	 *
	 * @return void
	 */
	public function runDeferred(): void {
		DeferredUpdates::addCallableUpdate( function () {
			$this->run();
		} );
	}

	/**
	 * Run the notifications occasionally (and optionally deferred).
	 *
	 * To prevent slowness and unnecessary overhead, notifications are only run sometimes. This function may therefore
	 * do nothing when called, or it may on occasion run the notifications. How often this function actually runs the
	 * notifications is dependent on the value of $wgTimedNotifyRunRate.
	 *
	 * When the run rate is a number between 0 and 1, the notifications are run, on average, every 1/runRate times this
	 * function is called. For example, if the run rate is 0.01, the notifications are run about every 100 times this
	 * function is called. That is, the probability that the notifications are run when this function is called is 1 in
	 * 100. In theory, the notifications could be run for each time this function is called, or they can never be run.
	 * However, in practice this does not happen. If the run rate is greater or equal to 1, the notifications are always
	 * run when this function is called. If the run rate is set to 0, notifications are never run when this function is
	 * called.
	 *
	 * @param bool $deferred Whether to run the notifications in a deferred update
	 * @return void
	 * @throws MWException
	 * @see NotificationRunner::run() for a function that always immediately runs the notifications
	 * @see NotificationRunner::runDeferred() for a function that always runs the notifications in a deferred request
	 */
	public function runOccasionally( bool $deferred = true ): void {
		if ( $this->runRate === 0.0 ) {
			// Make sure that the notifications never run if the run rate is zero
			return;
		}

		// Generate a random value between 0 inclusive and 1 inclusive
		$rand = lcg_value();

		if ( $rand <= $this->runRate ) {
			$deferred ? $this->runDeferred() : $this->run();
		}
	}
}
