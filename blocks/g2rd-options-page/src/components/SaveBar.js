import { Button } from '@wordpress/components';

export function SaveBar( { isDirty, isSaving, onSave, onReset } ) {
	if ( ! isDirty && ! isSaving ) {
		return null;
	}

	return (
		<div className="g2rd-save-bar">
			<span className="g2rd-save-bar__label">
				{ isSaving ? 'Enregistrement en cours…' : 'Modifications non enregistrées' }
			</span>
			<div className="g2rd-save-bar__actions">
				{ ! isSaving && (
					<Button variant="tertiary" onClick={ onReset } disabled={ isSaving }>
						Annuler
					</Button>
				) }
				<Button variant="primary" onClick={ onSave } disabled={ isSaving } isBusy={ isSaving }>
					Enregistrer
				</Button>
			</div>
		</div>
	);
}
