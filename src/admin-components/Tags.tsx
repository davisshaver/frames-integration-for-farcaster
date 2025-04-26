import { __ } from '@wordpress/i18n';
import { FormTokenField } from '@wordpress/components';

interface TagsProps {
	value: string[];
	onChange: ( tokens: string[] ) => void;
}

/**
 * Tags component using FormTokenField
 *
 * @param {TagsProps} props          - Component props
 * @param {string[]}  props.value    - Array of tag strings
 * @param {Function}  props.onChange - Function called when tags change
 * @return {JSX.Element} The Tags component
 */
const Tags = ( { value, onChange }: TagsProps ) => {
	return (
		<div className="components-base-control" style={ { width: '100%' } }>
			<FormTokenField
				value={ value }
				onChange={ onChange }
				saveTransform={ ( token ) => {
					return token
						.toLowerCase()
						.trim()
						.replace( / /g, '-' )
						.replace( /[^a-z0-9-]/g, '' );
				} }
				label={ __(
					'Descriptive Tags for Filtering/Search',
					'frames-integration-for-farcaster'
				) }
				placeholder={ __(
					'Add up to five tags…',
					'frames-integration-for-farcaster'
				) }
				maxLength={ 5 }
				__nextHasNoMarginBottom
			/>
			<p
				style={ {
					marginTop: '8px',
					fontSize: '12px',
					color: 'rgb(117, 117, 117)',
				} }
			>
				{ __(
					'Use 3–5 high-volume terms; no spaces, no repeats, no brand names. Use singular form.',
					'frames-integration-for-farcaster'
				) }
			</p>
		</div>
	);
};

export default Tags;
