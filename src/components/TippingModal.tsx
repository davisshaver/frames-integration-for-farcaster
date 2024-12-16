import sdk from '@farcaster/frame-sdk';
import {
	createRoot,
	useCallback,
	useEffect,
	useMemo,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Action, Fab } from 'react-tiny-fab';
import 'react-tiny-fab/dist/styles.css';
import './tipping-modal.scss';
import {
	useAccount,
	useConnect,
	useDisconnect,
	useSendTransaction,
	useSwitchChain,
	useWaitForTransactionReceipt,
} from 'wagmi';
import { parseEther } from 'viem';
import { base, mainnet, optimism, zora } from 'wagmi/chains';

import { truncateAddress } from '../utils/truncateAddress';
import { FarcasterLogo } from './FarcasterLogo';
import Provider, { config } from './WagmiProvider';

const tippingAddress = window.farcasterWP?.tippingAddress;
const tippingAmounts = window.farcasterWP?.tippingAmounts;

export function FAB() {
	const [ isTipping, setIsTipping ] = useState< boolean >( false );
	const { address, isConnected, chainId } = useAccount();
	const { disconnect } = useDisconnect();
	const { connect } = useConnect();
	const {
		data,
		error: sendTxError,
		isError: isSendTxError,
		isPending: isSendTxPending,
		reset: resetSendTx,
		sendTransaction,
	} = useSendTransaction();

	const { switchChain } = useSwitchChain();

	const { isLoading: isConfirming, isSuccess: isConfirmed } =
		useWaitForTransactionReceipt( {
			hash: data,
		} );

	const handleSend = useCallback(
		( sparks: number ) => {
			sendTransaction( {
				to: tippingAddress,
				value: parseEther( ( sparks / 1000000 ).toString() ),
			} );
		},
		[ sendTransaction ]
	);

	const sendTipTitle = useMemo( () => {
		if ( isConfirming ) {
			return __( 'Confirming tip…', 'farcaster-wp' );
		}
		if ( isConfirmed ) {
			return __( 'Tip sent!', 'farcaster-wp' );
		}
		if ( isSendTxError && isTipping ) {
			return __( 'Tipping error, try again', 'farcaster-wp' );
		}
		if ( isTipping ) {
			return __( 'Cancel tipping', 'farcaster-wp' );
		}
		if ( ! isConnected ) {
			return __( 'Connect your wallet to tip', 'farcaster-wp' );
		}
		return __( 'Tip the creator of this site', 'farcaster-wp' );
	}, [ isConfirming, isConfirmed, isSendTxError, isConnected, isTipping ] );

	useEffect( () => {
		if ( isTipping ) {
			document.body.style.overflow = 'hidden';
			return () => {
				document.body.style.overflow = 'unset';
			};
		}
		document.body.style.overflow = 'unset';
	}, [ isTipping ] );

	const openWarpcastUrl = useCallback( () => {
		const frameData = document
			.querySelector( 'meta[name="fc:frame"]' )
			?.getAttribute( 'content' );
		const frameDataJson = JSON.parse( frameData );
		const buttonTitle = window.farcasterWP?.castText;
		const buttonUrl = encodeURIComponent( frameDataJson.button.action.url );
		const url = `https://warpcast.com/~/compose?text=${ buttonTitle }&embeds[]=${ buttonUrl }`;
		sdk.actions.openUrl( url );
	}, [] );

	return (
		<>
			{ isTipping && (
				<div
					className={ isConfirmed ? 'pseudo-glow' : '' }
					style={ {
						alignItems: 'stretch',
						backgroundColor: '#472A91',
						display: 'flex',
						flexDirection: 'column',
						height: '100%',
						left: 0,
						overflow: 'scroll',
						position: 'fixed',
						top: 0,
						width: '100%',
						zIndex: 1000,
					} }
				>
					<button
						aria-label="Close tipping modal"
						onClick={ () => {
							if ( isSendTxError ) {
								resetSendTx();
							}
							setIsTipping( false );
						} }
						title="Close tipping modal"
						style={ {
							backgroundColor: 'transparent',
							border: 'none',
							color: 'white',
							cursor: 'pointer',
							fontSize: '1rem',
							fontWeight: 'bold',
							position: 'absolute',
							right: '0.25rem',
							top: '0.25rem',
						} }
					>
						x
					</button>
					<h2
						style={ {
							color: 'white',
							fontSize: '2rem',
							fontWeight: 'bold',
							marginBottom: 0,
							marginTop: '3rem',
							textAlign: 'center',
						} }
					>
						{ __( 'Tip the creator of this site', 'farcaster-wp' ) }
					</h2>
					<p
						style={ {
							color: 'white',
							fontSize: '0.75em',
							marginBottom: '1rem',
							padding: '.5rem .5rem 0 .5rem',
							textAlign: 'center',
						} }
					>
						{ isConfirmed && (
							<>
								{ __( 'Tip sent!', 'farcaster-wp' ) }{ ' ' }
								<button
									style={ {
										backgroundColor: 'transparent',
										border: 'none',
										color: 'white',
										cursor: 'pointer',
										display: 'inline',
										fontFamily: 'inherit',
										fontSize: 'inherit',
										margin: 0,
										padding: 0,
										textDecoration: 'underline',
									} }
									onClick={ () => setIsTipping( false ) }
								>
									{ __(
										'Click here to keep reading.',
										'farcaster-wp'
									) }
								</button>
							</>
						) }
						{ isConfirming && sendTipTitle }
						{ ! isConfirming &&
							! isConfirmed &&
							isSendTxPending && (
								<>{ __( 'Tip is pending…', 'farcaster-wp' ) }</>
							) }
						{ ! isConfirmed &&
							! isConfirming &&
							! isSendTxPending &&
							isConnected && (
								<>
									{ __(
										'You are connected on',
										'farcaster-wp'
									) }{ ' ' }
									<select
										style={ {
											backgroundColor: 'transparent',
											border: 'none',
											color: 'white',
											cursor: 'pointer',
											display: 'inline',
											fontFamily: 'inherit',
											fontSize: 'inherit',
											margin: 0,
											padding: 0,
											textDecoration: 'underline',
										} }
										value={ chainId }
										onChange={ ( e ) => {
											switchChain( {
												chainId: Number(
													e.target.value
												),
											} );
										} }
									>
										<option value={ base.id }>
											{ base.name }
										</option>
										<option value={ mainnet.id }>
											{ mainnet.name }
										</option>
										<option value={ optimism.id }>
											{ optimism.name }
										</option>
										<option value={ zora.id }>
											{ zora.name }
										</option>
									</select>{ ' ' }
									{ __( 'with the address', 'farcaster-wp' ) }{ ' ' }
									{ truncateAddress( address ) }
									{ '.' }{ ' ' }
									<button
										style={ {
											backgroundColor: 'transparent',
											border: 'none',
											color: 'white',
											cursor: 'pointer',
											display: 'inline',
											fontFamily: 'inherit',
											fontSize: 'inherit',
											margin: 0,
											padding: 0,
											textDecoration: 'underline',
										} }
										onClick={ () => disconnect() }
									>
										{ __( 'Disconnect.', 'farcaster-wp' ) }
									</button>
								</>
							) }
						{ ! isConfirmed && ! isConfirming && ! isConnected && (
							<>
								{ __(
									'You are not connected to a wallet.',
									'farcaster-wp'
								) }{ ' ' }
								<button
									style={ {
										backgroundColor: 'transparent',
										border: 'none',
										color: 'white',
										cursor: 'pointer',
										display: 'inline',
										fontFamily: 'inherit',
										fontSize: 'inherit',
										margin: 0,
										padding: 0,
										textDecoration: 'underline',
									} }
									onClick={ () =>
										connect( {
											connector: config.connectors[ 0 ],
										} )
									}
								>
									{ __(
										'Connect your wallet to tip.',
										'farcaster-wp'
									) }
								</button>
							</>
						) }
					</p>
					{ tippingAmounts.map( ( sparks ) => (
						<button
							aria-label={ `Tip ${ sparks.toLocaleString() } sparks` }
							style={ {
								backgroundColor: '#7C65C1',
								border: 'none',
								color: 'white',
								cursor:
									isConfirming || isConfirmed
										? 'default'
										: 'pointer',
								fontSize: '1.5rem',
								fontWeight: 'bold',
								margin: '0.5rem',
								padding: '1rem',
								opacity: isConfirming || isConfirmed ? 0.5 : 1,
							} }
							key={ sparks }
							onClick={ () => handleSend( sparks ) }
							title={ `Tip ${ sparks.toLocaleString() } sparks` }
							disabled={ isConfirming || isConfirmed }
						>
							✧{ sparks.toLocaleString() }
						</button>
					) ) }
					{ ! isConfirmed && ! isConfirming && isSendTxError && (
						<div
							style={ {
								backgroundColor: 'white',
								color: 'red',
								display: 'flex',
								flexDirection: 'column',
								margin: '0.5rem',
								padding: '1rem',
							} }
						>
							<p
								style={ {
									fontSize: '0.75rem',
									margin: '0.5rem',
									textAlign: 'center',
								} }
							>
								{ sendTxError.message }
							</p>
							<button
								onClick={ () => {
									resetSendTx();
								} }
								style={ {
									backgroundColor: 'white',
									border: '1px solid red',
									color: 'red',
									cursor: 'pointer',
									fontSize: '1rem',
									fontWeight: 'bold',
									margin: '0.5rem',
									padding: '1rem',
								} }
							>
								{ __( 'Try again', 'farcaster-wp' ) }
							</button>
						</div>
					) }
					<p
						style={ {
							color: 'white',
							fontSize: '0.75rem',
							textAlign: 'center',
						} }
					>
						{ __( '✧ is a new unit of Ethereum.', 'farcaster-wp' ) }{ ' ' }
						<a
							href="https://zora.co/writings/sparks"
							target="_blank"
							rel="noopener noreferrer"
							onClick={ ( event ) => {
								event.preventDefault();
								sdk.actions.openUrl(
									'https://zora.co/writings/sparks'
								);
							} }
						>
							{ __( 'Learn more.', 'farcaster-wp' ) }
						</a>
					</p>
				</div>
			) }
			<Fab
				mainButtonStyles={ {
					padding: 0,
				} }
				// event={ 'click' }
				// actionButtonStyles={ {
				// 	padding: 0,
				// } }
				style={ { bottom: 0, right: 0 } }
				icon={ <FarcasterLogo /> }
				alwaysShowTitle={ false }
				// onClick={ () => {} }
			>
				<Action
					style={ {
						backgroundColor: '#472A91',
						color: 'white',
					} }
					text="Compose a cast with this link"
					onClick={ openWarpcastUrl }
				>
					Cast
				</Action>
				{ tippingAddress && tippingAmounts.length > 0 && (
					<Action
						style={ {
							backgroundColor: '#472A91',
							color: 'white',
						} }
						text={ sendTipTitle }
						onClick={ () => {
							if ( isTipping && isSendTxError ) {
								resetSendTx();
							}
							setIsTipping( ! isTipping );
						} }
					>
						{ isTipping ? 'x' : 'Tip' }
					</Action>
				) }
			</Fab>
		</>
	);
}

export const renderTippingModal = () => {
	const tippingModalElement = document.getElementById(
		'farcaster-wp-tipping-modal'
	);
	if ( ! tippingModalElement ) {
		return;
	}
	const root = createRoot( tippingModalElement );
	root.render(
		<Provider>
			<FAB />
		</Provider>
	);
};
