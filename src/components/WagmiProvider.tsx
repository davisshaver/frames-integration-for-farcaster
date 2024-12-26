import { createConfig, http, WagmiProvider } from 'wagmi';
import { base, type Chain, mainnet, optimism, zora } from 'wagmi/chains';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { farcasterFrame } from '@farcaster/frame-wagmi-connector/dist/connector';

const chainMap = {
	optimism,
	base,
	mainnet,
	zora,
} as { [ key: string ]: Chain };

const chainList = window.farcasterWP?.tippingChains.length
	? window.farcasterWP?.tippingChains
	: [ 'optimism', 'base', 'mainnet', 'zora' ];

const [ firstChain, ...otherChains ] = chainList.reduce( ( acc, chain ) => {
	if ( chainMap[ chain ] ) {
		acc.push( chainMap[ chain ] );
	}
	return acc;
}, [] as Chain[] );

const chains = [ firstChain, ...otherChains ] as [ Chain, ...Chain[] ];

const transports = chains.reduce( ( acc, chain ) => {
	acc[ chain.id ] = http();
	return acc;
}, {} );

const queryClient = new QueryClient();

export default function Provider( {
	children,
}: {
	children: React.ReactNode;
} ) {
	const config = createConfig( {
		chains,
		transports,
		connectors: [ farcasterFrame() ],
	} );
	return (
		<WagmiProvider config={ config }>
			<QueryClientProvider client={ queryClient }>
				{ children }
			</QueryClientProvider>
		</WagmiProvider>
	);
}
