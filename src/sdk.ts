import sdk from '@farcaster/frame-sdk';
import { showToast } from './utils/toast';
import { renderTippingModal } from './components/TippingModal';

declare global {
	interface Window {
		farcasterWP: {
			debugEnabled: boolean;
			notificationsEnabled: boolean;
			castText: string;
			tippingAddress: string;
			tippingAmounts: number[];
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
					onDismiss: () => renderTippingModal(),
				} );
			} else {
				renderTippingModal();
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
			onDismiss: () => renderTippingModal(),
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
			console.log( 'FWP: Already added frame, skipping prompt' );
		}
		// @TODO: Right now, addFrame will immediately resolve if the
		// user has added the frame but not subscribed. So we can leave
		// this interaction out until the SDK is updated to handle this.
		// For now, we will show the tipping modal.
		// showToast( {
		// 	message: 'Want to receive notifications?',
		// 	duration: 10000,
		// 	buttonText: 'Subscribe',
		// 	onButtonClick: addFrame,
		// } );
		renderTippingModal();
		return;
	}

	const handleScroll = () => {
		if ( window.scrollY >= 200 ) {
			window.removeEventListener( 'scroll', handleScroll );
			addFrame();
		}
	};

	window.addEventListener( 'scroll', handleScroll );
};

if ( window.farcasterWP.debugEnabled ) {
	// eslint-disable-next-line no-console
	console.log( 'FWP: Loading SDK' );
}

loadSdk();
