import {
	ToggleControl,
	PanelRow,
	TextareaControl,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useEffect, type ComponentType } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { TemplateVariablesModal } from '../admin-components/TemplateVariablesModal';

declare global {
	interface Window {
		farcaster_wp_editor: {
			auto_casting: boolean;
			auto_casting_default: boolean;
			auto_casting_template: string;
		};
	}
}

/**
 * Use cast post toggle and message inputs
 *
 * @param {CastPostProps} props                 - Props
 * @param {Object}        props.meta            - Post meta
 * @param {string}        props.postType        - Post type
 * @param {string}        props.postStatus      - Post status
 * @param {Function}      props.updateMetaValue - Update post meta
 */
interface CastPostProps {
	meta: {
		farcaster_wp_cast_post: boolean;
		farcaster_wp_cast_post_message: string;
	} | null;
	postType: string;
	postStatus: string;
	updateMetaValue: ( key: string, value: boolean | string ) => void;
}

/**
 * Frames Integration for Farcaster - cast post
 *
 * @param {CastPostProps} props - Props
 * @return {JSX.Element} The cast post component
 */
const FarcasterWpCastPost = ( {
	meta,
	postType,
	postStatus,
	updateMetaValue,
}: CastPostProps ) => {
	useEffect( () => {
		if (
			meta &&
			undefined === meta.farcaster_wp_cast_post &&
			window.farcaster_wp_editor.auto_casting
		) {
			updateMetaValue( 'farcaster_wp_cast_post', true );
		}
	}, [ meta, updateMetaValue ] );

	if ( ! meta ) {
		return null;
	}

	// eslint-disable-next-line camelcase
	const {
		farcaster_wp_cast_post: castPost,
		farcaster_wp_cast_post_message: castPostMessage,
	} = meta;

	return (
		<PluginDocumentSettingPanel
			name="farcaster-wp-cast-post"
			title={ __( 'Cast Post', 'frames-integration-for-farcaster' ) }
			className="farcaster_wp__post-meta-toggles"
		>
			<PanelRow>
				{ [ 'post' ].includes( postType ) && (
					<div style={ { width: '100%' } }>
						<ToggleControl
							label={ __(
								'Cast post on publish',
								'frames-integration-for-farcaster'
							) }
							disabled={ postStatus === 'publish' }
							checked={ castPost }
							onChange={ () =>
								updateMetaValue(
									'farcaster_wp_cast_post',
									! castPost
								)
							}
							id="farcaster_wp_cast_post"
						/>
						{ castPost && (
							<>
								<TextareaControl
									label={ __(
										'Cast message',
										'frames-integration-for-farcaster'
									) }
									className="farcaster-wp__cast-post-message"
									value={ castPostMessage }
									disabled={ ! castPost }
									onChange={ ( value ) =>
										updateMetaValue(
											'farcaster_wp_cast_post_message',
											value
										)
									}
									placeholder={
										window.farcaster_wp_editor
											.auto_casting_template
									}
								/>
								<TemplateVariablesModal />
							</>
						) }
					</div>
				) }
			</PanelRow>
		</PluginDocumentSettingPanel>
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
		postStatus: getEditedPostAttribute( 'status' ),
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
const castPostControl = compose(
	withSelect( mapStateToProps ),
	withDispatch( mapDispatchToProps )
)( FarcasterWpCastPost ) as ComponentType;

if ( window.farcaster_wp_editor.auto_casting ) {
	registerPlugin( 'farcaster-wp-cast-post', {
		render: castPostControl,
	} );
}
