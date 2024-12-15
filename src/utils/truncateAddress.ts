/**
 * Truncates an address to 14 characters and adds 12 characters at the end.
 *
 * @param {string} address - The address to truncate.
 * @return {string} The truncated address.
 */
export const truncateAddress = ( address: string ) => {
	if ( ! address ) {
		return '';
	}
	return `${ address.slice( 0, 14 ) }...${ address.slice( -12 ) }`;
};
