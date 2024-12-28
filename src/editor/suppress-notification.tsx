import { FormToggle } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import type { ComponentType } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Use suppress notifications toggle
 *
 * @param {SuppressNotificationsProps} props                 - Props
 * @param {Object}                     props.meta            - Post meta
 * @param {string}                     props.postType        - Post type
 * @param {Function}                   props.updateMetaValue - Update post meta
 */
interface SuppressNotificationsProps {
	meta: {
		farcaster_wp_suppress_notifications: boolean;
	} | null;
	postType: string;
	updateMetaValue: ( key: string, value: boolean ) => void;
}

/**
 * Frames Integration for Farcaster suppress notifications
 *
 * @param {SuppressNotificationsProps} props - Props
 * @return {JSX.Element} The Frames Integration for Farcaster suppress notifications component
 */
const FarcasterWpSuppressNotifications = ( {
	meta,
	postType,
	updateMetaValue,
}: SuppressNotificationsProps ) => {
	if ( ! meta ) {
		return null;
	}
	// eslint-disable-next-line camelcase
	const { farcaster_wp_suppress_notifications: suppressNotifications } = meta;

	return (
		<PluginPostStatusInfo className="farcaster_wp__post-meta-toggles">
			{ [ 'post', 'page' ].includes( postType ) && (
				<div>
					<label htmlFor="farcaster_wp_suppress_notifications">
						{ __(
							'Suppress Farcaster notifications',
							'frames-integration-for-farcaster'
						) }
					</label>
					<FormToggle
						checked={ suppressNotifications }
						onChange={ () =>
							updateMetaValue(
								'farcaster_wp_suppress_notifications',
								! suppressNotifications
							)
						}
						id="farcaster_wp_suppress_notifications"
					/>
				</div>
			) }
		</PluginPostStatusInfo>
	);
};

/**
 * Map state to props
 * @param select
 */
const mapStateToProps = ( select ) => {
	const { getCurrentPostType, getEditedPostAttribute } =
		select( 'core/editor' );
	return {
		meta: getEditedPostAttribute( 'meta' ),
		postType: getCurrentPostType(),
	};
};

const mapDispatchToProps = ( dispatch ) => {
	const { editPost } = dispatch( 'core/editor' );
	return {
		updateMetaValue: ( key, value ) =>
			editPost( { meta: { [ key ]: value } } ),
	};
};

/**
 * Register plugins
 */
const suppressNotifications = compose(
	withSelect( mapStateToProps ),
	withDispatch( mapDispatchToProps )
)( FarcasterWpSuppressNotifications ) as ComponentType;

registerPlugin( 'farcaster-wp-suppress-notifications', {
	render: suppressNotifications,
} );
