import { useState, useEffect, useCallback } from 'react';
import api from '../services/api';

/**
 * Custom hook for managing report filter presets
 */
export function usePresets() {
    const [presets, setPresets] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    // Fetch all presets for current user
    const fetchPresets = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await api.get('/reports/presets');
            setPresets(response.data.data || []);
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to load presets');
            console.error('Error fetching presets:', err);
        } finally {
            setLoading(false);
        }
    }, []);

    // Save a new preset
    const savePreset = useCallback(async (name, reportType, filters) => {
        setLoading(true);
        setError(null);
        try {
            const response = await api.post('/reports/presets', {
                name,
                report_type: reportType,
                filters,
            });

            // Add new preset to list
            setPresets(prev => [...prev, response.data.data]);

            return response.data.data;
        } catch (err) {
            const errorMsg = err.response?.data?.error || 'Failed to save preset';
            setError(errorMsg);
            console.error('Error saving preset:', err);
            throw new Error(errorMsg);
        } finally {
            setLoading(false);
        }
    }, []);

    // Update an existing preset
    const updatePreset = useCallback(async (id, updates) => {
        setLoading(true);
        setError(null);
        try {
            const response = await api.put(`/reports/presets/${id}`, updates);

            // Update preset in list
            setPresets(prev =>
                prev.map(p => (p.id === id ? response.data.data : p))
            );

            return response.data.data;
        } catch (err) {
            const errorMsg = err.response?.data?.error || 'Failed to update preset';
            setError(errorMsg);
            console.error('Error updating preset:', err);
            throw new Error(errorMsg);
        } finally {
            setLoading(false);
        }
    }, []);

    // Delete a preset
    const deletePreset = useCallback(async (id) => {
        setLoading(true);
        setError(null);
        try {
            await api.delete(`/reports/presets/${id}`);

            // Remove preset from list
            setPresets(prev => prev.filter(p => p.id !== id));
        } catch (err) {
            const errorMsg = err.response?.data?.message || 'Failed to delete preset';
            setError(errorMsg);
            console.error('Error deleting preset:', err);
            throw new Error(errorMsg);
        } finally {
            setLoading(false);
        }
    }, []);

    // Load presets on mount
    useEffect(() => {
        fetchPresets();
    }, [fetchPresets]);

    return {
        presets,
        loading,
        error,
        savePreset,
        updatePreset,
        deletePreset,
        refreshPresets: fetchPresets,
    };
}
