import sdk, { type FrameContext } from '@farcaster/frame-sdk';
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
	useConfig,
} from 'wagmi';
import { getChains } from '@wagmi/core';
import { parseEther } from 'viem';

import { truncateAddress } from '../utils/truncateAddress';
import { FarcasterLogo } from './FarcasterLogo';
import Provider from './WagmiProvider';

const tippingAddress = window.farcasterWP?.tippingAddress;
const tippingAmounts = window.farcasterWP?.tippingAmounts;

export function FAB( { context }: { context: FrameContext } ) {
	const [ isAdded, setIsAdded ] = useState< boolean >( false );
	const [ isSubscribed, setIsSubscribed ] = useState< boolean >( false );
	useEffect( () => {
		if ( context?.client?.added ) {
			setIsAdded( true );
		}
		if (
			context?.client?.notificationDetails ||
			context?.location?.type === 'notification'
		) {
			setIsSubscribed( true );
		}
	}, [ context ] );
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

	const config = useConfig();
	const chains = getChains( config );

	const {
		switchChain,
		error: switchChainError,
		isError: isSwitchChainError,
		isPending: isSwitchChainPending,
	} = useSwitchChain();

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
			return __( 'Confirming tip…', 'frames-integration-for-farcaster' );
		}
		if ( isConfirmed ) {
			return __( 'Tip sent!', 'frames-integration-for-farcaster' );
		}
		if ( isSendTxError && isTipping ) {
			return __(
				'Tipping error, try again',
				'frames-integration-for-farcaster'
			);
		}
		if ( isTipping ) {
			return __( 'Cancel tipping', 'frames-integration-for-farcaster' );
		}
		if ( ! isConnected ) {
			return __(
				'Connect your wallet to tip',
				'frames-integration-for-farcaster'
			);
		}
		return __(
			'Tip the creator of this site',
			'frames-integration-for-farcaster'
		);
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
						paddingBottom: '1rem',
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
							fontSize: '1.75rem',
							fontWeight: 'bold',
							margin: '3rem .5rem 0 .5rem',
							textAlign: 'center',
						} }
					>
						{ __(
							'Tip the creator of this site',
							'frames-integration-for-farcaster'
						) }
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
								{ __(
									'Tip sent!',
									'frames-integration-for-farcaster'
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
									onClick={ () => setIsTipping( false ) }
								>
									{ __(
										'Click here to keep reading.',
										'frames-integration-for-farcaster'
									) }
								</button>
							</>
						) }
						{ isConfirming && sendTipTitle }
						{ ! isConfirming &&
							! isConfirmed &&
							isSendTxPending && (
								<>
									{ __(
										'Tip is pending…',
										'frames-integration-for-farcaster'
									) }
								</>
							) }
						{ ! isConfirmed &&
							! isConfirming &&
							! isSendTxPending &&
							isConnected && (
								<>
									{ __(
										'You are connected on',
										'frames-integration-for-farcaster'
									) }{ ' ' }
									<select
										style={ {
											backgroundColor: 'transparent',
											border: 'none',
											color: 'white',
											cursor: 'pointer',
											display: 'inline',
											filter: isSwitchChainPending
												? 'blur(1.1px)'
												: 'none',
											fontStyle: isSwitchChainPending
												? 'italic'
												: 'normal',
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
										{ chains.map( ( chain ) => (
											<option
												value={ chain.id }
												key={ chain.id }
											>
												{ chain.name }
											</option>
										) ) }
									</select>{ ' ' }
									{ __(
										'with the address',
										'frames-integration-for-farcaster'
									) }{ ' ' }
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
										{ __(
											'Disconnect.',
											'frames-integration-for-farcaster'
										) }
									</button>
									{ isSwitchChainError && (
										<div
											style={ {
												backgroundColor: 'white',
												color: 'red',
												display: 'flex',
												flexDirection: 'column',
												margin: '.25rem .5rem 1rem .5rem',
												padding: '0.5rem',
											} }
										>
											<p
												style={ {
													fontSize: '0.75rem',
													margin: '0.5rem',
													textAlign: 'center',
												} }
											>
												{ switchChainError.message }
											</p>
										</div>
									) }
								</>
							) }
						{ ! isConfirmed && ! isConfirming && ! isConnected && (
							<>
								{ __(
									'You are not connected to a wallet.',
									'frames-integration-for-farcaster'
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
										'frames-integration-for-farcaster'
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
								{ __(
									'Try again',
									'frames-integration-for-farcaster'
								) }
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
						{ __(
							'✧ is a new unit of Ethereum.',
							'frames-integration-for-farcaster'
						) }{ ' ' }
						<a
							style={ {
								color: 'white',
								textDecoration: 'underline',
							} }
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
							{ __(
								'Learn more.',
								'frames-integration-for-farcaster'
							) }
						</a>
					</p>
				</div>
			) }
			<Fab
				data-is-added={ isAdded }
				data-is-subscribed={ isSubscribed }
				mainButtonStyles={ {
					backgroundColor: 'transparent',
					borderRadius: 0,
					boxShadow: 'none',
					filter: 'drop-shadow( 0 0 4px rgba(0, 0, 0, .14))',
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

export const renderTippingModal = ( context ) => {
	const tippingModalElement = document.getElementById(
		'farcaster-wp-tipping-modal'
	);
	if ( ! tippingModalElement ) {
		return;
	}
	const root = createRoot( tippingModalElement );
	root.render(
		<Provider>
			<FAB context={ context } />
		</Provider>
	);
};
