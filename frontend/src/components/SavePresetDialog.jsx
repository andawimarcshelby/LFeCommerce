import React, { useState } from 'react';

/**
 * Dialog for saving current filter configuration as a preset
 */
export default function SavePresetDialog({ isOpen, onClose, onSave, reportType, filters }) {
    const [presetName, setPresetName] = useState('');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!presetName.trim()) {
            setError('Please enter a preset name');
            return;
        }

        setSaving(true);
        setError(null);

        try {
            await onSave(presetName.trim(), reportType, filters);
            setPresetName('');
            onClose();
        } catch (err) {
            setError(err.message || 'Failed to save preset');
        } finally {
            setSaving(false);
        }
    };

    const handleClose = () => {
        setPresetName('');
        setError(null);
        onClose();
    };

    if (!isOpen) return null;

    return (
        <div className="dialog-overlay">
            <div className="dialog-container">
                <div className="dialog-header">
                    <h2 className="dialog-title">Save Filter Preset</h2>
                    <button
                        className="dialog-close"
                        onClick={handleClose}
                        aria-label="Close dialog"
                    >
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="dialog-body">
                    <div className="form-group">
                        <label htmlFor="preset-name" className="form-label">
                            Preset Name
                        </label>
                        <input
                            type="text"
                            id="preset-name"
                            className="form-input"
                            value={presetName}
                            onChange={(e) => setPresetName(e.target.value)}
                            placeholder="e.g., Spring 2024 CS Courses"
                            autoFocus
                            disabled={saving}
                        />
                    </div>

                    <div className="preset-info">
                        <div className="preset-info-item">
                            <span className="preset-info-label">Report Type:</span>
                            <span className="preset-info-value">{reportType}</span>
                        </div>
                        <div className="preset-info-item">
                            <span className="preset-info-label">Active Filters:</span>
                            <span className="preset-info-value">
                                {Object.keys(filters || {}).length} filter(s)
                            </span>
                        </div>
                    </div>

                    {error && (
                        <div className="error-message">
                            {error}
                        </div>
                    )}

                    <div className="dialog-actions">
                        <button
                            type="button"
                            className="btn btn-secondary"
                            onClick={handleClose}
                            disabled={saving}
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            className="btn btn-primary"
                            disabled={saving || !presetName.trim()}
                        >
                            {saving ? 'Saving...' : 'Save Preset'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
