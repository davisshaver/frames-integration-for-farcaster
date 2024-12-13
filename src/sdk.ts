import sdk from '@farcaster/frame-sdk';
import { showToast } from './utils/toast';

declare global {
	interface Window {
		farcasterWP: {
			notificationsEnabled: boolean;
		};
	}
}

const loadSdk = async () => {
	const context = await sdk.context;
	sdk.actions.ready();

	if ( ! window.farcasterWP.notificationsEnabled ) {
		return;
	}

	if ( ! context ) {
		// No context, probably not in frame.
		return;
	}

	if ( context?.location?.type === 'notification' ) {
		showToast( {
			message: 'Thanks for being a subscriber!',
			duration: 3000,
		} );
		return;
	}

	sdk.actions
		.addFrame()
		.then( ( result ) => {
			if ( result?.added ) {
				showToast( {
					message: 'You are subscribed to notifications.',
					duration: 3000,
				} );
			}
		} )
		.catch( ( error ) => {
			showToast( {
				type: 'error',
				message:
					'Error adding frame, addFrame error: ' +
					JSON.stringify( error ),
				duration: 3000,
			} );
		} );
};

loadSdk();
