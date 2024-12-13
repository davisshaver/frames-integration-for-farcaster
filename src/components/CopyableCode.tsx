import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { copy, download } from '@wordpress/icons';

interface CopyableCodeProps {
	content: string;
	showCopyButton?: boolean;
	showDownloadButton?: boolean;
	downloadFilename?: string;
}

const CopyableCode = ( {
	content,
	showCopyButton = true,
	showDownloadButton = false,
	downloadFilename = 'download.json',
}: CopyableCodeProps ) => {
	const [ isCopied, setIsCopied ] = useState( false );

	const handleCopy = async () => {
		await navigator.clipboard.writeText( content );
		setIsCopied( true );
		setTimeout( () => setIsCopied( false ), 2000 );
	};

	const handleDownload = () => {
		const blob = new Blob( [ content ], { type: 'application/json' } );
		const url = URL.createObjectURL( blob );
		const a = document.createElement( 'a' );
		a.href = url;
		a.download = downloadFilename;
		document.body.appendChild( a );
		a.click();
		document.body.removeChild( a );
		URL.revokeObjectURL( url );
	};

	return (
		<div style={ { position: 'relative', maxWidth: '100%' } }>
			<pre
				style={ {
					background: '#f0f0f0',
					padding: '1rem',
					borderRadius: '4px',
					overflow: 'auto',
					whiteSpace: 'break-spaces',
					lineBreak: 'anywhere',
				} }
			>
				{ content }
			</pre>
			{ showCopyButton && (
				<Button
					icon={ copy }
					onClick={ handleCopy }
					style={ {
						position: 'absolute',
						top: '8px',
						right: '8px',
						background: 'white',
						border: '1px solid #ccc',
					} }
				>
					{ isCopied
						? __( 'Copied!', 'farcaster-wp' )
						: __( 'Copy', 'farcaster-wp' ) }
				</Button>
			) }
			{ showDownloadButton && (
				<Button
					icon={ download }
					onClick={ handleDownload }
					style={ {
						position: 'absolute',
						bottom: '8px',
						right: '8px',
						background: 'white',
						border: '1px solid #ccc',
					} }
				>
					{ __( 'Download', 'farcaster-wp' ) }
				</Button>
			) }
		</div>
	);
};

export { CopyableCode };
