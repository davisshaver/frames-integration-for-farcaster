import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHeading as Heading,
	Button,
	Panel,
	PanelBody,
	PanelRow,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { useSettings } from '../admin-hooks/use-settings';
import { useManifest } from '../admin-hooks/use-manifest';
import { Notices } from './Notices';
import {
	FramesEnabledControl,
	SplashBackgroundColorControl,
	ButtonTextControl,
	ImageUploadControl,
	UseTitleAsButtonTextControl,
	NotificationsEnabledControl,
	DebugEnabledControl,
	TippingEnabledControl,
	TippingAddressControl,
	TippingAmountsControl,
	ChainsControl,
	RPCURLControl,
	NoIndexControl,
	TaglineControl,
	DescriptionControl,
	CategoryControl,
	OGTitleControl,
	OGDescriptionControl,
} from './Controls';
import Tags from './Tags';
import { ManifestViewer } from './ManifestViewer';
import { SubscriptionsList } from './SubscriptionsList';
import { EventsList } from './EventsList';

const SettingsTitle = () => {
	return (
		<Heading level={ 1 }>
			{ __(
				'Mini App Integration for Farcaster Settings',
				'frames-integration-for-farcaster'
			) }
		</Heading>
	);
};

const SaveButton = ( { onClick } ) => {
	return (
		<Button variant="primary" onClick={ onClick } __next40pxDefaultSize>
			{ __( 'Save', 'frames-integration-for-farcaster' ) }
		</Button>
	);
};

