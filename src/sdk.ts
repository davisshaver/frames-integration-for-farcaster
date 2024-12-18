import sdk, { type FrameContext } from '@farcaster/frame-sdk';
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
			tippingChains: string[];
		};
	}
}

const addFrame = ( context: FrameContext ) => {
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
					onDismiss: () => renderTippingModal( context ),
				} );
			} else {
				renderTippingModal( context );
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
			onDismiss: () => renderTippingModal( context ),
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
		// User has added the frame but not subscribed.
		// We do not have a way to prompt them to subscribe.
		// So we will just show the tipping modal.
		renderTippingModal( context );
		return;
	}

	const handleScroll = () => {
		if ( window.scrollY >= 200 ) {
			window.removeEventListener( 'scroll', handleScroll );
			addFrame( context );
		}
	};

	window.addEventListener( 'scroll', handleScroll );
};

if ( window.farcasterWP.debugEnabled ) {
	// eslint-disable-next-line no-console
	console.log( 'FWP: Loading SDK' );
}

loadSdk();
