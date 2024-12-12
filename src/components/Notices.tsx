import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { NoticeList } from '@wordpress/components';

const Notices = () => {
	const { removeNotice } = useDispatch( noticesStore );
	const notices = useSelect(
		( select ) => select( noticesStore ).getNotices(),
		[]
	);

	if ( notices.length === 0 ) {
		return null;
	}

	const formattedNotices = notices.map( ( notice ) => ( {
		id: notice.id,
		content: notice.content,
	} ) );

	return (
		<NoticeList notices={ formattedNotices } onRemove={ removeNotice } />
	);
};

export { Notices };
