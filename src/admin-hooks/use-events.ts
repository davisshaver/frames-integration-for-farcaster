import apiFetch from '@wordpress/api-fetch';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { Events } from '../admin-utils/events';

export const useEvents = () => {
	const [ events, setEvents ] = useState< Events >();

	const fetchEvents = useCallback( () => {
		apiFetch< Events >( {
			path: '/farcaster-wp/v1/events',
		} ).then( ( fetchedEvents ) => {
			setEvents( fetchedEvents );
		} );
	}, [] );

	useEffect( () => {
		fetchEvents();
	}, [ fetchEvents ] );

	return {
		events,
		fetchEvents,
	};
};
