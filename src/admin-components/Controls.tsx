import { __ } from '@wordpress/i18n';
import {
	FontSizePicker,
	TextareaControl,
	ToggleControl,
	ColorPicker,
	TextControl,
	Button,
	SelectControl,
} from '@wordpress/components';
import { MediaUpload } from '@wordpress/media-utils';
import type { DragSourceMonitor } from 'react-dnd';
import { DndProvider, useDrag, useDrop } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import { isAddress } from 'viem';

type Chain = {
	id: string;
	name: string;
};

const AVAILABLE_CHAINS: Chain[] = [
	{ id: 'optimism', name: 'Optimism' },
	{ id: 'base', name: 'Base' },
	{ id: 'mainnet', name: 'Ethereum Mainnet' },
	{ id: 'zora', name: 'Zora' },
];

type DraggableItemProps< T > = {
	dragType: string;
	index: number;
	item: T;
	moveItem: ( fromIndex: number, toIndex: number ) => void;
	renderContent: ( item: T, index: number ) => React.ReactNode;
};

const DraggableItem = < T extends unknown >( {
	dragType,
	index,
	item,
	moveItem,
	renderContent,
}: DraggableItemProps< T > ) => {
	const [ { isDragging }, drag ] = useDrag( {
		type: dragType,
		item: { index },
		collect: ( monitor: DragSourceMonitor ) => ( {
			isDragging: monitor.isDragging(),
		} ),
	} );

	const [ , drop ] = useDrop( {
		accept: dragType,
		hover: ( draggedItem: { index: number } ) => {
			if ( draggedItem.index !== index ) {
				moveItem( draggedItem.index, index );
				draggedItem.index = index;
			}
		},
	} );

	return (
		<div
			ref={ ( node ) => drag( drop( node ) ) }
			style={ {
				alignItems: 'center',
				cursor: 'move',
				display: 'flex',
				gap: '8px',
				marginBottom: '8px',
				opacity: isDragging ? 0.5 : 1,
			} }
		>
			<span className="dashicons dashicons-menu" />
			<div
				style={ {
					alignItems: 'center',
					display: 'flex',
					flex: 1,
					justifyContent: 'space-between',
				} }
			>
				{ renderContent( item, index ) }
			</div>
		</div>
	);
};

const DraggableChainItem = ( { chain, index, moveItem, removeChain } ) => {
	return (
		<DraggableItem
			dragType="chain"
			index={ index }
			item={ chain }
			moveItem={ moveItem }
			renderContent={ ( chainItem, idx ) => (
				<>
					<span>{ chainItem.name }</span>
					<Button
						variant="secondary"
						isDestructive
						onClick={ () => removeChain( idx ) }
						icon="trash"
					/>
				</>
			) }
		/>
	);
};

const ChainsControl = ( { value = [], onChange } ) => {
	const enabledChains = value
		.map( ( chainId ) =>
			AVAILABLE_CHAINS.find( ( chain ) => chain.id === chainId )
		)
		.filter( Boolean );

	const addChain = ( chainId: string ) => {
		if ( ! value.includes( chainId ) ) {
			onChange( [ ...value, chainId ] );
		}
	};

	const removeChain = ( index: number ) => {
		const newChains = [ ...value ];
		newChains.splice( index, 1 );
		onChange( newChains );
	};

	const moveItem = ( fromIndex: number, toIndex: number ) => {
		const newChains = [ ...value ];
		const [ movedItem ] = newChains.splice( fromIndex, 1 );
		newChains.splice( toIndex, 0, movedItem );
		onChange( newChains );
	};

	const availableToAdd = AVAILABLE_CHAINS.filter(
		( chain ) => ! value.includes( chain.id )
	);

	return (
		<DndProvider backend={ HTML5Backend }>
			<div style={ { width: '100%' } }>
				<div className="components-base-control">
					<div className="components-base-control__field">
						<label
							htmlFor="chains-control"
							className="components-base-control__label"
						>
							{ __( 'Supported Chains', 'farcaster-wp' ) }
						</label>
						<div id="chains-control">
							{ enabledChains.length > 0 ? (
								enabledChains.map( ( chain, index ) => (
									<DraggableChainItem
										chain={ chain }
										index={ index }
										key={ chain.id }
										moveItem={ moveItem }
										removeChain={ removeChain }
									/>
								) )
							) : (
								<div
									style={ {
										background: '#f0f0f0',
										borderRadius: '4px',
										color: '#757575',
										margin: '8px 0',
										padding: '12px',
										textAlign: 'center',
									} }
								>
									{ __(
										'No chains selected. Add a chain below to get started.',
										'farcaster-wp'
									) }
								</div>
							) }
						</div>
					</div>
				</div>
				{ availableToAdd.length > 0 && (
					<div className="components-base-control">
						<SelectControl
							label={ __( 'Add Chain', 'farcaster-wp' ) }
							value=""
							options={ [
								{
									value: '',
									label: __(
										'Select a chain to add…',
										'farcaster-wp'
									),
								},
								...availableToAdd.map( ( chain ) => ( {
									value: chain.id,
									label: chain.name,
								} ) ),
							] }
							onChange={ ( chainId ) => {
								if ( chainId ) {
									addChain( chainId );
								}
							} }
						/>
					</div>
				) }
			</div>
		</DndProvider>
	);
};

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
		newAmounts[ index ] = Math.max( 0, parseInt( newValue ) || 0 );
		onChange( newAmounts );
	};

	const moveItem = ( fromIndex, toIndex ) => {
		const newAmounts = [ ...value ];
		const [ movedItem ] = newAmounts.splice( fromIndex, 1 );
		newAmounts.splice( toIndex, 0, movedItem );
		onChange( newAmounts );
	};

	return (
		<DndProvider backend={ HTML5Backend }>
			<div style={ { width: '100%' } }>
				<div className="components-base-control">
					<div className="components-base-control__field">
						<label
							htmlFor="tipping-amounts-control"
							className="components-base-control__label"
						>
							{ __( 'Tipping Amounts (in', 'farcaster-wp' ) }{ ' ' }
							<a
								href="https://zora.co/writings/sparks"
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __( 'Sparks', 'farcaster-wp' ) }
							</a>
							{ __( ')', 'farcaster-wp' ) }
						</label>
						<div id="tipping-amounts-control">
							{ value.length > 0 ? (
								value.map( ( amount, index ) => (
									<DraggableAmountItem
										key={ index }
										amount={ amount }
										index={ index }
										moveItem={ moveItem }
										updateAmount={ updateAmount }
										removeAmount={ removeAmount }
									/>
								) )
							) : (
								<div
									style={ {
										padding: '12px',
										background: '#f0f0f0',
										borderRadius: '4px',
										textAlign: 'center',
										color: '#757575',
										margin: '8px 0',
									} }
								>
									{ __(
										'No tipping amounts configured. Add an amount below to get started.',
										'farcaster-wp'
									) }
								</div>
							) }
						</div>
					</div>
				</div>
				<Button variant="secondary" onClick={ addAmount } icon="plus">
					{ __( 'Add Amount', 'farcaster-wp' ) }
				</Button>
			</div>
		</DndProvider>
	);
};

