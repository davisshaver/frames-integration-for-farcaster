import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { copy } from '@wordpress/icons';

interface CopyableCodeProps {
	content: string;
	showCopyButton?: boolean;
}

const CopyableCode = ( {
	content,
	showCopyButton = true,
}: CopyableCodeProps ) => {
	const [ isCopied, setIsCopied ] = useState( false );

	const handleCopy = async () => {
		await navigator.clipboard.writeText( content );
		setIsCopied( true );
		setTimeout( () => setIsCopied( false ), 2000 );
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
						? __( 'Copied!', 'wp-farcaster' )
						: __( 'Copy', 'wp-farcaster' ) }
				</Button>
			) }
		</div>
	);
};

export { CopyableCode };
