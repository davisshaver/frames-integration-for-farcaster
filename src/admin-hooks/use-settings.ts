import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { FarcasterManifestSchema } from '../admin-utils/manifest';

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
		domain_manifest: string;
		notifications_enabled: boolean;
		debug_enabled: boolean;
		tipping_enabled: boolean;
		tipping_address: string;
		tipping_amounts: number[];
		tipping_chains: string[];
		rpc_url: string;
		// auto_casting: boolean;
		// auto_casting_default: boolean;
		no_index: boolean;
		tagline: string;
		description: string;
		category: string;
		// tags: string[];
		hero_image: {
			id: number;
			url: string;
		};
	};
}

export const useSettings = () => {
	const [ domainManifest, setDomainManifest ] = useState< string >();
	const [ rpcURL, setRpcURL ] = useState< string >();
	const [ framesEnabled, setFramesEnabled ] = useState< boolean >();
	const [ notificationsEnabled, setNotificationsEnabled ] =
		useState< boolean >( false );
	const [ debugEnabled, setDebugEnabled ] = useState< boolean >( false );
	const [ tippingEnabled, setTippingEnabled ] = useState< boolean >( false );
	// const [ autoCasting, setAutoCasting ] = useState< boolean >( false );
	// const [ autoCastingDefault, setAutoCastingDefault ] =
	// 	useState< boolean >( false );
	// const [ autoCastingTemplate, setAutoCastingTemplate ] =
	// 	useState< string >();
	const [ tippingAddress, setTippingAddress ] = useState< string >();
	const [ tippingAmounts, setTippingAmounts ] = useState< number[] >( [] );
	const [ tippingChains, setTippingChains ] = useState< string[] >( [] );
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
	const [ noIndex, setNoIndex ] = useState< boolean >( false );
	const [ tagline, setTagline ] = useState< string >();
	const [ description, setDescription ] = useState< string >();
	const [ category, setCategory ] = useState< string >();
	// const [ tags, setTags ] = useState< string[] >( [] );
	const [ heroImage, setHeroImage ] = useState< {
		id: number;
		url: string;
	} >( {
		id: 0,
		url: '',
	} );
	const [ useTitleAsButtonText, setUseTitleAsButtonText ] =
		useState< boolean >( false );
	const { createSuccessNotice, createErrorNotice, removeNotice } =
		useDispatch( noticesStore );

	useEffect( () => {
		apiFetch< WPSettings >( { path: '/wp/v2/settings' } ).then(
			( settings ) => {
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
				setDomainManifest( settings.farcaster_wp?.domain_manifest );
				setNotificationsEnabled(
					settings.farcaster_wp?.notifications_enabled
				);
				setDebugEnabled( settings.farcaster_wp?.debug_enabled );
				setTippingEnabled( settings.farcaster_wp?.tipping_enabled );
				setTippingAddress( settings.farcaster_wp?.tipping_address );
				setTippingAmounts( settings.farcaster_wp?.tipping_amounts );
				setTippingChains( settings.farcaster_wp?.tipping_chains );
				setRpcURL( settings.farcaster_wp?.rpc_url );
				setNoIndex( settings.farcaster_wp?.no_index );
				setTagline( settings.farcaster_wp?.tagline );
				setDescription( settings.farcaster_wp?.description );
				setCategory( settings.farcaster_wp?.category );
				// setTags( settings.farcaster_wp?.tags );
				setHeroImage( settings.farcaster_wp?.hero_image );
				// setAutoCasting( settings.farcaster_wp?.auto_casting );
				// setAutoCastingDefault(
				// 	settings.farcaster_wp?.auto_casting_default
				// );
				// setAutoCastingTemplate(
				// 	settings.farcaster_wp?.auto_casting_template
				// );
			}
		);
	}, [] );

	useEffect( () => {
		let noticeId: string | undefined;

		async function showRpcNotice() {
			if ( rpcURL !== undefined && rpcURL === '' && ! noticeId ) {
				const actionObject = ( await createErrorNotice(
					__(
						'RPC URL is required for key validation. Currently, signatures will be validated, but keys will not be verified using contract.',
						'frames-integration-for-farcaster'
					)
				) ) as any;
				noticeId = actionObject?.notice?.id;
			} else if ( rpcURL !== undefined && rpcURL !== '' && noticeId ) {
				removeNotice( noticeId );
				noticeId = undefined;
			}
		}

		showRpcNotice();

		return () => {
			if ( noticeId ) {
				removeNotice( noticeId );
			}
		};
	}, [ createErrorNotice, removeNotice, rpcURL ] );

	const saveSettings = ( callback?: () => void ) => {
		// Hacky validation here to prevent saving invalid manifest.
		if ( domainManifest ) {
			let parsedDomainManifest = null;
			try {
				parsedDomainManifest = JSON.parse( domainManifest );
			} catch {}
			const result =
				FarcasterManifestSchema.safeParse( parsedDomainManifest );
			if ( ! result.success ) {
				createErrorNotice(
					__(
						'Did not save settings, domain manifest is invalid.',
						'frames-integration-for-farcaster'
					)
				).then(
					() =>
						document.scrollingElement?.scrollTo( {
							top: 0,
							behavior: 'smooth',
						} )
				);
				return;
			}
		}

		if ( rpcURL ) {
			try {
				new URL( rpcURL );
			} catch {
				createErrorNotice(
					__(
						'RPC URL is invalid.',
						'frames-integration-for-farcaster'
					)
				).then(
					() =>
						document.scrollingElement?.scrollTo( {
							top: 0,
							behavior: 'smooth',
						} )
				);
				return;
			}
		}

		apiFetch( {
			path: '/wp/v2/settings',
			method: 'POST',
			data: {
				farcaster_wp: {
					frames_enabled: framesEnabled,
					splash_background_color: splashBackgroundColor,
					button_text: buttonText,
					splash_image: splashImage,
					fallback_image: fallbackImage,
					use_title_as_button_text: useTitleAsButtonText,
					domain_manifest: domainManifest,
					notifications_enabled: notificationsEnabled,
					debug_enabled: debugEnabled,
					tipping_enabled: tippingEnabled,
					tipping_address: tippingAddress,
					tipping_amounts: tippingAmounts,
					tipping_chains: tippingChains,
					rpc_url: rpcURL,
					no_index: noIndex,
					tagline,
					description,
					category,
					// tags,
					hero_image: heroImage,
					// auto_casting: autoCasting,
					// auto_casting_default: autoCastingDefault,
					// auto_casting_template: autoCastingTemplate,
				},
			},
		} )
			.then( () => {
				createSuccessNotice(
					__( 'Settings saved.', 'frames-integration-for-farcaster' )
				).then( () => {
					if ( callback ) {
						callback();
					}
					document.scrollingElement?.scrollTo( {
						top: 0,
						behavior: 'smooth',
					} );
				} );
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
		domainManifest,
		setDomainManifest,
		notificationsEnabled,
		setNotificationsEnabled,
		debugEnabled,
		setDebugEnabled,
		tippingEnabled,
		setTippingEnabled,
		tippingAddress,
		setTippingAddress,
		tippingAmounts,
		setTippingAmounts,
		tippingChains,
		setTippingChains,
		rpcURL,
		setRpcURL,
		noIndex,
		setNoIndex,
		tagline,
		setTagline,
		description,
		setDescription,
		category,
		setCategory,
		heroImage,
		setHeroImage,
		// autoCasting,
		// setAutoCasting,
		// autoCastingDefault,
		// setAutoCastingDefault,
		// autoCastingTemplate,
		// setAutoCastingTemplate,
	};
};
