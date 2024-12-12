import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState, useMemo } from '@wordpress/element';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { Notice, __experimentalText as Text } from '@wordpress/components';
import { FarcasterManifest, FarcasterManifestSchema } from '../utils/manifest';
import { CopyableCode } from './CopyableCode';

interface ManifestMismatches {
	count: number;
	details: {
		name: boolean;
		homeUrl: boolean;
		iconUrl: boolean;
		splashImageUrl: boolean;
		splashBackgroundColor: boolean;
	};
}

const ManifestViewer = ( {
	currentManifest,
}: {
	currentManifest: FarcasterManifest;
	mismatches?: ManifestMismatches;
} ) => {
	const [ manifest, setManifest ] = useState< FarcasterManifest | null >(
		null
	);
	const [ validationError, setValidationError ] = useState< string >( '' );
	const [ fetchError, setFetchError ] = useState< string >( '' );
	const [ isLoading, setIsLoading ] = useState( true );

	const mismatches = useMemo( () => {
		if ( ! manifest || ! currentManifest ) {
			return null;
		}

		const details = {
			name: manifest?.frame?.name !== currentManifest?.frame?.name,
			homeUrl:
				manifest?.frame?.homeUrl !== currentManifest?.frame?.homeUrl,
			iconUrl:
				manifest?.frame?.iconUrl !== currentManifest?.frame?.iconUrl,
			splashImageUrl:
				manifest?.frame?.splashImageUrl !==
				currentManifest?.frame?.splashImageUrl,
			splashBackgroundColor:
				manifest?.frame?.splashBackgroundColor !==
				currentManifest?.frame?.splashBackgroundColor,
		};

		const count = Object.values( details ).filter( Boolean ).length;

		return { count, details } as ManifestMismatches;
	}, [ manifest, currentManifest ] );

	const validateManifest = ( data: unknown ) => {
		const result = FarcasterManifestSchema.safeParse( data );
		if ( ! result.success ) {
			setValidationError( result.error.message );
			return data; // Return unvalidated data to still display it
		}
		setValidationError( '' );
		return result.data;
	};

	useEffect( () => {
		const fetchManifest = async () => {
			try {
				const controller = new AbortController();
				const timeoutId = setTimeout(
					() => controller.abort( 'Manifest retrieval timed out' ),
					2000
				);

				const response = await fetch( '/.well-known/farcaster.json', {
					signal: controller.signal,
				} );
				clearTimeout( timeoutId );

				if ( ! response.ok ) {
					throw new Error( 'Manifest file not found' );
				}
				const data = await response.json();
				const processedData = validateManifest( data );
				setManifest( processedData );
				setFetchError( '' );
			} catch ( err ) {
				setFetchError(
					err instanceof Error
						? `${ __( 'Error:', 'farcaster-wp' ) } ${ err.message }`
						: __(
								'Farcaster manifest file not found or request timed out at /.well-known/farcaster.json',
								'farcaster-wp'
						  )
				);
			} finally {
				setIsLoading( false );
			}
		};
		fetchManifest();
	}, [] );

	if ( isLoading ) {
		return <Text>{ __( 'Loading manifest…', 'farcaster-wp' ) }</Text>;
	}

	return (
		<div className="manifest-viewer">
			{ fetchError && (
				<>
					<Notice status="error" isDismissible={ false }>
						{ fetchError }
					</Notice>
					<div style={ { marginTop: '8px' } } />
				</>
			) }
			<Text>
				{ __(
					'The Farcaster manifest file declares metadata for your frame application and defines supported triggers.',
					'farcaster-wp'
				) }{ ' ' }
				<a
					href="https://docs.farcaster.xyz/developers/frames/v2/spec#frame-manifest"
					target="_blank"
					rel="noopener noreferrer"
				>
					{ __(
						'Learn more about the manifest specification.',
						'farcaster-wp'
					) }
				</a>
			</Text>
			{ manifest && (
				<>
					<div style={ { marginTop: '16px' } }>
						<Text>
							{ __(
								'Current manifest contents:',
								'farcaster-wp'
							) }
						</Text>
					</div>
					<div style={ { marginTop: '8px' } } />
					<CopyableCode
						content={ JSON.stringify( manifest, null, 2 ) }
						showCopyButton={ false }
					/>
					{ validationError && (
						<Notice status="error" isDismissible={ false }>
							{ __(
								'Validation complete, manifest is not valid. Errors:',
								'farcaster-wp'
							) }{ ' ' }
							<ul style={ { margin: 0, paddingLeft: '1rem' } }>
								{ JSON.parse( validationError ).map(
									( error, index ) => (
										<li key={ index }>
											<strong>
												{ error.path.join( '.' ) }
											</strong>
											: { error.message }
										</li>
									)
								) }
							</ul>
						</Notice>
					) }
					{ ! validationError && (
						<Notice status="info" isDismissible={ false }>
							{ currentManifest && mismatches
								? sprintf(
										/* translators: %d: number of mismatches */
										__(
											'Validation complete, manifest is valid. %d mismatches found with current settings.',
											'farcaster-wp'
										),
										mismatches.count
								  )
								: __(
										'Validation complete, manifest is valid.',
										'farcaster-wp'
								  ) }
						</Notice>
					) }
					{ currentManifest && mismatches?.count > 0 && (
						<>
							{ mismatches.details.name && (
								<Notice
									status="warning"
									isDismissible={ false }
								>
									{ __(
										'The manifest name does not match the current site name.',
										'farcaster-wp'
									) }
								</Notice>
							) }
							{ mismatches.details.homeUrl && (
								<Notice
									status="warning"
									isDismissible={ false }
								>
									{ __(
										'The manifest home URL does not match the current site home URL.',
										'farcaster-wp'
									) }
								</Notice>
							) }
							{ mismatches.details.iconUrl && (
								<Notice
									status="warning"
									isDismissible={ false }
								>
									{ __(
										'The manifest icon URL does not match the current site icon URL.',
										'farcaster-wp'
									) }
								</Notice>
							) }
							{ mismatches.details.splashImageUrl && (
								<Notice
									status="warning"
									isDismissible={ false }
								>
									{ __(
										'The manifest splash image URL does not match the current site splash image URL.',
										'farcaster-wp'
									) }
								</Notice>
							) }
							{ mismatches.details.splashBackgroundColor && (
								<Notice
									status="warning"
									isDismissible={ false }
								>
									{ __(
										'The manifest splash background color does not match the current site splash background color.',
										'farcaster-wp'
									) }
								</Notice>
							) }
						</>
					) }

					{ validationError ||
						( mismatches?.count > 0 && (
							<>
								<div style={ { marginTop: '16px' } }>
									<Text>
										{ __(
											'Update manifest to match current settings? Here is the manifest data to reference:',
											'farcaster-wp'
										) }
									</Text>
								</div>
								<CopyableCode
									content={ JSON.stringify(
										currentManifest,
										null,
										2
									) }
								/>
							</>
						) ) }
				</>
			) }
		</div>
	);
};

export { ManifestViewer };
