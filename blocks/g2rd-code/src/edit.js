/**
 * Éditeur React du bloc G2RD Code : saisie du code + réglages (langue, thème, etc.).
 * La liste des langues est importée depuis languages.json (même source que le PHP).
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	BlockControls,
	AlignmentToolbar,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	SelectControl,
	RangeControl,
	ToggleControl,
} from '@wordpress/components';
import { useMemo } from '@wordpress/element';

// Fichier partagé avec prettycode-helpers.php (render serveur).
import languages from '../languages.json';

// Thèmes CSS fournis par highlight.php (dossier styles/ du paquet scrivo).
const HLJS_THEMES = [
	{ label: 'Monokai', value: 'monokai' },
	{ label: 'GitHub', value: 'github' },
	{ label: 'GitHub Dark', value: 'github-dark' },
	{ label: 'Atom One Dark', value: 'atom-one-dark' },
	{ label: 'Atom One Light', value: 'atom-one-light' },
	{ label: 'VS Code', value: 'vs2015' },
	{ label: 'Xcode', value: 'xcode' },
	{ label: 'Solarized (dark)', value: 'obsidian' },
	{ label: 'Nord', value: 'nord' },
	{ label: 'Night Owl', value: 'night-owl' },
	{ label: 'Default', value: 'default' },
	{ label: 'Gradient dark', value: 'gradient-dark' },
];

const languageOptions = languages.map(({ label, value }) => ({ label, value }));

export default function Edit({ attributes, setAttributes }) {
	const {
		source,
		file,
		language,
		theme,
		fontSize,
		startLine,
		showLines,
		wrapLines,
		align,
	} = attributes;

	const blockProps = useBlockProps({
		className: 'g2rd-code-editor__wrapper hljs',
		style: {
			'--wrb-code-font-size': `${fontSize}px`,
		},
	});

	const lineCount = useMemo(() => {
		if (!source) {
			return 1;
		}
		return source.split('\n').length;
	}, [source]);

	const lineNumbers = useMemo(() => {
		if (!showLines) {
			return null;
		}
		const items = [];
		for (let i = 0; i < lineCount; i += 1) {
			items.push(
				<span key={`ln-${startLine + i}`}>{startLine + i}</span>
			);
		}
		return items;
	}, [showLines, lineCount, startLine]);

	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={align}
					onChange={(newAlign) => setAttributes({ align: newAlign })}
				/>
			</BlockControls>

			<InspectorControls>
				<PanelBody title={__('Fichier et langue', 'g2rd')} initialOpen>
					<TextControl
						label={__('Nom du fichier (optionnel)', 'g2rd')}
						value={file}
						onChange={(v) => setAttributes({ file: v })}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						help={__(
							'Affiché dans l’en-tête du bloc sur le site.',
							'g2rd'
						)}
					/>
					<SelectControl
						label={__('Langage', 'g2rd')}
						value={language}
						options={languageOptions}
						onChange={(v) => setAttributes({ language: v })}
					/>
					<SelectControl
						label={__('Thème coloration', 'g2rd')}
						value={theme}
						options={HLJS_THEMES}
						onChange={(v) => setAttributes({ theme: v })}
					/>
				</PanelBody>
				<PanelBody title={__('Mise en forme', 'g2rd')}>
					<RangeControl
						label={__('Taille de police (px)', 'g2rd')}
						value={fontSize}
						onChange={(v) => setAttributes({ fontSize: v ?? 14 })}
						min={10}
						max={24}
					/>
					<RangeControl
						label={__('Numéro de la première ligne', 'g2rd')}
						value={startLine}
						onChange={(v) => setAttributes({ startLine: v ?? 1 })}
						min={1}
						max={99999}
					/>
					<ToggleControl
						label={__('Afficher les numéros de ligne', 'g2rd')}
						checked={showLines}
						onChange={(v) => setAttributes({ showLines: v })}
					/>
					<ToggleControl
						label={__('Retour à la ligne dans le code', 'g2rd')}
						checked={wrapLines}
						onChange={(v) => setAttributes({ wrapLines: v })}
					/>
				</PanelBody>
				<PanelBody
					title={__('Code source', 'g2rd')}
					initialOpen={false}
				>
					<TextareaControl
						label={__('Coller ou modifier ici', 'g2rd')}
						value={source}
						onChange={(v) => setAttributes({ source: v })}
						__nextHasNoMarginBottom
						rows={12}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<div className="g2rd-code-editor__body">
					{showLines && (
						<span
							className="g2rd-code__lines"
							aria-hidden="true"
						>
							{lineNumbers}
						</span>
					)}
					<div className="g2rd-code-editor__editor">
						<textarea
							className={wrapLines ? 'is-wrap' : ''}
							value={source}
							onChange={(e) =>
								setAttributes({ source: e.target.value })
							}
							spellCheck={false}
							aria-label={__('Code source', 'g2rd')}
							placeholder={__(
								'Écrivez votre code ici…',
								'g2rd'
							)}
						/>
					</div>
				</div>
			</div>
		</>
	);
}
