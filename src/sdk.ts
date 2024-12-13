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
			type: 'success',
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
					type: 'success',
					message: 'You are subscribed to notifications.',
					duration: 3000,
				} );
			} else {
				showToast( {
					message:
						'Would you like to get Warpcast notifications about new posts?',
					buttonText: 'Yes!',
					onButtonClick: () => {
						sdk.actions
							.addFrame()
							.then( ( retryResult ) => {
								// @TODO Technically we could subscribe the user here, but
								// for now we are going to wait for the webhook event to arrive.
								if ( retryResult && retryResult?.added ) {
									showToast( {
										message:
											'Successfully subscribed to notifications!',
										duration: 3000,
									} );
								} else {
									showToast( {
										type: 'error',
										message:
											'Error adding frame, addFrame response: ' +
											JSON.stringify( retryResult ),
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
					},
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