const SettingsPage = () => {
	const {
		saveSettings,
		framesEnabled,
		setFramesEnabled,
		splashBackgroundColor,
		setSplashBackgroundColor,
		buttonText,
		setButtonText,
		splashImage,
		setSplashImage,
		fallbackImage,
		setFallbackImage,
		useTitleAsButtonText,
		setUseTitleAsButtonText,
		domainManifest,
		setDomainManifest,
		notificationsEnabled,
		setNotificationsEnabled,
		debugEnabled,
		setDebugEnabled,
		tippingEnabled,
		setTippingEnabled,
		tippingAddress,
		setTippingAddress,
		tippingAmounts,
		setTippingAmounts,
		tippingChains,
		setTippingChains,
		rpcURL,
		setRpcURL,
		noIndex,
		setNoIndex,
		tagline,
		setTagline,
		description,
		setDescription,
		category,
		setCategory,
		tags,
		setTags,
		heroImage,
		setHeroImage,
		ogTitle,
		setOgTitle,
		ogDescription,
		setOgDescription,
		ogImage,
		setOgImage,
	} = useSettings();

	const { manifest, fetchManifest } = useManifest();

	return (
		<>
			<SettingsTitle />
			<Notices />
			<Panel header="🟪 Mini App General Settings">
				<PanelBody>
					<PanelRow>
						<FramesEnabledControl
							value={ framesEnabled }
							onChange={ ( value ) => setFramesEnabled( value ) }
						/>
					</PanelRow>
					<PanelRow>
						<DebugEnabledControl
							value={ debugEnabled }
							onChange={ setDebugEnabled }
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody
					title={ __(
						'Mini App Button Default Text',
						'frames-integration-for-farcaster'
					) }
					initialOpen={ framesEnabled }
				>
					<PanelRow>
						<div style={ { width: '100%' } }>
							<VStack spacing={ 4 }>
								<UseTitleAsButtonTextControl
									value={ useTitleAsButtonText }
									onChange={ setUseTitleAsButtonText }
								/>
								<ButtonTextControl
									disabled={ useTitleAsButtonText }
									value={ buttonText }
									onChange={ setButtonText }
								/>
							</VStack>
						</div>
					</PanelRow>
				</PanelBody>
				<PanelBody
					title={ __(
						'Mini App Default Image',
						'frames-integration-for-farcaster'
					) }
					initialOpen={ framesEnabled }
				>
					<PanelRow>
						<ImageUploadControl
							helpText={ __(
								'Image will be displayed in 3:2 aspect ratio.',
								'frames-integration-for-farcaster'
							) }
							value={ fallbackImage }
							onChange={ setFallbackImage }
						/>
					</PanelRow>
				</PanelBody>
			</Panel>
			<Panel header="🟪 Mini App Splash Page">
				<PanelBody
					title={ __(
						'Mini AppSplash Background Color',
						'frames-integration-for-farcaster'
					) }
					initialOpen={ framesEnabled }
				>
					<PanelRow>
						<SplashBackgroundColorControl
							value={ splashBackgroundColor }
							onChange={ setSplashBackgroundColor }
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody
					title={ __(
						'Mini App Splash Image',
						'frames-integration-for-farcaster'
					) }
					initialOpen={ framesEnabled }
				>
					<PanelRow>
						<ImageUploadControl
							value={ splashImage }
							onChange={ setSplashImage }
							labelText={ __(
								'Image will be displayed as 200x200px.',
								'frames-integration-for-farcaster'
							) }
							helpText={ __(
								'This image will be used as the splash image for all posts.',
								'frames-integration-for-farcaster'
							) }
						/>
					</PanelRow>
				</PanelBody>
			</Panel>
			<Panel header="🟪 Mini App Promotional Asset">
				<PanelBody
					title={ __(
						'Promotional Display Image',
						'frames-integration-for-farcaster'
					) }
					initialOpen={ framesEnabled }
				>
					<PanelRow>
						<ImageUploadControl
							helpText={ __(
								'Image will be displayed in 1.91:1 aspect ratio.',
								'frames-integration-for-farcaster'
							) }
							value={ heroImage }
							onChange={ setHeroImage }
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody initialOpen={ framesEnabled }>
					<PanelRow>
						<TaglineControl
							value={ tagline }
							onChange={ setTagline }
						/>
					</PanelRow>
				</PanelBody>
			</Panel>
			<Panel header="🟪 Mini App Store Page">
				<PanelBody initialOpen={ framesEnabled }>
					<PanelRow>
						<DescriptionControl
							value={ description }
							onChange={ setDescription }
						/>
					</PanelRow>
				</PanelBody>
			</Panel>
			<Panel header="🟪 Search & Discovery">
				<PanelBody initialOpen={ framesEnabled }>
					<PanelRow>
						<NoIndexControl
							value={ noIndex }
							onChange={ setNoIndex }
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody initialOpen={ framesEnabled }>
					<PanelRow>
						<CategoryControl
							value={ category }
							onChange={ setCategory }
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody initialOpen={ framesEnabled }>
					<PanelRow>
						<Tags value={ tags } onChange={ setTags } />
					</PanelRow>
				</PanelBody>
			</Panel>
			<Panel header="🟪 Sharing Experience">
				<PanelBody initialOpen={ framesEnabled }>
					<PanelRow>
						<OGTitleControl
							value={ ogTitle }
							onChange={ setOgTitle }
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody initialOpen={ framesEnabled }>
					<PanelRow>
						<OGDescriptionControl
							value={ ogDescription }
							onChange={ setOgDescription }
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody
					title={ __(
						'OG Image',
						'frames-integration-for-farcaster'
					) }
					initialOpen={ framesEnabled }
				>
					<PanelRow>
						<ImageUploadControl
							labelText={ __(
								'Image will be displayed in 1.91:1 aspect ratio.',
								'frames-integration-for-farcaster'
							) }
							value={ ogImage }
							onChange={ setOgImage }
							helpText={ __(
								'1200x630 px JPG or PNG. Should show your brand clearly. No excessive text. Logo + tagline + UI is a good combo.',
								'frames-integration-for-farcaster'
							) }
						/>
					</PanelRow>
				</PanelBody>
			</Panel>
			<Panel header="🟪 Tipping">
				<PanelBody>
					<PanelRow>
						<TippingEnabledControl
							value={ tippingEnabled }
							onChange={ setTippingEnabled }
						/>
					</PanelRow>
					<PanelRow>
						<TippingAddressControl
							value={ tippingAddress }
							onChange={ setTippingAddress }
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody
					title={ __(
						'Tipping Chains',
						'frames-integration-for-farcaster'
					) }
					initialOpen={ tippingEnabled }
				>
					<PanelRow>
						<ChainsControl
							value={ tippingChains }
							onChange={ setTippingChains }
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody
					title={ __(
						'Tipping Amounts',
						'frames-integration-for-farcaster'
					) }
					initialOpen={ tippingEnabled }
				>
					<PanelRow>
						<TippingAmountsControl
							value={ tippingAmounts }
							onChange={ setTippingAmounts }
						/>
					</PanelRow>
				</PanelBody>
			</Panel>
			<Panel header="🟪 Notifications">
				<PanelBody>
					<PanelRow>
						<NotificationsEnabledControl
							value={ notificationsEnabled }
							onChange={ setNotificationsEnabled }
						/>
					</PanelRow>
					<PanelRow>
						<RPCURLControl
							value={ rpcURL }
							onChange={ setRpcURL }
						/>
					</PanelRow>
					<PanelRow>
						<SubscriptionsList />
					</PanelRow>
					<PanelRow>
						<EventsList />
					</PanelRow>
				</PanelBody>
			</Panel>
			<Panel header="🟪 Manifest">
				<PanelBody
					title={ __(
						'Manifest Validation',
						'frames-integration-for-farcaster'
					) }
				>
					<PanelRow>
						<ManifestViewer
							currentManifest={ manifest }
							domainManifest={ domainManifest }
							setDomainManifest={ setDomainManifest }
						/>
					</PanelRow>
				</PanelBody>
			</Panel>
			<SaveButton onClick={ () => saveSettings( fetchManifest ) } />
		</>
	);
};

export { SettingsPage };
