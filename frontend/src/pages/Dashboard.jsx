import { Link } from 'react-router-dom'

function Dashboard() {
    return (
        <div className="space-y-8">
            {/* Hero Section */}
            <div className="card bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-700 text-white border-0 shadow-2xl">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-4xl font-black mb-3">Welcome to LMS Reporting</h2>
                        <p className="text-blue-100 text-lg font-medium">
                            High-Performance Analytics for Academic Excellence
                        </p>
                        <div className="flex gap-4 mt-6">
                            <Link to="/reports" className="btn bg-white text-blue-700 hover:bg-blue-50">
                                Generate Report
                            </Link>
                            <Link to="/exports" className="btn bg-blue-800/50 text-white border-2 border-white/30 hover:bg-blue-800/70">
                                View Exports
                            </Link>
                        </div>
                    </div>
                    <div className="text-9xl opacity-20">
                        📊
                    </div>
                </div>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                <StatCard
                    title="Total Events"
                    value="10M+"
                    description="Course activity records"
                    icon="📊"
                    gradient="from-blue-500 to-blue-600"
                    iconBg="bg-blue-100"
                    iconColor="text-blue-600"
                />
                <StatCard
                    title="Students"
                    value="5,000"
                    description="Active learners"
                    icon="👥"
                    gradient="from-green-500 to-emerald-600"
                    iconBg="bg-green-100"
                    iconColor="text-green-600"
                />
                <StatCard
                    title="Courses"
                    value="100"
                    description="Across 4 academic terms"
                    icon="📚"
                    gradient="from-purple-500 to-purple-600"
                    iconBg="bg-purple-100"
                    iconColor="text-purple-600"
                />
                <StatCard
                    title="Instructors"
                    value="200"
                    description="Teaching faculty"
                    icon="🎓"
                    gradient="from-amber-500 to-orange-600"
                    iconBg="bg-amber-100"
                    iconColor="text-amber-600"
                />
            </div>

            {/* System Capabilities */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title flex items-center">
                            <span className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                ⚡
                            </span>
                            System Capabilities
                        </h3>
                    </div>
                    <div className="space-y-3">
                        <Feature
                            icon="🚀"
                            text="Process 10M+ rows with table partitioning"
                            color="text-blue-600"
                        />
                        <Feature
                            icon="📄"
                            text="Generate 1,000+ page PDFs asynchronously"
                            color="text-green-600"
                        />
                        <Feature
                            icon="⚡"
                            text="Preview queries with p95 < 500ms response"
                            color="text-purple-600"
                        />
                        <Feature
                            icon="📊"
                            text="Stream Excel exports for millions of rows"
                            color="text-amber-600"
                        />
                        <Feature
                            icon="📈"
                            text="Real-time progress tracking for export jobs"
                            color="text-red-600"
                        />
                    </div>
                </div>

                <div className="card">
                    <div className="card-header">
                        <h3 className="card-title flex items-center">
                            <span className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                📋
                            </span>
                            Quick Actions
                        </h3>
                    </div>
                    <div className="grid grid-cols-1 gap-4">
                        <ActionButton
                            to="/reports"
                            title="Generate New Report"
                            description="Create detailed activity reports"
                            icon="📈"
                            color="bg-gradient-to-r from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200"
                        />
                        <ActionButton
                            to="/exports"
                            title="Monitor Export Jobs"
                            description="Track report generation progress"
                            icon="📥"
                            color="bg-gradient-to-r from-green-50 to-green-100 hover:from-green-100 hover:to-green-200"
                        />
                        <ActionButton
                            to="/reports"
                            title="Scheduled Reports"
                            description="Manage recurring report exports"
                            icon="🕐"
                            color="bg-gradient-to-r from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200"
                        />
                    </div>
                </div>
            </div>

            {/* Technical Info */}
            <div className="card bg-gradient-to-r from-gray-800 to-gray-900 text-white border-0">
                <div className="card-header border-white/20">
                    <h3 className="card-title text-white">🔧 Technical Specifications</h3>
                </div>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <TechSpec label="Database" value="PostgreSQL 15" />
                    <TechSpec label="Backend" value="Laravel + Octane" />
                    <TechSpec label="Queue System" value="Redis + Horizon" />
                    <TechSpec label="Frontend" value="React + Vite" />
                </div>
            </div>
        </div>
    )
}

function StatCard({ title, value, description, icon, gradient, iconBg, iconColor }) {
    return (
        <div className={`stat-card ${gradient} text-white shadow-xl hover:scale-105 transition-transform`}>
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-sm font-semibold opacity-90 mb-2">{title}</p>
                    <p className="stat-value">{value}</p>
                    <p className="text-xs opacity-80 mt-1">{description}</p>
                </div>
                <div className={`${iconBg} ${iconColor} w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shadow-lg`}>
                    {icon}
                </div>
            </div>
        </div>
    )
}

function ActionButton({ to, title, description, icon, color }) {
    return (
        <Link
            to={to}
            className={`block p-5 rounded-xl border-2 border-transparent ${color} transition-all hover:border-gray-300 hover:shadow-lg group`}
        >
            <div className="flex items-center space-x-4">
                <div className="text-5xl group-hover:scale-110 transition-transform">{icon}</div>
                <div>
                    <h4 className="font-bold text-gray-900 text-lg">{title}</h4>
                    <p className="text-sm text-gray-600 mt-1">{description}</p>
                </div>
            </div>
        </Link>
    )
}

function Feature({ icon, text, color }) {
    return (
        <div className="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
            <span className={`text-2xl ${color} flex-shrink-0`}>{icon}</span>
            <span className="text-gray-700 font-medium">{text}</span>
        </div>
    )
}

function TechSpec({ label, value }) {
    return (
        <div>
            <p className="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">{label}</p>
            <p className="text-lg font-bold text-white">{value}</p>
        </div>
    )
}

export default Dashboard
