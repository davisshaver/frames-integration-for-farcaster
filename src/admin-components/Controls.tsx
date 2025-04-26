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
							{ __(
								'Supported Chains',
								'frames-integration-for-farcaster'
							) }
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
										'frames-integration-for-farcaster'
									) }
								</div>
							) }
						</div>
					</div>
				</div>
				{ availableToAdd.length > 0 && (
					<div className="components-base-control">
						<SelectControl
							label={ __(
								'Add Chain',
								'frames-integration-for-farcaster'
							) }
							value=""
							options={ [
								{
									value: '',
									label: __(
										'Select a chain to add…',
										'frames-integration-for-farcaster'
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
			label={ __(
				'Domain Manifest',
				'frames-integration-for-farcaster'
			) }
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
							{ __(
								'Tipping Amounts (in',
								'frames-integration-for-farcaster'
							) }{ ' ' }
							<a
								href="https://zora.co/writings/sparks"
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __(
									'Sparks',
									'frames-integration-for-farcaster'
								) }
							</a>
							{ __( ')', 'frames-integration-for-farcaster' ) }
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
										'frames-integration-for-farcaster'
									) }
								</div>
							) }
						</div>
					</div>
				</div>
				<Button variant="secondary" onClick={ addAmount } icon="plus">
					{ __( 'Add Amount', 'frames-integration-for-farcaster' ) }
				</Button>
			</div>
		</DndProvider>
	);
};

