import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

interface WPSettings {
	farcaster_wp: {
		message: string;
		display: string;
		size: string;
		frames_enabled: boolean;
		splash_background_color: string;
		button_text: string;
		splash_image: {
			id: number;
			url: string;
		};
		fallback_image: {
			id: number;
			url: string;
		};
		use_title_as_button_text: boolean;
	};
}

export const useSettings = () => {
	const [ message, setMessage ] = useState< string >();
	const [ display, setDisplay ] = useState< string >();
	const [ size, setSize ] = useState< string >();
	const [ framesEnabled, setFramesEnabled ] = useState< boolean >();
	const [ splashBackgroundColor, setSplashBackgroundColor ] =
		useState< string >();
	const [ buttonText, setButtonText ] = useState< string >();
	const [ splashImage, setSplashImage ] = useState< {
		id: number;
		url: string;
	} >( {
		id: 0,
		url: '',
	} );
	const [ fallbackImage, setFallbackImage ] = useState< {
		id: number;
		url: string;
	} >( {
		id: 0,
		url: '',
	} );
	const [ useTitleAsButtonText, setUseTitleAsButtonText ] =
		useState< boolean >( false );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	useEffect( () => {
		apiFetch< WPSettings >( { path: '/wp/v2/settings' } ).then(
			( settings ) => {
				setMessage( settings.farcaster_wp?.message );
				setDisplay( settings.farcaster_wp?.display );
				setSize( settings.farcaster_wp?.size );
				setFramesEnabled( settings.farcaster_wp?.frames_enabled );
				setSplashBackgroundColor(
					settings.farcaster_wp?.splash_background_color
				);
				setButtonText( settings.farcaster_wp?.button_text );
				setSplashImage( settings.farcaster_wp?.splash_image );
				setFallbackImage( settings.farcaster_wp?.fallback_image );
				setUseTitleAsButtonText(
					settings.farcaster_wp?.use_title_as_button_text
				);
			}
		);
	}, [] );

	const saveSettings = () => {
		apiFetch( {
			path: '/wp/v2/settings',
			method: 'POST',
			data: {
				farcaster_wp: {
					message,
					display,
					size,
					frames_enabled: framesEnabled,
					splash_background_color: splashBackgroundColor,
					button_text: buttonText,
					splash_image: splashImage,
					fallback_image: fallbackImage,
					use_title_as_button_text: useTitleAsButtonText,
				},
			},
		} )
			.then( () => {
				createSuccessNotice(
					__( 'Settings saved.', 'frames-integration-for-farcaster' )
				).then(
					() =>
						document.scrollingElement?.scrollTo( {
							top: 0,
							behavior: 'smooth',
						} )
				);
			} )
			.catch( ( error ) => {
				// eslint-disable-next-line no-console
				console.error( error );
				// @TODO: This doesn't seem to be styled as an error notice.
				createErrorNotice(
					__(
						'Failed to save settings.',
						'frames-integration-for-farcaster'
					)
				).then(
					() =>
						document.scrollingElement?.scrollTo( {
							top: 0,
							behavior: 'smooth',
						} )
				);
			} );
	};

	return {
		message,
		setMessage,
		display,
		setDisplay,
		size,
		setSize,
		saveSettings,
		framesEnabled,
		setFramesEnabled,
		splashBackgroundColor,
		setSplashBackgroundColor,
		buttonText,
		setButtonText,
		splashImage,
		setSplashImage,
		fallbackImage,
		setFallbackImage,
		useTitleAsButtonText,
		setUseTitleAsButtonText,
	};
};
