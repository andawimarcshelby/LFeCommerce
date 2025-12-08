import { useQuery } from '@tanstack/react-query'
import api from '../services/api'
import { formatDistanceToNow } from 'date-fns'

function ExportCenter() {
    const { data: jobs, isLoading, refetch } = useQuery({
        queryKey: ['export-jobs'],
        queryFn: async () => {
            const response = await api.get('/api/reports/exports')
            return response.data
        },
        refetchInterval: 5000, // Poll every 5 seconds
    })

    const deleteJob = async (jobId) => {
        if (!confirm('Are you sure you want to delete this export job?')) return
        await api.delete(`/api/reports/exports/${jobId}`)
        refetch()
    }

    const statusConfig = {
        queued: { color: 'badge-info', icon: '⏳', label: 'Queued' },
        running: { color: 'badge-warning', icon: '⚙️', label: 'Running' },
        completed: { color: 'badge-success', icon: '✅', label: 'Completed' },
        failed: { color: 'badge-error', icon: '❌', label: 'Failed' },
    }

    return (
        <div className="space-y-6">
            {/* Page Header */}
            <div className="card bg-gradient-to-r from-purple-600 to-indigo-600 text-white border-0">
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-3xl font-black mb-2">Export Center</h2>
                        <p className="text-purple-100">Monitor report generation and download completed exports</p>
                    </div>
                    <button onClick={refetch} className="btn bg-white/20 text-white border-2 border-white/30 hover:bg-white/30">
                        <svg className="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            {/* Stats Overview */}
            {jobs && jobs.length > 0 && (
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div className="card bg-gradient-to-br from-blue-500 to-blue-600 text-white border-0">
                        <div className="text-sm font-semibold opacity-90">Total Jobs</div>
                        <div className="text-3xl font-black mt-1">{jobs.length}</div>
                    </div>
                    <div className="card bg-gradient-to-br from-yellow-500 to-orange-600 text-white border-0">
                        <div className="text-sm font-semibold opacity-90">Running</div>
                        <div className="text-3xl font-black mt-1">
                            {jobs.filter(j => j.status === 'running').length}
                        </div>
                    </div>
                    <div className="card bg-gradient-to-br from-green-500 to-emerald-600 text-white border-0">
                        <div className="text-sm font-semibold opacity-90">Completed</div>
                        <div className="text-3xl font-black mt-1">
                            {jobs.filter(j => j.status === 'completed').length}
                        </div>
                    </div>
                    <div className="card bg-gradient-to-br from-red-500 to-red-600 text-white border-0">
                        <div className="text-sm font-semibold opacity-90">Failed</div>
                        <div className="text-3xl font-black mt-1">
                            {jobs.filter(j => j.status === 'failed').length}
                        </div>
                    </div>
                </div>
            )}

            {/* Loading State */}
            {isLoading && (
                <div className="card text-center py-20">
                    <div className="inline-block w-16 h-16 border-4 border-purple-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                    <p className="text-xl font-semibold text-gray-700">Loading export jobs...</p>
                </div>
            )}

            {/* Empty State */}
            {jobs && jobs.length === 0 && (
                <div className="card text-center py-20">
                    <div className="text-8xl mb-4">📥</div>
                    <h3 className="text-2xl font-bold text-gray-800 mb-2">No Export Jobs Yet</h3>
                    <p className="text-gray-600 mb-6">Create your first report from the Reports page!</p>
                    <a href="/reports" className="btn btn-primary inline-flex items-center">
                        <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                        </svg>
                        Generate Report
                    </a>
                </div>
            )}

            {/* Jobs List */}
            {jobs && jobs.length > 0 && (
                <div className="space-y-4">
                    {jobs.map((job) => (
                        <JobCard key={job.id} job={job} statusConfig={statusConfig} onDelete={deleteJob} />
                    ))}
                </div>
            )}
        </div>
    )
}

function JobCard({ job, statusConfig, onDelete }) {
    const status = statusConfig[job.status]

    return (
        <div className="card hover:shadow-2xl">
            <div className="flex items-start justify-between">
                <div className="flex-1">
                    {/* Header */}
                    <div className="flex items-center gap-3 mb-3">
                        <span className={`badge ${status.color} text-sm`}>
                            {status.icon} {status.label}
                        </span>
                        <span className="font-bold text-gray-800 text-lg">
                            {job.report_type.replace('_', ' ').toUpperCase()}
                        </span>
                        <span className="text-sm text-gray-500">•</span>
                        <span className="badge badge-purple">
                            {job.format.toUpperCase()}
                        </span>
                    </div>

                    {/* Metadata */}
                    <div className="text-sm text-gray-600 space-y-2">
                        <div className="flex items-center gap-2">
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Created {formatDistanceToNow(new Date(job.created_at), { addSuffix: true })}
                        </div>

                        {/* Progress Bar for Running Jobs */}
                        {job.status === 'running' && (
                            <div className="mt-4 space-y-2">
                                <div className="flex justify-between text-xs font-semibold">
                                    <span className="text-blue-600">Processing: {job.progress_percent}%</span>
                                    <span className="text-gray-600">
                                        {job.processed_rows?.toLocaleString()} / {job.total_rows?.toLocaleString()} rows
                                    </span>
                                </div>
                                <div className="progress-bar">
                                    <div
                                        className="progress-fill"
                                        style={{ width: `${job.progress_percent}%` }}
                                    ></div>
                                </div>
                                {job.current_section && (
                                    <div className="text-xs text-gray-500 italic flex items-center gap-1">
                                        <svg className="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        {job.current_section}
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Completed State */}
                        {job.status === 'completed' && (
                            <div className="mt-3 p-4 bg-green-50 rounded-lg border-2 border-green-200">
                                <div className="flex items-center gap-2 text-green-700 font-semibold mb-1">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Ready for download!
                                </div>
                                <div className="text-xs text-green-600">File size: {job.file_size}</div>
                            </div>
                        )}

                        {/* Failed State */}
                        {job.status === 'failed' && job.error_message && (
                            <div className="mt-3 p-4 bg-red-50 rounded-lg border-2 border-red-200">
                                <div className="flex items-start gap-2">
                                    <svg className="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div className="text-sm text-red-700">{job.error_message}</div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Actions */}
                <div className="flex gap-2 ml-4">
                    {job.status === 'completed' && job.download_url && (
                        <a
                            href={job.download_url}
                            className="btn btn-success"
                            download
                        >
                            <svg className="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download
                        </a>
                    )}
                    <button
                        onClick={() => onDelete(job.id)}
                        className="btn btn-secondary text-red-600 hover:bg-red-50 border-red-200"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    )
}

export default ExportCenter
