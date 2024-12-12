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
} from './Controls';
import { ManifestViewer } from './ManifestViewer';

const SettingsTitle = () => {
	return (
		<Heading level={ 1 }>
			{ __( 'Farcaster Settings', 'wp-farcaster' ) }
		</Heading>
	);
};

const SaveButton = ( { onClick } ) => {
	return (
		<Button variant="primary" onClick={ onClick } __next40pxDefaultSize>
			{ __( 'Save', 'wp-farcaster' ) }
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
	} = useSettings();

	const { manifest } = useManifest();

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
					title={ __( 'Frame Button', 'wp-farcaster' ) }
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
					title={ __( 'Splash Background Color', 'wp-farcaster' ) }
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
					title={ __( 'Frame Splash Image', 'wp-farcaster' ) }
					initialOpen={ framesEnabled }
				>
					<PanelRow>
						<ImageUploadControl
							value={ splashImage }
							onChange={ setSplashImage }
							labelText={ __(
								'Image will be displayed as 200x200px.',
								'wp-farcaster'
							) }
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody
					title={ __( 'Fallback Frame Image', 'wp-farcaster' ) }
					initialOpen={ framesEnabled }
				>
					<PanelRow>
						<ImageUploadControl
							labelText={ __(
								'Image will be displayed in 3:2 aspect ratio.',
								'wp-farcaster'
							) }
							value={ fallbackImage }
							onChange={ setFallbackImage }
						/>
					</PanelRow>
				</PanelBody>
			</Panel>
			<Panel header="Manifest">
				<PanelBody
					title={ __( 'Manifest Validation', 'wp-farcaster' ) }
				>
					<PanelRow>
						<ManifestViewer currentManifest={ manifest } />
					</PanelRow>
				</PanelBody>
			</Panel>
			<SaveButton onClick={ saveSettings } />
		</>
	);
};

export { SettingsPage };
