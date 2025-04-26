import { z } from 'zod';

// Define Zod schemas
const FrameConfigSchema = z.object( {
	version: z.literal( '1', { message: 'Version must be 1' } ),
	name: z
		.string( {
			invalid_type_error: 'Name must be a string',
			required_error: 'Name is required',
		} )
		.max( 32, { message: 'Name must be 32 characters or less' } ),
	homeUrl: z
		.string( {
			invalid_type_error: 'Home URL must be a string',
			required_error: 'Home URL is required',
		} )
		.max( 1024, { message: 'Home URL must be 1024 characters or less' } ),
	iconUrl: z
		.string( {
			invalid_type_error: 'Icon URL must be a string',
			required_error: 'Icon URL is required',
		} )
		.max( 1024, { message: 'Icon URL must be 1024 characters or less' } ),
	splashImageUrl: z
		.string( {
			invalid_type_error: 'Splash image URL must be a string',
		} )
		.max( 1024, {
			message: 'Splash image URL must be 1024 characters or less',
		} )
		.optional(),
	imageUrl: z
		.string( {
			invalid_type_error: 'Image URL must be a string',
			required_error: 'Image URL is required',
		} )
		.max( 1024, {
			message: 'Splash image URL must be 1024 characters or less',
		} ),
	buttonTitle: z
		.string( {
			invalid_type_error: 'Button title must be a string',
			required_error: 'Button title is required',
		} )
		.max( 32, { message: 'Button title must be 32 characters or less' } ),
	splashBackgroundColor: z
		.string( {
			invalid_type_error: 'Splash background color must be a string',
		} )
		.regex( /^#[0-9A-Fa-f]{3,6}$/, {
			message: 'Splash background color must be a valid hex color',
		} )
		.optional(),
	webhookUrl: z
		.string( {
			invalid_type_error: 'Webhook URL must be a string',
		} )
		.max( 1024, {
			message: 'Webhook URL must be 1024 characters or less',
		} )
		.optional(),
	tagline: z
		.string( {
			invalid_type_error: 'Tagline must be a string',
		} )
		.max( 30, { message: 'Tagline must be 30 characters or less' } )
		.optional(),
	description: z
		.string( {
			invalid_type_error: 'Description must be a string',
		} )
		.max( 170, { message: 'Description must be 170 characters or less' } )
		.optional(),
	primaryCategory: z
		.enum(
			[
				'games',
				'social',
				'finance',
				'utility',
				'productivity',
				'health-fitness',
				'news-media',
				'music',
				'shopping',
				'education',
				'art-creativity',
			],
			{
				message: 'Primary category must be a valid category',
			}
		)
		.optional(),
	noindex: z
		.boolean( {
			invalid_type_error: 'Noindex must be a boolean',
		} )
		.optional(),
	heroImageUrl: z
		.string( {
			invalid_type_error: 'Hero image URL must be a string',
		} )
		.max( 1024, {
			message: 'Hero image URL must be 1024 characters or less',
		} )
		.optional(),
	tags: z
		.array(
			z
				.string()
				.max( 20, { message: 'Tag must be 20 characters or less' } ),
			{
				invalid_type_error: 'Tags must be an array',
			}
		)
		.max( 5, { message: 'Tags must be 5 or less' } )
		.optional(),
	ogTitle: z
		.string( {
			invalid_type_error: 'OG title must be a string',
		} )
		.max( 30, { message: 'OG title must be 30 characters or less' } )
		.optional(),
	ogDescription: z
		.string( {
			invalid_type_error: 'OG description must be a string',
		} )
		.max( 100, {
			message: 'OG description must be 100 characters or less',
		} )
		.optional(),
} );

const TriggerConfigSchema = z.discriminatedUnion( 'type', [
	z.object( {
		type: z.literal( 'cast', {
			message: 'Trigger type must be cast',
		} ),
		id: z.string( {
			required_error: 'Trigger ID is required',
		} ),
		url: z.string( {
			required_error: 'Trigger URL is required',
		} ),
		name: z
			.string( {
				invalid_type_error: 'Trigger name must be a string',
			} )
			.optional(),
	} ),
	z.object( {
		type: z.literal( 'composer', {
			message: 'Trigger type must be composer',
		} ),
		id: z.string( {
			invalid_type_error: 'Trigger ID must be a string',
			required_error: 'Trigger ID is required',
		} ),
		url: z.string( {
			invalid_type_error: 'Trigger URL must be a string',
			required_error: 'Trigger URL is required',
		} ),
		name: z
			.string( {
				invalid_type_error: 'Trigger name must be a string',
			} )
			.optional(),
	} ),
] );

export const FarcasterManifestSchema = z.object( {
	accountAssociation: z.object(
		{
			header: z.string( {
				invalid_type_error: 'Header must be a string',
				required_error: 'Header is required',
			} ),
			payload: z.string( {
				invalid_type_error: 'Payload must be a string',
				required_error: 'Payload is required',
			} ),
			signature: z.string( {
				invalid_type_error: 'Signature must be a string',
				required_error: 'Signature is required',
			} ),
		},
		{
			invalid_type_error: 'Account association must be an object',
		}
	),
	frame: FrameConfigSchema,
	triggers: z
		.array( TriggerConfigSchema, {
			invalid_type_error: 'Triggers must be an array',
		} )
		.optional(),
} );

export type FarcasterManifest = z.infer< typeof FarcasterManifestSchema >;
