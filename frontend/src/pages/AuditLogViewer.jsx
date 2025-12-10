import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../services/api';
import DateRangePicker from '../components/DateRangePicker';
import { InlineLoader } from '../components/LoadingSpinner';

export default function AuditLogViewer() {
    const [filters, setFilters] = useState({
        startDate: null,
        endDate: null,
        userId: '',
        action: '',
        status: 'all',
        search: '',
        page: 1
    });

    // Fetch audit logs
    const { data: logsData, isLoading } = useQuery({
        queryKey: ['audit-logs', filters],
        queryFn: async () => {
            const params = new URLSearchParams();

            if (filters.startDate) params.append('start_date', filters.startDate.toISOString().split('T')[0]);
            if (filters.endDate) params.append('end_date', filters.endDate.toISOString().split('T')[0]);
            if (filters.userId) params.append('user_id', filters.userId);
            if (filters.action) params.append('action', filters.action);
            if (filters.status !== 'all') params.append('status', filters.status);
            if (filters.search) params.append('search', filters.search);
            params.append('page', filters.page);

            const response = await api.get(`/audit/logs?${params.toString()}`);
            return response.data;
        },
        refetchInterval: 30000, // Refresh every 30 seconds
    });

    // Fetch statistics
    const { data: stats } = useQuery({
        queryKey: ['audit-stats'],
        queryFn: async () => {
            const response = await api.get('/audit/stats');
            return response.data;
        },
    });

    const handleFilterChange = (key, value) => {
        setFilters(prev => ({ ...prev, [key]: value, page: 1 }));
    };

    const getStatusBadgeColor = (status) => {
        if (status >= 200 && status < 300) return 'bg-green-100 text-green-800';
        if (status >= 300 && status < 400) return 'bg-blue-100 text-blue-800';
        if (status >= 400 && status < 500) return 'bg-yellow-100 text-yellow-800';
        return 'bg-red-100 text-red-800';
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-6">
            {/* Header */}
            <div className="mb-8">
                <h1 className="text-4xl font-black text-gray-900 mb-2">Audit Log Viewer</h1>
                <p className="text-gray-600 text-lg">Monitor all system activity and API requests</p>
            </div>

            {/* Statistics Cards */}
            {stats && (
                <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
                    <div className="bg-white rounded-xl shadow-md p-6">
                        <div className="text-sm text-gray-600 font-semibold mb-1">Total Logs</div>
                        <div className="text-3xl font-black text-gray-900">{stats.total_logs?.toLocaleString()}</div>
                    </div>
                    <div className="bg-white rounded-xl shadow-md p-6">
                        <div className="text-sm text-gray-600 font-semibold mb-1">Today</div>
                        <div className="text-3xl font-black text-blue-600">{stats.today_logs?.toLocaleString()}</div>
                    </div>
                    <div className="bg-white rounded-xl shadow-md p-6">
                        <div className="text-sm text-gray-600 font-semibold mb-1">Unique Users</div>
                        <div className="text-3xl font-black text-purple-600">{stats.unique_users}</div>
                    </div>
                    <div className="bg-white rounded-xl shadow-md p-6">
                        <div className="text-sm text-gray-600 font-semibold mb-1">Avg Time</div>
                        <div className="text-3xl font-black text-green-600">{stats.avg_response_time}ms</div>
                    </div>
                    <div className="bg-white rounded-xl shadow-md p-6">
                        <div className="text-sm text-gray-600 font-semibold mb-1">Errors</div>
                        <div className="text-3xl font-black text-red-600">{stats.error_count}</div>
                    </div>
                </div>
            )}

            {/* Filters */}
            <div className="bg-white rounded-xl shadow-lg p-6 mb-6 space-y-4">
                <h3 className="text-lg font-bold text-gray-900 mb-4">Filters</h3>

                {/* Date Range */}
                <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-2">Date Range</label>
                    <DateRangePicker
                        startDate={filters.startDate}
                        endDate={filters.endDate}
                        onChange={(start, end) => {
                            setFilters(prev => ({ ...prev, startDate: start, endDate: end, page: 1 }));
                        }}
                    />
                </div>

                {/* Other Filters */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">Action/Route</label>
                        <input
                            type="text"
                            value={filters.action}
                            onChange={(e) => handleFilterChange('action', e.target.value)}
                            placeholder="e.g., reports.preview"
                            className="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">Status Code</label>
                        <select
                            value={filters.status}
                            onChange={(e) => handleFilterChange('status', e.target.value)}
                            className="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="all">All Status</option>
                            <option value="200">200 (OK)</option>
                            <option value="201">201 (Created)</option>
                            <option value="400">400 (Bad Request)</option>
                            <option value="401">401 (Unauthorized)</option>
                            <option value="403">403 (Forbidden)</option>
                            <option value="404">404 (Not Found)</option>
                            <option value="500">500 (Server Error)</option>
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-semibold text-gray-700 mb-2">Search (IP/User Agent)</label>
                        <input
                            type="text"
                            value={filters.search}
                            onChange={(e) => handleFilterChange('search', e.target.value)}
                            placeholder="192.168.1.1 or Chrome"
                            className="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                </div>
            </div>

            {/* Audit Logs Table */}
            <div className="bg-white rounded-xl shadow-lg overflow-hidden">
                {isLoading ? (
                    <div className="p-12">
                        <InlineLoader message="Loading audit logs..." />
                    </div>
                ) : logsData?.data?.length === 0 ? (
                    <div className="p-12 text-center">
                        <div className="text-6xl mb-4">📋</div>
                        <h3 className="text-xl font-bold text-gray-900 mb-2">No Audit Logs Found</h3>
                        <p className="text-gray-600">Try adjusting your filters</p>
                    </div>
                ) : (
                    <>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gray-100">
                                    <tr>
                                        <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Timestamp</th>
                                        <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">User</th>
                                        <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Action</th>
                                        <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">IP Address</th>
                                        <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                                        <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Duration</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {logsData?.data?.map((log) => (
                                        <tr key={log.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {new Date(log.created_at).toLocaleString()}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-semibold text-gray-900">{log.user?.name}</div>
                                                <div className="text-xs text-gray-500">{log.user?.email}</div>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-900 font-mono">{log.action}</td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600">{log.ip_address}</td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-3 py-1 rounded-full text-xs font-bold ${getStatusBadgeColor(log.response_status)}`}>
                                                    {log.response_status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {log.duration_ms}ms
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {logsData?.meta && (
                            <div className="bg-gray-50 px-6 py-4 flex items-center justify-between border-t-2 border-gray-200">
                                <div className="text-sm text-gray-600">
                                    Showing page {logsData.meta.current_page} of {logsData.meta.last_page}
                                    ({logsData.meta.total} total logs)
                                </div>
                                <div className="flex gap-2">
                                    <button
                                        onClick={() => handleFilterChange('page', filters.page - 1)}
                                        disabled={filters.page === 1}
                                        className="px-4 py-2 bg-blue-600 text-white rounded-lg disabled:bg-gray-300 disabled:cursor-not-allowed hover:bg-blue-700 transition-colors font-semibold"
                                    >
                                        Previous
                                    </button>
                                    <button
                                        onClick={() => handleFilterChange('page', filters.page + 1)}
                                        disabled={filters.page >= logsData.meta.last_page}
                                        className="px-4 py-2 bg-blue-600 text-white rounded-lg disabled:bg-gray-300 disabled:cursor-not-allowed hover:bg-blue-700 transition-colors font-semibold"
                                    >
                                        Next
                                    </button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>
        </div>
    );
}
