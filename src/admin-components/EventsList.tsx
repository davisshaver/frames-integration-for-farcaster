// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalText as Text } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEvents } from '../admin-hooks/use-events';

export const EventsList = () => {
	const { events } = useEvents();

	return (
		<div style={ { width: '100%' } }>
			<div style={ { marginTop: '16px' } }>
				<Text>
					{ __( 'You have', 'frames-integration-for-farcaster' ) }{ ' ' }
					{ events?.length }{ ' ' }
					{ __(
						'events on your site:',
						'frames-integration-for-farcaster'
					) }
				</Text>
			</div>
			<div style={ { marginTop: '8px' } } />

			<pre
				style={ {
					maxHeight: '500px',
					overflow: 'auto',
					border: '1px solid #ddd',
					borderRadius: '4px',
					padding: '8px',
					backgroundColor: '#f9f9f9',
				} }
			>
				{ JSON.stringify( events, null, 2 ) }
			</pre>
		</div>
	);
};
