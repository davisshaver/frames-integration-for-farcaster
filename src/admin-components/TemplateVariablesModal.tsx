import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from 'react';

const TemplateVariablesModal = () => {
	const [ isOpen, setOpen ] = useState( false );
	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );

	return (
		<>
			<small>
				{ __(
					'The post URL will be automatically added as a cast embed.',
					'frames-integration-for-farcaster'
				) }{ ' ' }
				<Button
					variant="link"
					onClick={ openModal }
					style={ { fontSize: 'inherit' } }
				>
					{ __(
						'See other template variables.',
						'frames-integration-for-farcaster'
					) }
				</Button>
			</small>
			{ isOpen && (
				<Modal
					title={ __(
						'Template variables',
						'frames-integration-for-farcaster'
					) }
					onRequestClose={ closeModal }
				>
					<ul>
						<li>
							<span>#title# - Post Title</span>
						</li>
						<li>
							<span>#author# - Post Author</span>
						</li>
						<li>
							<span>#excerpt# - Post Excerpt</span>
						</li>
						<li>
							<span>#date# - Post Date</span>
						</li>
					</ul>
				</Modal>
			) }
		</>
	);
};

export { TemplateVariablesModal };
