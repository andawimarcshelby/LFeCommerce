import { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../services/api';
import { useToast } from '../contexts/ToastContext';

export default function AdminExportCenter() {
    const toast = useToast();
    const [filters, setFilters] = useState({
        status: 'all',
        user_id: '',
        format: 'all',
        search: ''
    });

    // Fetch all export jobs (admin view)
    const { data: allJobs, isLoading, error, refetch } = useQuery({
        queryKey: ['admin-exports', filters],
        queryFn: async () => {
            const params = new URLSearchParams();
            if (filters.status !== 'all') params.append('status', filters.status);
            if (filters.user_id) params.append('user_id', filters.user_id);
            if (filters.format !== 'all') params.append('format', filters.format);
            if (filters.search) params.append('search', filters.search);

            const response = await api.get(`/reports/exports/admin?${params}`);
            return response.data;
        },
        refetchInterval: 5000, // Poll every 5 seconds
    });

    // Fetch user list for filter
    const { data: users } = useQuery({
        queryKey: ['users'],
        queryFn: async () => {
            const response = await api.get('/users');
            return response.data;
        }
    });

    const handleCancelJob = async (jobId) => {
        if (!confirm('Are you sure you want to cancel this export job?')) return;

        try {
            await api.delete(`/reports/exports/${jobId}`);
            toast.success('Export job cancelled successfully');
            refetch();
        } catch (error) {
            toast.error(error.response?.data?.message || 'Failed to cancel job');
        }
    };

    if (error) {
        return (
            <div className="bg-red-50 border-2 border-red-200 rounded-xl p-8 text-center">
                <div className="w-16 h-16 bg-red-100 rounded-full mx-auto flex items-center justify-center mb-4">
                    <svg className="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 className="text-2xl font-bold text-red-900 mb-2">Failed to Load Export Jobs</h2>
                <p className="text-red-700 mb-4">{error.response?.data?.message || error.message}</p>
                <button
                    onClick={() => refetch()}
                    className="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold"
                >
                    Retry
                </button>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="bg-gradient-to-r from-purple-600 to-indigo-700 rounded-2xl p-8 shadow-xl text-white">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-4xl font-black mb-2">Admin Export Center</h1>
                        <p className="text-purple-100 text-lg">Monitor and manage all user export jobs</p>
                    </div>
                    <div className="text-right">
                        <div className="text-5xl font-black">{allJobs?.data?.length || 0}</div>
                        <div className="text-purple-200 font-semibold">Total Jobs</div>
                    </div>
                </div>
            </div>

            {/* Filters */}
            <div className="bg-white rounded-xl shadow-lg p-6">
                <h2 className="text-xl font-bold text-gray-900 mb-4">Filters</h2>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {/* Status Filter */}
                    <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                        <select
                            value={filters.status}
                            onChange={(e) => setFilters({ ...filters, status: e.target.value })}
                            className="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                        >
                            <option value="all">All Statuses</option>
                            <option value="queued">Queued</option>
                            <option value="running">Running</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>

                    {/* User Filter */}
                    <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">User</label>
                        <select
                            value={filters.user_id}
                            onChange={(e) => setFilters({ ...filters, user_id: e.target.value })}
                            className="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                        >
                            <option value="">All Users</option>
                            {users?.data?.map(user => (
                                <option key={user.id} value={user.id}>{user.name}</option>
                            ))}
                        </select>
                    </div>

                    {/* Format Filter */}
                    <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">Format</label>
                        <select
                            value={filters.format}
                            onChange={(e) => setFilters({ ...filters, format: e.target.value })}
                            className="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                        >
                            <option value="all">All Formats</option>
                            <option value="pdf">PDF</option>
                            <option value="xlsx">Excel</option>
                        </select>
                    </div>

                    {/* Search */}
                    <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                        <input
                            type="text"
                            placeholder="Search by report type..."
                            value={filters.search}
                            onChange={(e) => setFilters({ ...filters, search: e.target.value })}
                            className="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                        />
                    </div>
                </div>
            </div>

            {/* Jobs Table */}
            <div className="bg-white rounded-xl shadow-lg overflow-hidden">
                {isLoading ? (
                    <div className="p-12 text-center">
                        <div className="w-16 h-16 border-4 border-purple-600 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                        <p className="text-gray-600 font-semibold">Loading export jobs...</p>
                    </div>
                ) : allJobs?.data?.length === 0 ? (
                    <div className="p-12 text-center">
                        <div className="w-20 h-20 bg-gray-100 rounded-full mx-auto flex items-center justify-center mb-4">
                            <svg className="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 className="text-xl font-bold text-gray-900 mb-2">No Export Jobs Found</h3>
                        <p className="text-gray-600">No jobs match the current filters</p>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                                <tr>
                                    <th className="px-6 py-4 text-left text-xs font-black text-gray-700 uppercase tracking-wider">User</th>
                                    <th className="px-6 py-4 text-left text-xs font-black text-gray-700 uppercase tracking-wider">Report Type</th>
                                    <th className="px-6 py-4 text-left text-xs font-black text-gray-700 uppercase tracking-wider">Format</th>
                                    <th className="px-6 py-4 text-left text-xs font-black text-gray-700 uppercase tracking-wider">Status</th>
                                    <th className="px-6 py-4 text-left text-xs font-black text-gray-700 uppercase tracking-wider">Progress</th>
                                    <th className="px-6 py-4 text-left text-xs font-black text-gray-700 uppercase tracking-wider">Created</th>
                                    <th className="px-6 py-4 text-left text-xs font-black text-gray-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {allJobs?.data?.map(job => (
                                    <tr key={job.id} className="hover:bg-gray-50 transition-colors">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm font-semibold text-gray-900">{job.user?.name}</div>
                                            <div className="text-xs text-gray-500">{job.user?.email}</div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                                {job.report_type}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className="text-sm font-semibold text-gray-700 uppercase">
                                                {job.format}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <JobStatusBadge status={job.status} />
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            {job.status === 'running' ? (
                                                <div className="w-32">
                                                    <div className="flex items-center justify-between text-xs mb-1">
                                                        <span className="font-semibold text-gray-700">{job.progress_percent}%</span>
                                                        <span className="text-gray-500">{job.current_section || 'Processing'}</span>
                                                    </div>
                                                    <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
                                                        <div
                                                            className="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-500"
                                                            style={{ width: `${job.progress_percent}%` }}
                                                        />
                                                    </div>
                                                </div>
                                            ) : job.status === 'completed' ? (
                                                <span className="text-sm text-gray-600">{job.file_size_human}</span>
                                            ) : (
                                                <span className="text-sm text-gray-400">—</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {new Date(job.created_at).toLocaleString()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                            {job.status === 'completed' && job.download_url && (
                                                <a
                                                    href={job.download_url}
                                                    className="text-green-600 hover:text-green-800 font-semibold"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    Download
                                                </a>
                                            )}
                                            {(job.status === 'queued' || job.status === 'running') && (
                                                <button
                                                    onClick={() => handleCancelJob(job.id)}
                                                    className="text-red-600 hover:text-red-800 font-semibold"
                                                >
                                                    Cancel
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
}

function JobStatusBadge({ status }) {
    const statusStyles = {
        queued: 'bg-gray-100 text-gray-800 border-gray-300',
        running: 'bg-blue-100 text-blue-800 border-blue-300 animate-pulse',
        completed: 'bg-green-100 text-green-800 border-green-300',
        failed: 'bg-red-100 text-red-800 border-red-300',
    };

    const statusIcons = {
        queued: '⏳',
        running: '⚙️',
        completed: '✓',
        failed: '✕',
    };

    return (
        <span className={`inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold border-2 ${statusStyles[status]}`}>
            <span>{statusIcons[status]}</span>
            <span className="uppercase">{status}</span>
        </span>
    );
}
