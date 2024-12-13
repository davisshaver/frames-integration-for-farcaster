import sdk from '@farcaster/frame-sdk';
import { showToast } from './utils/toast';

declare global {
	interface Window {
		farcasterWP: {
			debugEnabled: boolean;
			notificationsEnabled: boolean;
		};
	}
}

const addFrame = () => {
	sdk.actions
		.addFrame()
		.then( ( result ) => {
			if ( window.farcasterWP.debugEnabled ) {
				// eslint-disable-next-line no-console
				console.log( 'FWP: addFrame result', result );
			}
			if ( result?.added ) {
				if ( window.farcasterWP.debugEnabled ) {
					// eslint-disable-next-line no-console
					console.log(
						'FWP: Showing you are subscribed to notifications toast'
					);
				}
				showToast( {
					message: 'You are subscribed to notifications.',
					duration: 3000,
				} );
			}
		} )
		.catch( ( error ) => {
			if ( window.farcasterWP.debugEnabled ) {
				// eslint-disable-next-line no-console
				console.error( 'FWP: addFrame error', error );
			}
			showToast( {
				type: 'error',
				message:
					'Error adding frame, addFrame error: ' +
					JSON.stringify( error ),
				duration: 3000,
			} );
		} );
};

const loadSdk = async () => {
	const context = await sdk.context;
	sdk.actions.ready();

	if ( window.farcasterWP.debugEnabled ) {
		// eslint-disable-next-line no-console
		console.log( 'FWP: Frame SDK loaded' );
		// eslint-disable-next-line no-console
		console.log( 'FWP: Context', context );
	}

	if ( ! window.farcasterWP.notificationsEnabled ) {
		if ( window.farcasterWP.debugEnabled ) {
			// eslint-disable-next-line no-console
			console.log( 'FWP: Notifications not enabled' );
		}
		return;
	}

	if ( ! context ) {
		// No context, probably not in a frame.
		if ( window.farcasterWP.debugEnabled ) {
			// eslint-disable-next-line no-console
			console.log( 'FWP: No context, probably not in a frame' );
		}
		return;
	}

	if (
		context?.client?.notificationDetails ||
		context?.location?.type === 'notification'
	) {
		if ( window.farcasterWP.debugEnabled ) {
			// eslint-disable-next-line no-console
			console.log( 'FWP: Showing thanks for being a susbcriber toast' );
		}
		showToast( {
			message: 'Thanks for being a subscriber!',
			duration: 3000,
		} );
		return;
	}

	if ( window.farcasterWP.debugEnabled ) {
		// eslint-disable-next-line no-console
		console.log( 'FWP: Calling add frame' );
	}

	if ( context?.client?.added ) {
		if ( window.farcasterWP.debugEnabled ) {
			// eslint-disable-next-line no-console
			console.log( 'FWP: Already added frame, trying prompt' );
		}
		// @TODO: Right now, addFrame will immediately resolve if the
		// user has added the frame but not subscribed. So we can leave
		// this interaction out until the SDK is updated to handle this.
		// showToast( {
		// 	message: 'Want to receive notifications?',
		// 	duration: 10000,
		// 	buttonText: 'Subscribe',
		// 	onButtonClick: addFrame,
		// } );
		return;
	}
	addFrame();
};

if ( window.farcasterWP.debugEnabled ) {
	// eslint-disable-next-line no-console
	console.log( 'FWP: Loading SDK' );
}

loadSdk();
