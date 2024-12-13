interface ToastOptions {
	buttonText?: string;
	duration?: number;
	message: string;
	onButtonClick?: () => void;
	type?: 'success' | 'error';
}

export const showToast = ( {
	buttonText,
	duration = 10000,
	message,
	onButtonClick,
	type = 'success',
}: ToastOptions ) => {
	const toast = document.createElement( 'div' );

	const backgroundColorMap = {
		error: '#FF453A',
		success: '#472A91',
	};

	toast.style.cssText = `
        align-items: center;
        background: ${ backgroundColorMap[ type ] };
        border-radius: 4px;
        bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        color: white;
        display: flex;
        font-size: 14px;
        gap: 12px;
        left: 50%;
        max-width: calc(100% - 80px);
        padding: 12px 24px;
        position: fixed;
        transform: translateX(-50%);
        width: max-content;
        z-index: 10000;
    `;

	toast.textContent = message;

	if ( buttonText && onButtonClick ) {
		const button = document.createElement( 'button' );
		button.textContent = buttonText;
		button.style.cssText = `
            background: #7C65C1;
            border-radius: 4px;
            border: none;
            color: white;
            cursor: pointer;
            padding: 8px 16px;
        `;
		button.onclick = () => {
			onButtonClick();
			document.body.removeChild( toast );
		};
		toast.appendChild( button );
	}

	document.body.appendChild( toast );

	setTimeout( () => {
		if ( document.body.contains( toast ) ) {
			document.body.removeChild( toast );
		}
	}, duration );
};
