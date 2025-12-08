import { Routes, Route, Link, useLocation, Navigate } from 'react-router-dom'
import { useAuth } from './contexts/AuthContext'
import { useToast } from './contexts/ToastContext'
import Dashboard from './pages/Dashboard'
import ReportPreview from './pages/ReportPreview'
import ExportCenter from './pages/ExportCenter'
import Login from './pages/Login'

function App() {
    const location = useLocation()
    const { user, logout, loading } = useAuth()
    const toast = useToast()

    if (loading) {
        return (
            <div className="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50/30 to-gray-100 flex items-center justify-center">
                <div className="text-center">
                    <div className="w-16 h-16 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
                    <p className="mt-4 text-gray-600 font-medium">Loading...</p>
                </div>
            </div>
        )
    }

    if (!user) {
        return <Login />
    }

    const handleLogout = async () => {
        await logout()
        toast.info('You have been logged out')
    }

    return (
        <div className="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50/30 to-gray-100">
            {/* Navigation Header */}
            <nav className="header-gradient shadow-xl border-b-4 border-blue-300 sticky top-0 z-50">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center h-20">
                        {/* Logo & Title */}
                        <div className="flex items-center space-x-4">
                            <div className="university-seal w-14 h-14 bg-white rounded-full flex items-center justify-center shadow-lg">
                                <span className="text-3xl">🎓</span>
                            </div>
                            <div className="text-white">
                                <h1 className="text-2xl font-black tracking-tight">
                                    University LMS
                                </h1>
                                <p className="text-xs text-blue-200 font-medium">
                                    Advanced Reporting & Analytics
                                </p>
                            </div>
                        </div>

                        {/* Navigation Links */}
                        <div className="flex space-x-2">
                            <NavLink to="/" active={location.pathname === '/'}>
                                <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                Dashboard
                            </NavLink>
                            <NavLink to="/reports" active={location.pathname === '/reports'}>
                                <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Reports
                            </NavLink>
                            <NavLink to="/exports" active={location.pathname === '/exports'}>
                                <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                                </svg>
                                Export Center
                            </NavLink>
                        </div>

                        {/* User Menu */}
                        <div className="flex items-center space-x-4">
                            <button className="text-white hover:text-blue-100 transition-colors">
                                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </button>
                            <div className="text-white text-right">
                                <p className="font-semibold text-sm">{user.name}</p>
                                <p className="text-xs text-blue-200">{user.email}</p>
                            </div>
                            <button
                                onClick={handleLogout}
                                className="px-4 py-2 bg-white/20 text-white rounded-lg hover:bg-white/30 transition-all font-medium text-sm"
                            >
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            </nav>

            {/* Main Content */}
            <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <Routes>
                    <Route path="/" element={<Dashboard />} />
                    <Route path="/reports" element={<ReportPreview />} />
                    <Route path="/exports" element={<ExportCenter />} />
                </Routes>
            </main>

            {/* Footer */}
            <footer className="bg-gradient-to-r from-gray-800 to-gray-900 text-white py-8 mt-20">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center">
                        <div>
                            <p className="font-semibold">© 2024 University LMS</p>
                            <p className="text-sm text-gray-400 mt-1">Advanced Reporting System v1.0</p>
                        </div>
                        <div className="text-right">
                            <p className="text-sm text-gray-400">Powered by Laravel + React</p>
                            <p className="text-xs text-gray-500 mt-1">Processing 10M+ Records</p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    )
}

function NavLink({ to, active, children }) {
    return (
        <Link
            to={to}
            className={`flex items-center px-5 py-2.5 rounded-xl font-semibold transition-all duration-300 ${active
                ? 'bg-white text-blue-700 shadow-lg scale-105'
                : 'text-white hover:bg-white/20 hover:shadow-md'
                }`}
        >
            {children}
        </Link>
    )
}

export default App
