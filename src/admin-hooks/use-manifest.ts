import apiFetch from '@wordpress/api-fetch';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { FarcasterManifest } from '../admin-utils/manifest';

export const useManifest = () => {
	const [ manifest, setManifest ] = useState< FarcasterManifest >();

	const fetchManifest = useCallback( () => {
		apiFetch< FarcasterManifest >( {
			path: '/farcaster-wp/v1/manifest',
		} ).then( ( fetchedManifest ) => {
			setManifest( fetchedManifest );
		} );
	}, [] );

	useEffect( () => {
		fetchManifest();
	}, [ fetchManifest ] );

	return {
		manifest,
		fetchManifest,
	};
};
