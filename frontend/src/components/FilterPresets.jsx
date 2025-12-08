import React, { useState } from 'react';

/**
 * Dropdown selector for saved filter presets
 */
export default function FilterPresets({ presets, onSelectPreset, currentPreset }) {
    const [isOpen, setIsOpen] = useState(false);

    const handleSelect = (preset) => {
        onSelectPreset(preset);
        setIsOpen(false);
    };

    return (
        <div className="preset-selector">
            <label className="preset-label">
                Saved Presets
            </label>

            <div className="preset-dropdown">
                <button
                    className="preset-trigger"
                    onClick={() => setIsOpen(!isOpen)}
                    aria-haspopup="listbox"
                    aria-expanded={isOpen}
                >
                    <span className="preset-value">
                        {currentPreset ? currentPreset.name : 'Select a preset...'}
                    </span>
                    <svg
                        className={`preset-icon ${isOpen ? 'rotate-180' : ''}`}
                        width="20"
                        height="20"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fillRule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clipRule="evenodd"
                        />
                    </svg>
                </button>

                {isOpen && (
                    <div className="preset-menu">
                        {presets.length === 0 ? (
                            <div className="preset-item-empty">
                                No saved presets yet
                            </div>
                        ) : (
                            presets.map(preset => (
                                <button
                                    key={preset.id}
                                    className={`preset-item ${currentPreset?.id === preset.id ? 'active' : ''}`}
                                    onClick={() => handleSelect(preset)}
                                    role="option"
                                    aria-selected={currentPreset?.id === preset.id}
                                >
                                    <div className="preset-item-content">
                                        <span className="preset-item-name">{preset.name}</span>
                                        <span className="preset-item-type">{preset.report_type}</span>
                                    </div>
                                    {currentPreset?.id === preset.id && (
                                        <svg className="preset-check" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                            <path
                                                fillRule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clipRule="evenodd"
                                            />
                                        </svg>
                                    )}
                                </button>
                            ))
                        )}
                    </div>
                )}
            </div>

            {/* Click outside to close */}
            {isOpen && (
                <div
                    className="preset-backdrop"
                    onClick={() => setIsOpen(false)}
                />
            )}
        </div>
    );
}
