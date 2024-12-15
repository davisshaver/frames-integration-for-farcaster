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

const ManifestControl = ( { value, onChange } ) => {
	return (
		<TextareaControl
			label={ __( 'Domain Manifest', 'farcaster-wp' ) }
			value={ value }
			onChange={ onChange }
		/>
	);
};

const TippingAmountsControl = ( { value = [], onChange } ) => {
	const addAmount = () => {
		onChange( [ ...value, 0 ] );
	};

	const removeAmount = ( index ) => {
		const newAmounts = [ ...value ];
		newAmounts.splice( index, 1 );
		onChange( newAmounts );
	};

	const updateAmount = ( index, newValue ) => {
		const newAmounts = [ ...value ];
		// Convert to integer and ensure positive
		newAmounts[ index ] = Math.max( 0, parseInt( newValue ) || 0 );
		onChange( newAmounts );
	};

	return (
		<div>
			{ /* eslint-disable-next-line jsx-a11y/label-has-associated-control */ }
			<label
				className="components-base-control__label"
				style={ {
					display: 'block',
					fontSize: '11px',
					fontWeight: '500',
					lineHeight: '1.4',
					marginBottom: '8px',
					padding: '0px',
					textTransform: 'uppercase',
				} }
			>
				{ __( 'Tipping Amounts (in Sparks)', 'farcaster-wp' ) }
			</label>
			{ value.map( ( amount, index ) => (
				<div
					key={ index }
					style={ {
						display: 'flex',
						gap: '8px',
						marginBottom: '8px',
					} }
				>
					<TextControl
						type="number"
						value={ amount }
						onChange={ ( newValue ) =>
							updateAmount( index, newValue )
						}
						min={ 0 }
					/>
					<Button
						variant="secondary"
						isDestructive
						onClick={ () => removeAmount( index ) }
						icon="trash"
					/>
				</div>
			) ) }
			<Button variant="secondary" onClick={ addAmount } icon="plus">
				{ __( 'Add Amount', 'farcaster-wp' ) }
			</Button>
		</div>
	);
};

const ButtonTextControl = ( { value, onChange } ) => {
	return (
		<TextControl
			label={ __( 'Button Text', 'farcaster-wp' ) }
			value={ value }
			help={ __(
				'This text will be used as the button text for all posts. Limited to 32 characters.',
				'farcaster-wp'
			) }
			onChange={ onChange }
			__nextHasNoMarginBottom
			maxLength={ 32 }
		/>
	);
};

const TippingAddressControl = ( { value, onChange } ) => {
	return (
		<TextControl
			label={ __( 'Tipping Address', 'farcaster-wp' ) }
			value={ value }
			help={ __(
				'This address will be used to tip the site creator when a user clicks the button.',
				'farcaster-wp'
			) }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
};

const MessageControl = ( { value, onChange } ) => {
	return (
		<TextareaControl
			label={ __( 'Message', 'farcaster-wp' ) }
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
			label={ __( 'Use Title as Button Text', 'farcaster-wp' ) }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
};

const NotificationsEnabledControl = ( { value, onChange } ) => {
	return (
		<ToggleControl
			checked={ value }
			label={ __( 'Enable Notifications', 'farcaster-wp' ) }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
};

const DebugEnabledControl = ( { value, onChange } ) => {
	return (
		<ToggleControl
			checked={ value }
			label={ __( 'Enable SDK Logging', 'farcaster-wp' ) }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
};

const FramesEnabledControl = ( { value, onChange } ) => {
	return (
		<ToggleControl
			checked={ value }
			label={ __( 'Enable Farcaster Frames', 'farcaster-wp' ) }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
};

const TippingEnabledControl = ( { value, onChange } ) => {
	return (
		<ToggleControl
			checked={ value }
			label={ __( 'Enable Tipping', 'farcaster-wp' ) }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
};

const DisplayControl = ( { value, onChange } ) => {
	return (
		<ToggleControl
			checked={ value }
			label={ __( 'Display', 'farcaster-wp' ) }
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
					name: __( 'Small', 'farcaster-wp' ),
					size: 'small',
					slug: 'small',
				},
				{
					name: __( 'Medium', 'farcaster-wp' ),
					size: 'medium',
					slug: 'medium',
				},
				{
					name: __( 'Large', 'farcaster-wp' ),
					size: 'large',
					slug: 'large',
				},
				{
					name: __( 'Extra Large', 'farcaster-wp' ),
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
				'farcaster-wp'
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
	ManifestControl,
	NotificationsEnabledControl,
	DebugEnabledControl,
	TippingEnabledControl,
	TippingAddressControl,
	TippingAmountsControl,
};