const ButtonTextControl = ( { value, onChange, useTitleAsButtonText } ) => {
	return (
		<TextControl
			label={ __( 'Button Text', 'frames-integration-for-farcaster' ) }
			value={ value }
			help={
				! useTitleAsButtonText
					? __(
							'This text will be used as the button text for all posts. Limited to 32 characters.',
							'frames-integration-for-farcaster'
					  )
					: __(
							'This text will be used as the button text when frame is used outside of casts. Limited to 32 characters.',
							'frames-integration-for-farcaster'
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
				label={ __(
					'Tipping Address',
					'frames-integration-for-farcaster'
				) }
				value={ value }
				onChange={ onChange }
				help={
					isInvalid
						? __(
								'Please enter a valid Ethereum address',
								'frames-integration-for-farcaster'
						  )
						: __(
								'Enter the Ethereum address that will receive tips',
								'frames-integration-for-farcaster'
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
				'Use Post Title as Button Text',
				'frames-integration-for-farcaster'
			) }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
};

const NotificationsEnabledControl = ( { value, onChange } ) => {
	return (
		<ToggleControl
			checked={ value }
			label={ __(
				'Enable Notifications',
				'frames-integration-for-farcaster'
			) }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
};

const NoIndexControl = ( { value, onChange } ) => {
	return (
		<ToggleControl
			checked={ value }
			label={ __(
				'Exclude from search',
				'frames-integration-for-farcaster'
			) }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
};

const TaglineControl = ( { value, onChange } ) => {
	return (
		<div style={ { width: '100%' } }>
			<TextControl
				label={ __( 'Tagline', 'frames-integration-for-farcaster' ) }
				value={ value }
				onChange={ onChange }
				maxLength={ 30 }
			/>
		</div>
	);
};

const DescriptionControl = ( { value, onChange } ) => {
	return (
		<div style={ { width: '100%' } }>
			<TextControl
				label={ __(
					'Description',
					'frames-integration-for-farcaster'
				) }
				value={ value }
				onChange={ onChange }
				maxLength={ 170 }
			/>
		</div>
	);
};

const CategoryControl = ( { value, onChange } ) => {
	return (
		<SelectControl
			label={ __( 'Category', 'frames-integration-for-farcaster' ) }
			value={ value }
			options={ [
				{
					value: 'games',
					label: __( 'Games', 'frames-integration-for-farcaster' ),
				},
				{
					value: 'social',
					label: __( 'Social', 'frames-integration-for-farcaster' ),
				},
				{
					value: 'finance',
					label: __( 'Finance', 'frames-integration-for-farcaster' ),
				},
				{
					value: 'utility',
					label: __( 'Utility', 'frames-integration-for-farcaster' ),
				},
				{
					value: 'productivity',
					label: __(
						'Productivity',
						'frames-integration-for-farcaster'
					),
				},
				{
					value: 'health-fitness',
					label: __(
						'Health & Fitness',
						'frames-integration-for-farcaster'
					),
				},
				{
					value: 'news-media',
					label: __(
						'News & Media',
						'frames-integration-for-farcaster'
					),
				},
				{
					value: 'music',
					label: __( 'Music', 'frames-integration-for-farcaster' ),
				},
				{
					value: 'shopping',
					label: __( 'Shopping', 'frames-integration-for-farcaster' ),
				},
				{
					value: 'education',
					label: __(
						'Education',
						'frames-integration-for-farcaster'
					),
				},
				{
					value: 'developer-tools',
					label: __(
						'Developer Tools',
						'frames-integration-for-farcaster'
					),
				},
				{
					value: 'entertainment',
					label: __(
						'Entertainment',
						'frames-integration-for-farcaster'
					),
				},
				{
					value: 'art-creativity',
					label: __(
						'Art & Creativity',
						'frames-integration-for-farcaster'
					),
				},
			] }
			onChange={ onChange }
		/>
	);
};

const DebugEnabledControl = ( { value, onChange } ) => {
	return (
		<ToggleControl
			checked={ value }
			label={ __(
				'Enable SDK Logging',
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
				'Enable Mini App',
				'frames-integration-for-farcaster'
			) }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
};

const TippingEnabledControl = ( { value, onChange } ) => {
	return (
		<ToggleControl
			checked={ value }
			label={ __( 'Enable Tipping', 'frames-integration-for-farcaster' ) }
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
				label={ __(
					'RPC URL for Optimism',
					'frames-integration-for-farcaster'
				) }
				value={ value }
				onChange={ onChange }
				help={
					! value || value === ''
						? __(
								'Enter the URL of your Ethereum RPC for the Optimism chain. Required for complete key verification.',
								'frames-integration-for-farcaster'
						  )
						: __(
								'Enter the URL of your Ethereum RPC for the Optimism chain.',
								'frames-integration-for-farcaster'
						  )
				}
				className={ ! value || value === '' ? 'has-error' : '' }
				type="url"
				__nextHasNoMarginBottom
			/>
		</div>
	);
};

// const AutoCastingControl = ( { value, onChange } ) => {
// 	return (
// 		<ToggleControl
// 			checked={ value }
// 			label={ __(
// 				'Enable Auto-Casting',
// 				'frames-integration-for-farcaster'
// 			) }
// 			onChange={ onChange }
// 			__nextHasNoMarginBottom
// 		/>
// 	);
// };

// const AutoCastingDefaultControl = ( { value, onChange } ) => {
// 	return (
// 		<ToggleControl
// 			checked={ value }
// 			label={ __(
// 				'Auto-Cast by Default',
// 				'frames-integration-for-farcaster'
// 			) }
// 			onChange={ onChange }
// 			__nextHasNoMarginBottom
// 		/>
// 	);
// };

// const AutoCastingTemplateControl = ( { value, onChange } ) => {
// 	return (
// 		<TextareaControl
// 			label={ __(
// 				'Auto-Cast Template',
// 				'frames-integration-for-farcaster'
// 			) }
// 			value={ value }
// 			placeholder={ '#title# #url#' }
// 			onChange={ onChange }
// 			__nextHasNoMarginBottom
// 		/>
// 	);
// };

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
	// AutoCastingControl,
	// AutoCastingDefaultControl,
	// AutoCastingTemplateControl,
	NoIndexControl,
	TaglineControl,
	DescriptionControl,
	CategoryControl,
};
