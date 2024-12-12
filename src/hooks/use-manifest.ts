import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { FarcasterManifest } from '../utils/manifest';

export const useManifest = () => {
	const [ manifest, setManifest ] = useState< FarcasterManifest >();

	useEffect( () => {
		apiFetch< FarcasterManifest >( {
			path: '/farcaster-wp/v1/manifest',
		} ).then( ( fetchedManifest ) => {
			setManifest( fetchedManifest );
		} );
	}, [] );

	return {
		manifest,
	};
};
