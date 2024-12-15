// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalText as Text } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSubscriptions } from '../admin-hooks/use-subscriptions';

export const SubscriptionsList = () => {
	const { subscriptions } = useSubscriptions();

	return (
		<div>
			<div style={ { marginTop: '16px' } }>
				<Text>
					{ __( 'You have', 'farcaster-wp' ) }{ ' ' }
					{ subscriptions?.length }{ ' ' }
					{ __( 'subscriptions on your site:', 'farcaster-wp' ) }
				</Text>
			</div>
			<div style={ { marginTop: '8px' } } />
			<pre>{ JSON.stringify( subscriptions, null, 2 ) }</pre>
		</div>
	);
};
