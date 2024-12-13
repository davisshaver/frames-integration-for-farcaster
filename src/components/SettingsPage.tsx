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
import { useSettings } from '../hooks/use-settings';
import { useManifest } from '../hooks/use-manifest';
import { Notices } from './Notices';
import {
	FramesEnabledControl,
	SplashBackgroundColorControl,
	ButtonTextControl,
	ImageUploadControl,
	UseTitleAsButtonTextControl,
	NotificationsEnabledControl,
} from './Controls';
import { ManifestViewer } from './ManifestViewer';
import { SubscriptionsList } from './SubscriptionsList';

const SettingsTitle = () => {
	return (
		<Heading level={ 1 }>
			{ __( 'Farcaster Settings', 'farcaster-wp' ) }
		</Heading>
	);
};

const SaveButton = ( { onClick } ) => {
	return (
		<Button variant="primary" onClick={ onClick } __next40pxDefaultSize>
			{ __( 'Save', 'farcaster-wp' ) }
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
	} = useSettings();

	const { manifest, fetchManifest } = useManifest();

	return (
		<>
			<SettingsTitle />
			<Notices />
			<Panel header="Frames">
				<PanelBody>
					<PanelRow>
						<FramesEnabledControl
							value={ framesEnabled }
							onChange={ ( value ) => setFramesEnabled( value ) }
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody
					title={ __( 'Frame Button', 'farcaster-wp' ) }
					initialOpen={ framesEnabled }
				>
					<PanelRow>
						<VStack spacing={ 4 }>
							<UseTitleAsButtonTextControl
								value={ useTitleAsButtonText }
								onChange={ setUseTitleAsButtonText }
							/>
							{ useTitleAsButtonText !== true && (
								<ButtonTextControl
									value={ buttonText }
									onChange={ setButtonText }
								/>
							) }
						</VStack>
					</PanelRow>
				</PanelBody>
				<PanelBody
					title={ __( 'Splash Background Color', 'farcaster-wp' ) }
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
					title={ __( 'Frame Splash Image', 'farcaster-wp' ) }
					initialOpen={ framesEnabled }
				>
					<PanelRow>
						<ImageUploadControl
							value={ splashImage }
							onChange={ setSplashImage }
							labelText={ __(
								'Image will be displayed as 200x200px.',
								'farcaster-wp'
							) }
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody
					title={ __( 'Fallback Frame Image', 'farcaster-wp' ) }
					initialOpen={ framesEnabled }
				>
					<PanelRow>
						<ImageUploadControl
							labelText={ __(
								'Image will be displayed in 3:2 aspect ratio.',
								'farcaster-wp'
							) }
							value={ fallbackImage }
							onChange={ setFallbackImage }
						/>
					</PanelRow>
				</PanelBody>
			</Panel>
			<Panel header="Notifications">
				<PanelBody>
					<PanelRow>
						<NotificationsEnabledControl
							value={ notificationsEnabled }
							onChange={ setNotificationsEnabled }
						/>
					</PanelRow>
					<PanelRow>
						<SubscriptionsList />
					</PanelRow>
				</PanelBody>
			</Panel>
			<Panel header="Manifest">
				<PanelBody
					title={ __( 'Manifest Validation', 'farcaster-wp' ) }
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
