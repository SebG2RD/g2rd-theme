import { CPTPanel } from '../components/CPTPanel';

const { cptDefaults } = window.G2RDOptionsData || {};

const CPT_META = {
	portfolio:       { label: 'Portfolio',      icon: 'admin-appearance' },
	prestations:     { label: 'Prestations',    icon: 'clipboard' },
	'qui-sommes-nous': { label: 'Qui sommes-nous', icon: 'groups' },
};

export function TabContenu( { settings, update } ) {
	const cpts = settings.cpts || {};

	return (
		<div className="g2rd-tab-content">
			<section className="g2rd-section">
				<h2 className="g2rd-section__title">
					<span className="dashicons dashicons-category"></span>
					Types de contenus personnalisés (CPT)
				</h2>
				<p className="g2rd-section__desc">
					Activez ou désactivez les types de contenus inclus dans le thème.
					Un changement de slug nécessite une mise à jour des permaliens.
				</p>
				<div className="g2rd-cpt-list">
					{ Object.entries( CPT_META ).map( ( [ key, { label, icon } ] ) => (
						<CPTPanel
							key={ key }
							cptKey={ key }
							label={ label }
							icon={ icon }
							settings={ cpts[ key ] }
							defaults={ cptDefaults?.[ key ] || {} }
							onChange={ ( field, value ) =>
								update( [ 'cpts', key, field ], value )
							}
						/>
					) ) }
				</div>
			</section>
		</div>
	);
}
