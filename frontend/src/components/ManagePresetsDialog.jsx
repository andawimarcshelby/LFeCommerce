import React, { useState } from 'react';

/**
 * Dialog for managing (editing/deleting) saved presets
 */
export default function ManagePresetsDialog({ isOpen, onClose, presets, onUpdate, onDelete }) {
    const [editingId, setEditingId] = useState(null);
    const [editName, setEditName] = useState('');
    const [saving, setSaving] = useState(false);
    const [deleting, setDeleting] = useState(null);

    const handleStartEdit = (preset) => {
        setEditingId(preset.id);
        setEditName(preset.name);
    };

    const handleSaveEdit = async (id) => {
        if (!editName.trim()) return;

        setSaving(true);
        try {
            await onUpdate(id, { name: editName.trim() });
            setEditingId(null);
            setEditName('');
        } catch (err) {
            console.error('Failed to update preset:', err);
        } finally {
            setSaving(false);
        }
    };

    const handleCancelEdit = () => {
        setEditingId(null);
        setEditName('');
    };

    const handleDelete = async (id) => {
        if (!confirm('Are you sure you want to delete this preset?')) {
            return;
        }

        setDeleting(id);
        try {
            await onDelete(id);
        } catch (err) {
            console.error('Failed to delete preset:', err);
        } finally {
            setDeleting(null);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="dialog-overlay">
            <div className="dialog-container dialog-lg">
                <div className="dialog-header">
                    <h2 className="dialog-title">Manage Presets</h2>
                    <button
                        className="dialog-close"
                        onClick={onClose}
                        aria-label="Close dialog"
                    >
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>
                </div>

                <div className="dialog-body">
                    {presets.length === 0 ? (
                        <p className="text-center text-gray-500">No saved presets yet</p>
                    ) : (
                        <div className="preset-list">
                            {presets.map(preset => (
                                <div key={preset.id} className="preset-list-item">
                                    {editingId === preset.id ? (
                                        // Edit mode
                                        <div className="preset-edit">
                                            <input
                                                type="text"
                                                className="preset-edit-input"
                                                value={editName}
                                                onChange={(e) => setEditName(e.target.value)}
                                                onKeyDown={(e) => {
                                                    if (e.key === 'Enter') handleSaveEdit(preset.id);
                                                    if (e.key === 'Escape') handleCancelEdit();
                                                }}
                                                autoFocus
                                                disabled={saving}
                                            />
                                            <div className="preset-edit-actions">
                                                <button
                                                    className="btn btn-sm btn-primary"
                                                    onClick={() => handleSaveEdit(preset.id)}
                                                    disabled={saving || !editName.trim()}
                                                >
                                                    {saving ? 'Saving...' : 'Save'}
                                                </button>
                                                <button
                                                    className="btn btn-sm btn-secondary"
                                                    onClick={handleCancelEdit}
                                                    disabled={saving}
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    ) : (
                                        // View mode
                                        <>
                                            <div className="preset-list-info">
                                                <h3 className="preset-list-name">{preset.name}</h3>
                                                <div className="preset-list-meta">
                                                    <span className="preset-badge">{preset.report_type}</span>
                                                    <span className="preset-date">
                                                        {new Date(preset.created_at).toLocaleDateString()}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="preset-list-actions">
                                                <button
                                                    className="btn-icon"
                                                    onClick={() => handleStartEdit(preset)}
                                                    title="Edit preset"
                                                    disabled={deleting === preset.id}
                                                >
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    className="btn-icon btn-danger"
                                                    onClick={() => handleDelete(preset.id)}
                                                    title="Delete preset"
                                                    disabled={deleting === preset.id}
                                                >
                                                    {deleting === preset.id ? (
                                                        <span>...</span>
                                                    ) : (
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                            <polyline points="3 6 5 6 21 6" />
                                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                                        </svg>
                                                    )}
                                                </button>
                                            </div>
                                        </>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="dialog-footer">
                    <button
                        className="btn btn-secondary"
                        onClick={onClose}
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    );
}
