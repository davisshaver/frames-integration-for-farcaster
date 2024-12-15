import apiFetch from '@wordpress/api-fetch';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { Subscriptions } from '../admin-utils/subscriptions';

export const useSubscriptions = () => {
	const [ subscriptions, setSubscriptions ] = useState< Subscriptions >();

	const fetchSubscriptions = useCallback( () => {
		apiFetch< Subscriptions >( {
			path: '/farcaster-wp/v1/subscriptions',
		} ).then( ( fetchedSubscriptions ) => {
			setSubscriptions( fetchedSubscriptions );
		} );
	}, [] );

	useEffect( () => {
		fetchSubscriptions();
	}, [ fetchSubscriptions ] );

	return {
		subscriptions,
		fetchSubscriptions,
	};
};
