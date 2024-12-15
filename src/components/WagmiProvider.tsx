import { createConfig, http, WagmiProvider } from 'wagmi';
import { base, mainnet, optimism, zora } from 'wagmi/chains';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { frameConnector } from '../utils/connector';

export const config = createConfig( {
	chains: [ optimism, base, mainnet, zora ],
	transports: {
		[ base.id ]: http(),
		[ mainnet.id ]: http(),
		[ optimism.id ]: http(),
		[ zora.id ]: http(),
	},
	connectors: [ frameConnector() ],
} );

const queryClient = new QueryClient();

export default function Provider( {
	children,
}: {
	children: React.ReactNode;
} ) {
	return (
		<WagmiProvider config={ config }>
			<QueryClientProvider client={ queryClient }>
				{ children }
			</QueryClientProvider>
		</WagmiProvider>
	);
}
