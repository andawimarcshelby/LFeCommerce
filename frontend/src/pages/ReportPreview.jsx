import { useState } from 'react'
import { useQuery, useMutation } from '@tanstack/react-query'
import api from '../services/api'

function ReportPreview() {
    const [filters, setFilters] = useState({
        report_type: 'detail',
        filters: {
            date_from: '2024-01-01',
            date_to: '2024-12-31',
            term_ids: [],
            course_ids: [],
            event_types: [],
        },
    })

    const [page, setPage] = useState(1)
    const [showFilters, setShowFilters] = useState(true)

    // Preview query
    const { data, isLoading, error, refetch } = useQuery({
        queryKey: ['report-preview', filters, page],
        queryFn: async () => {
            const response = await api.post('/api/reports/preview', {
                ...filters,
                page,
                per_page: 100,
            })
            return response
        },
        enabled: false,
    })

    // Export mutation
    const exportMutation = useMutation({
        mutationFn: async (format) => {
            return await api.post('/api/reports/exports', {
                ...filters,
                format,
            })
        },
        onSuccess: () => {
            alert('✅ Export job created! Check Export Center for status.')
        },
        onError: () => {
            alert('❌ Failed to create export job. Please try again.')
        },
    })

    const handlePreview = () => {
        setPage(1)
        refetch()
    }

    return (
        <div className="space-y-6">
            {/* Page Header */}
            <div className="card bg-gradient-to-r from-indigo-600 to-blue-600 text-white border-0">
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-3xl font-black mb-2">Generate Report</h2>
                        <p className="text-blue-100">Configure filters and preview your academic data</p>
                    </div>
                    <button
                        onClick={() => setShowFilters(!showFilters)}
                        className="btn bg-white/20 text-white border-2 border-white/30 hover:bg-white/30"
                    >
                        {showFilters ? '← Hide' : 'Show →'} Filters
                    </button>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {/* Filters Sidebar */}
                {showFilters && (
                    <div className="lg:col-span-1 space-y-4">
                        <div className="card">
                            <h3 className="font-bold text-lg mb-4 flex items-center">
                                <span className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-2 text-blue-600">
                                    🎯
                                </span>
                                Report Configuration
                            </h3>

                            <div className="space-y-4">
                                {/* Report Type */}
                                <div>
                                    <label className="input-label">Report Type</label>
                                    <select
                                        className="select"
                                        value={filters.report_type}
                                        onChange={(e) => setFilters({ ...filters, report_type: e.target.value })}
                                    >
                                        <option value="detail">📄 Detail Report</option>
                                        <option value="summary">📊 Summary Report</option>
                                        <option value="top_n">🏆 Top-N Report</option>
                                        <option value="per_student">👤 Per-Student Report</option>
                                    </select>
                                </div>

                                {/* Date Range */}
                                <div>
                                    <label className="input-label">Date From</label>
                                    <input
                                        type="date"
                                        className="input"
                                        value={filters.filters.date_from}
                                        onChange={(e) =>
                                            setFilters({
                                                ...filters,
                                                filters: { ...filters.filters, date_from: e.target.value },
                                            })
                                        }
                                    />
                                </div>

                                <div>
                                    <label className="input-label">Date To</label>
                                    <input
                                        type="date"
                                        className="input"
                                        value={filters.filters.date_to}
                                        onChange={(e) =>
                                            setFilters({
                                                ...filters,
                                                filters: { ...filters.filters, date_to: e.target.value },
                                            })
                                        }
                                    />
                                </div>

                                {/* Action Buttons */}
                                <div className="pt-4 space-y-3">
                                    <button
                                        className="btn btn-primary w-full"
                                        onClick={handlePreview}
                                        disabled={isLoading}
                                    >
                                        {isLoading ? (
                                            <>
                                                <span className="spinner mr-2"></span>
                                                Loading...
                                            </>
                                        ) : (
                                            '🔍 Preview Report'
                                        )}
                                    </button>

                                    <button
                                        className="btn btn-success w-full"
                                        onClick={() => exportMutation.mutate('pdf')}
                                        disabled={exportMutation.isPending}
                                    >
                                        📄 Export as PDF
                                    </button>

                                    <button
                                        className="btn btn-success w-full"
                                        onClick={() => exportMutation.mutate('xlsx')}
                                        disabled={exportMutation.isPending}
                                    >
                                        📊 Export as Excel
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Quick Stats */}
                        {data && (
                            <div className="card bg-gradient-to-br from-green-500 to-emerald-600 text-white">
                                <h4 className="font-bold mb-3">Preview Stats</h4>
                                <div className="space-y-2">
                                    <div className="flex justify-between">
                                        <span className="opacity-90">Total Rows:</span>
                                        <span className="font-bold">{data.pagination.total.toLocaleString()}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="opacity-90">Query Time:</span>
                                        <span className="font-bold">{data.meta.query_time_ms}ms</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="opacity-90">Pages:</span>
                                        <span className="font-bold">{data.pagination.last_page}</span>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Preview Results */}
                <div className={showFilters ? 'lg:col-span-3' : 'lg:col-span-4'}>
                    {!data && !isLoading && !error && (
                        <div className="card text-center py-20">
                            <div className="text-8xl mb-4">📊</div>
                            <h3 className="text-2xl font-bold text-gray-800 mb-2">Ready to Generate Report</h3>
                            <p className="text-gray-600">Configure filters and click "Preview Report" to see results</p>
                        </div>
                    )}

                    {isLoading && (
                        <div className="card text-center py-20">
                            <div className="inline-block w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                            <p className="text-xl font-semibold text-gray-700">Loading preview...</p>
                            <p className="text-gray-500 mt-2">Processing query...</p>
                        </div>
                    )}

                    {error && (
                        <div className="card bg-red-50 border-2 border-red-200">
                            <div className="flex items-start space-x-3">
                                <span className="text-3xl">❌</span>
                                <div>
                                    <h3 className="font-bold text-red-800 mb-1">Error Loading Report</h3>
                                    <p className="text-red-700">{error.message}</p>
                                </div>
                            </div>
                        </div>
                    )}

                    {data && (
                        <div className="card">
                            <div className="card-header">
                                <h3 className="card-title">
                                    Preview Results ({data.pagination.total.toLocaleString()} rows)
                                </h3>
                                <span className="badge badge-info">
                                    Query: {data.meta.query_time_ms}ms
                                </span>
                            </div>

                            <div className="table-container">
                                <table className="table">
                                    <thead className="table-header">
                                        <tr>
                                            {data.data[0] &&
                                                Object.keys(data.data[0]).map((key) => (
                                                    <th key={key} className="table-header-cell">
                                                        {key.replace('_', ' ').toUpperCase()}
                                                    </th>
                                                ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {data.data.map((row, idx) => (
                                            <tr key={idx} className="table-row">
                                                {Object.values(row).map((value, cellIdx) => (
                                                    <td key={cellIdx} className="table-cell">
                                                        {value?.toString() || '—'}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            <div className="mt-6 flex justify-between items-center">
                                <button
                                    className="btn btn-secondary"
                                    onClick={() => setPage(page - 1)}
                                    disabled={page === 1}
                                >
                                    ← Previous
                                </button>
                                <span className="text-sm font-semibold text-gray-600">
                                    Page {data.pagination.current_page} of {data.pagination.last_page}
                                </span>
                                <button
                                    className="btn btn-secondary"
                                    onClick={() => setPage(page + 1)}
                                    disabled={page === data.pagination.last_page}
                                >
                                    Next →
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    )
}

export default ReportPreview
