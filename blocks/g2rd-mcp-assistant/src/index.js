/**
 * MCP Assistant — Gutenberg editor sidebar panel (SP-5)
 *
 * Adds a "MCP Assistant" panel to the Document tab of the Block Editor.
 * Displays the MCP server status and the number of pending write confirmations
 * so the content editor has immediate visibility without leaving the editor.
 *
 * Visible only to administrators (the PHP enqueue is gated on manage_options).
 */

import { registerPlugin }                from '@wordpress/plugins';
import { PluginDocumentSettingPanel }    from '@wordpress/editor';
import { useEffect, useState, useCallback } from '@wordpress/element';
import { Button, Spinner }               from '@wordpress/components';
import apiFetch                          from '@wordpress/api-fetch';

const { adminUrl } = window.G2RDMcpAssistantData || {};

function McpStatusDot( { active } ) {
	return (
		<span style={ {
			display:      'inline-block',
			width:        8,
			height:       8,
			borderRadius: '50%',
			background:   active ? '#00a32a' : '#d63638',
			marginRight:  6,
			verticalAlign: 'middle',
		} } />
	);
}

function McpAssistantPanel() {
	const [ state,       setState       ] = useState( { loading: true, error: false, total: null, anomalies: 0 } );

	const refresh = useCallback( () => {
		setState( ( s ) => ( { ...s, loading: true, error: false } ) );

		Promise.all( [
			apiFetch( { path: '/g2rd/v1/mcp-queue?per_page=1' } ).catch( () => null ),
			apiFetch( { path: '/g2rd/v1/mcp-anomalies' } ).catch( () => null ),
		] ).then( ( [ queue, anom ] ) => {
			setState( {
				loading:   false,
				error:     queue === null,
				total:     queue?.total ?? 0,
				anomalies: anom?.summary?.total ?? 0,
			} );
		} );
	}, [] );

	useEffect( () => {
		refresh();
	}, [ refresh ] );

	const panelUrl = adminUrl
		? `${ adminUrl }admin.php?page=g2rd-options`
		: null;

	return (
		<div style={ { fontSize: 13 } }>
			{ state.loading ? (
				<div style={ { textAlign: 'center', padding: '12px 0' } }><Spinner /></div>
			) : state.error ? (
				<p style={ { color: '#d63638', margin: 0 } }>
					<McpStatusDot active={ false } />
					Serveur MCP inaccessible
				</p>
			) : (
				<>
					<p style={ { margin: '0 0 10px', display: 'flex', alignItems: 'center' } }>
						<McpStatusDot active={ true } />
						<strong>MCP actif</strong>
					</p>

					{ state.total > 0 && (
						<div style={ {
							background: '#fef3c7',
							border:     '1px solid #f59e0b',
							borderRadius: 4,
							padding:    '6px 10px',
							marginBottom: 8,
						} }>
							<span style={ { color: '#92400e', fontWeight: 600 } }>
								<span className="dashicons dashicons-clock" style={ { fontSize: 14, width: 14, height: 14, verticalAlign: 'middle', marginRight: 4 } }></span>
								{ state.total } demande{ state.total > 1 ? 's' : '' } en attente
							</span>
						</div>
					) }

					{ state.anomalies > 0 && (
						<div style={ {
							background: '#fef2f2',
							border:     '1px solid #fca5a5',
							borderRadius: 4,
							padding:    '6px 10px',
							marginBottom: 8,
						} }>
							<span style={ { color: '#991b1b', fontWeight: 600 } }>
								<span className="dashicons dashicons-warning" style={ { fontSize: 14, width: 14, height: 14, verticalAlign: 'middle', marginRight: 4 } }></span>
								{ state.anomalies } anomalie{ state.anomalies > 1 ? 's' : '' } détectée{ state.anomalies > 1 ? 's' : '' }
							</span>
						</div>
					) }

					<div style={ { display: 'flex', gap: 6, marginTop: 4 } }>
						{ panelUrl && (
							<Button
								variant="secondary"
								isSmall
								href={ panelUrl }
								target="_blank"
								rel="noreferrer"
							>
								<span className="dashicons dashicons-admin-settings" style={ { fontSize: 14, width: 14, height: 14, verticalAlign: 'middle', marginRight: 4 } }></span>
								Options MCP
							</Button>
						) }
						<Button variant="tertiary" isSmall onClick={ refresh }>
							<span className="dashicons dashicons-update" style={ { fontSize: 14, width: 14, height: 14, verticalAlign: 'middle' } }></span>
						</Button>
					</div>
				</>
			) }
		</div>
	);
}

registerPlugin( 'g2rd-mcp-assistant', {
	render: () => (
		<PluginDocumentSettingPanel
			name="g2rd-mcp-assistant-panel"
			title="MCP Assistant"
			icon="shield"
		>
			<McpAssistantPanel />
		</PluginDocumentSettingPanel>
	),
} );
