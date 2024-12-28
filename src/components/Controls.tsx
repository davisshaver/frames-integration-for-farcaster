import { __ } from '@wordpress/i18n';
import {
	FontSizePicker,
	TextareaControl,
	ToggleControl,
	ColorPicker,
	TextControl,
	Button,
} from '@wordpress/components';
import { MediaUpload } from '@wordpress/media-utils';

const SplashBackgroundColorControl = ( { value, onChange } ) => {
	return <ColorPicker color={ value } onChange={ onChange } />;
};

const ButtonTextControl = ( { value, onChange } ) => {
	return (
		<TextControl
			label={ __( 'Button Text', 'frames-integration-for-farcaster' ) }
			value={ value }
			help={ __(
				'This text will be used as the button text for all posts. Limited to 32 characters.',
				'frames-integration-for-farcaster'
			) }
			onChange={ onChange }
			__nextHasNoMarginBottom
			maxLength={ 32 }
		/>
	);
};

const MessageControl = ( { value, onChange } ) => {
	return (
		<TextareaControl
			label={ __( 'Message', 'frames-integration-for-farcaster' ) }
			value={ value }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
};

const UseTitleAsButtonTextControl = ( { value, onChange } ) => {
	return (
		<ToggleControl
			checked={ value }
			label={ __(
				'Use Title as Button Text',
				'frames-integration-for-farcaster'
			) }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
};

const FramesEnabledControl = ( { value, onChange } ) => {
	return (
		<ToggleControl
			checked={ value }
			label={ __(
				'Enable Farcaster Frames',
				'frames-integration-for-farcaster'
			) }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
};

const DisplayControl = ( { value, onChange } ) => {
	return (
		<ToggleControl
			checked={ value }
			label={ __( 'Display', 'frames-integration-for-farcaster' ) }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
};

const SizeControl = ( { value, onChange } ) => {
	return (
		<FontSizePicker
			fontSizes={ [
				{
					name: __( 'Small', 'frames-integration-for-farcaster' ),
					size: 'small',
					slug: 'small',
				},
				{
					name: __( 'Medium', 'frames-integration-for-farcaster' ),
					size: 'medium',
					slug: 'medium',
				},
				{
					name: __( 'Large', 'frames-integration-for-farcaster' ),
					size: 'large',
					slug: 'large',
				},
				{
					name: __(
						'Extra Large',
						'frames-integration-for-farcaster'
					),
					size: 'x-large',
					slug: 'x-large',
				},
			] }
			value={ value }
			onChange={ onChange }
			disableCustomFontSizes={ true }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
};

const ImageUploadControl = ( {
	value,
	onChange,
	buttonText = 'Select Image',
	labelText = '',
} ) => {
	return (
		<MediaUpload
			onSelect={ ( media ) =>
				onChange( {
					id: media.id,
					url: media.url,
				} )
			}
			help={ __(
				'This image will be used as the splash image for all posts.',
				'frames-integration-for-farcaster'
			) }
			allowedTypes={ [ 'image' ] }
			value={ value }
			render={ ( { open } ) => (
				<div>
					{ value && value.url ? (
						<div style={ { marginBottom: '10px' } }>
							<img
								src={ value.url }
								alt="Selected"
								style={ {
									maxWidth: '200px',
									height: 'auto',
									display: 'block',
									marginBottom: '8px',
								} }
							/>
							<div>
								<Button
									onClick={ open }
									variant="secondary"
									style={ { marginRight: '8px' } }
								>
									Replace Image
								</Button>
								<Button
									onClick={ () =>
										onChange( {
											id: null,
											url: '',
										} )
									}
									variant="link"
									isDestructive
								>
									Remove Image
								</Button>
							</div>
						</div>
					) : (
						<Button
							label={ labelText }
							showTooltip={ true }
							onClick={ open }
							variant="secondary"
						>
							{ buttonText }
						</Button>
					) }
				</div>
			) }
		/>
	);
};

export {
	MessageControl,
	DisplayControl,
	SizeControl,
	FramesEnabledControl,
	SplashBackgroundColorControl,
	ButtonTextControl,
	ImageUploadControl,
	UseTitleAsButtonTextControl,
};