const ButtonTextControl = ( { value, onChange, useTitleAsButtonText } ) => {
	return (
		<TextControl
			label={ __( 'Button Text', 'farcaster-wp' ) }
			value={ value }
			help={
				! useTitleAsButtonText
					? __(
							'This text will be used as the button text for all posts. Limited to 32 characters.',
							'farcaster-wp'
					  )
					: __(
							'This text will be used as the button text when frame is used outside of casts. Limited to 32 characters.',
							'farcaster-wp'
					  )
			}
			onChange={ onChange }
			__nextHasNoMarginBottom
			maxLength={ 32 }
		/>
	);
};

const TippingAddressControl = ( { value, onChange } ) => {
	const isInvalid = value && value !== '' && ! isAddress( value );
	return (
		<div style={ { width: '100%' } }>
			<TextControl
				label={ __( 'Tipping Address', 'farcaster-wp' ) }
				value={ value }
				onChange={ onChange }
				help={
					isInvalid
						? __(
								'Please enter a valid Ethereum address',
								'farcaster-wp'
						  )
						: __(
								'Enter the Ethereum address that will receive tips',
								'farcaster-wp'
						  )
				}
				className={ isInvalid ? 'has-error' : '' }
			/>
		</div>
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
			label={ __( 'Use Post Title as Button Text', 'farcaster-wp' ) }
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

const DraggableAmountItem = ( {
	amount,
	index,
	moveItem,
	removeAmount,
	updateAmount,
} ) => {
	return (
		<DraggableItem
			dragType="amount"
			index={ index }
			item={ amount }
			moveItem={ moveItem }
			renderContent={ ( amountValue, idx ) => (
				<>
					<TextControl
						type="number"
						value={ amountValue }
						onChange={ ( newValue ) =>
							updateAmount( idx, newValue )
						}
						min={ 0 }
					/>
					<Button
						icon="trash"
						isDestructive
						onClick={ () => removeAmount( idx ) }
						variant="secondary"
					/>
				</>
			) }
		/>
	);
};

const RPCURLControl = ( { value, onChange } ) => {
	return (
		<div style={ { width: '100%' } }>
			<TextControl
				label={ __( 'RPC URL for Optimism', 'farcaster-wp' ) }
				value={ value }
				onChange={ onChange }
				help={
					! value || value === ''
						? __(
								'Enter the URL of your Ethereum RPC for the Optimism chain. Required for complete key verification.',
								'farcaster-wp'
						  )
						: __(
								'Enter the URL of your Ethereum RPC for the Optimism chain.',
								'farcaster-wp'
						  )
				}
				className={ ! value || value === '' ? 'has-error' : '' }
				type="url"
				__nextHasNoMarginBottom
			/>
		</div>
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
	ChainsControl,
	RPCURLControl,
};
