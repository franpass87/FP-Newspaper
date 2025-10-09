/**
 * Cronaca di Viterbo - Gutenberg Blocks
 * @version 1.6.0
 */

const { registerBlockType } = wp.blocks;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, SelectControl, RangeControl } = wp.components;
const { ServerSideRender } = wp.serverSideRender || wp.components;

/**
 * Block: Proposte List
 */
registerBlockType('cdv/proposte-list', {
	title: 'CdV - Lista Proposte',
	icon: 'list-view',
	category: 'widgets',
	attributes: {
		limit: {
			type: 'number',
			default: 5,
		},
		quartiere: {
			type: 'string',
			default: '',
		},
		orderby: {
			type: 'string',
			default: 'date',
		},
	},
	edit: ({ attributes, setAttributes }) => {
		return (
			<div>
				<InspectorControls>
					<PanelBody title="Impostazioni">
						<RangeControl
							label="Numero proposte"
							value={attributes.limit}
							onChange={(limit) => setAttributes({ limit })}
							min={1}
							max={20}
						/>
						<SelectControl
							label="Quartiere"
							value={attributes.quartiere}
							options={[
								{ label: 'Tutti i quartieri', value: '' },
								...(cdvBlocks.quartieri || []),
							]}
							onChange={(quartiere) => setAttributes({ quartiere })}
						/>
						<SelectControl
							label="Ordina per"
							value={attributes.orderby}
							options={[
								{ label: 'Data', value: 'date' },
								{ label: 'Voti', value: 'votes' },
								{ label: 'Titolo', value: 'title' },
							]}
							onChange={(orderby) => setAttributes({ orderby })}
						/>
					</PanelBody>
				</InspectorControls>
				<ServerSideRender block="cdv/proposte-list" attributes={attributes} />
			</div>
		);
	},
	save: () => null, // Server-side render
});

/**
 * Block: Petizioni List
 */
registerBlockType('cdv/petizioni-list', {
	title: 'CdV - Lista Petizioni',
	icon: 'edit-page',
	category: 'widgets',
	attributes: {
		limit: {
			type: 'number',
			default: 5,
		},
		status: {
			type: 'string',
			default: 'aperte',
		},
	},
	edit: ({ attributes, setAttributes }) => {
		return (
			<div>
				<InspectorControls>
					<PanelBody title="Impostazioni">
						<RangeControl
							label="Numero petizioni"
							value={attributes.limit}
							onChange={(limit) => setAttributes({ limit })}
							min={1}
							max={20}
						/>
						<SelectControl
							label="Stato"
							value={attributes.status}
							options={[
								{ label: 'Solo aperte', value: 'aperte' },
								{ label: 'Solo chiuse', value: 'chiuse' },
								{ label: 'Tutte', value: 'tutte' },
							]}
							onChange={(status) => setAttributes({ status })}
						/>
					</PanelBody>
				</InspectorControls>
				<ServerSideRender block="cdv/petizioni-list" attributes={attributes} />
			</div>
		);
	},
	save: () => null,
});

/**
 * Block: Dashboard
 */
registerBlockType('cdv/dashboard', {
	title: 'CdV - Dashboard Analytics',
	icon: 'chart-area',
	category: 'widgets',
	attributes: {
		periodo: {
			type: 'number',
			default: 30,
		},
	},
	edit: ({ attributes, setAttributes }) => {
		return (
			<div>
				<InspectorControls>
					<PanelBody title="Impostazioni">
						<SelectControl
							label="Periodo"
							value={attributes.periodo}
							options={[
								{ label: 'Ultimi 7 giorni', value: 7 },
								{ label: 'Ultimi 30 giorni', value: 30 },
								{ label: 'Ultimi 90 giorni', value: 90 },
								{ label: 'Ultimo anno', value: 365 },
							]}
							onChange={(periodo) => setAttributes({ periodo: parseInt(periodo) })}
						/>
					</PanelBody>
				</InspectorControls>
				<ServerSideRender block="cdv/dashboard" attributes={attributes} />
			</div>
		);
	},
	save: () => null,
});

/**
 * Block: Mappa Interattiva
 */
registerBlockType('cdv/mappa', {
	title: 'CdV - Mappa Interattiva',
	icon: 'location-alt',
	category: 'widgets',
	attributes: {
		tipo: {
			type: 'string',
			default: 'proposte',
		},
		height: {
			type: 'string',
			default: '500px',
		},
	},
	edit: ({ attributes, setAttributes }) => {
		return (
			<div>
				<InspectorControls>
					<PanelBody title="Impostazioni">
						<SelectControl
							label="Tipo contenuto"
							value={attributes.tipo}
							options={[
								{ label: 'Proposte', value: 'proposte' },
								{ label: 'Eventi', value: 'eventi' },
								{ label: 'Petizioni', value: 'petizioni' },
								{ label: 'Tutti', value: 'tutti' },
							]}
							onChange={(tipo) => setAttributes({ tipo })}
						/>
						<SelectControl
							label="Altezza"
							value={attributes.height}
							options={[
								{ label: '400px', value: '400px' },
								{ label: '500px', value: '500px' },
								{ label: '600px', value: '600px' },
								{ label: '800px', value: '800px' },
							]}
							onChange={(height) => setAttributes({ height })}
						/>
					</PanelBody>
				</InspectorControls>
				<div style={{ 
					background: '#f0f0f0', 
					padding: '40px', 
					textAlign: 'center',
					borderRadius: '8px',
					minHeight: attributes.height,
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'center',
				}}>
					<div>
						<span style={{ fontSize: '48px' }}>🗺️</span>
						<p><strong>Mappa Interattiva</strong></p>
						<p>Tipo: {attributes.tipo} | Altezza: {attributes.height}</p>
						<small>Anteprima disponibile solo nel frontend</small>
					</div>
				</div>
			</div>
		);
	},
	save: () => null,
});

/**
 * Block: User Profile
 */
registerBlockType('cdv/user-profile', {
	title: 'CdV - Profilo Utente',
	icon: 'admin-users',
	category: 'widgets',
	attributes: {
		userId: {
			type: 'number',
			default: 0,
		},
	},
	edit: ({ attributes, setAttributes }) => {
		return (
			<div>
				<InspectorControls>
					<PanelBody title="Impostazioni">
						<p>
							<label>User ID (0 = current user)</label>
							<input
								type="number"
								value={attributes.userId}
								onChange={(e) => setAttributes({ userId: parseInt(e.target.value) })}
								style={{ width: '100%' }}
							/>
						</p>
					</PanelBody>
				</InspectorControls>
				<ServerSideRender block="cdv/user-profile" attributes={attributes} />
			</div>
		);
	},
	save: () => null,
});
