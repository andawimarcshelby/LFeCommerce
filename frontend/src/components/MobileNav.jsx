import { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';

export default function MobileNav({ user, onLogout }) {
    const [isOpen, setIsOpen] = useState(false);
    const location = useLocation();

    const toggleMenu = () => setIsOpen(!isOpen);
    const closeMenu = () => setIsOpen(false);

    const isActive = (path) => location.pathname === path;

    const navLinks = [
        { path: '/', label: 'Dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
        { path: '/reports', label: 'Reports', icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
        { path: '/exports', label: 'Export Center', icon: 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10' },
    ];

    // Add admin link if user has admin/viewer role
    if (user?.roles?.includes('admin') || user?.roles?.includes('viewer')) {
        navLinks.push({
            path: '/admin/exports',
            label: 'Admin',
            icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'
        });
    }

    return (
        <>
            {/* Hamburger Button (Mobile Only) */}
            <button
                onClick={toggleMenu}
                className="md:hidden p-2 text-white hover:bg-white/20 rounded-lg transition-colors"
            >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={isOpen ? "M6 18L18 6M6 6l12 12" : "M4 6h16M4 12h16M4 18h16"} />
                </svg>
            </button>

            {/* Mobile Drawer Overlay */}
            {isOpen && (
                <div
                    className="fixed inset-0 bg-black/50 z-40 md:hidden"
                    onClick={closeMenu}
                />
            )}

            {/* Mobile Drawer */}
            <div
                className={`fixed top-0 right-0 h-full w-80 bg-gradient-to-br from-blue-700 to-blue-900 z-50 transform transition-transform duration-300 md:hidden ${isOpen ? 'translate-x-0' : 'translate-x-full'
                    }`}
            >
                {/* Header */}
                <div className="flex items-center justify-between p-6 border-b border-white/20">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-white rounded-full flex items-center justify-center">
                            <span className="text-2xl">🎓</span>
                        </div>
                        <div className="text-white">
                            <h2 className="font-bold text-lg">University LMS</h2>
                            <p className="text-xs text-blue-200">Reporting System</p>
                        </div>
                    </div>
                    <button
                        onClick={closeMenu}
                        className="p-2 text-white hover:bg-white/20 rounded-lg transition-colors"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* User Info */}
                <div className="p-6 border-b border-white/20">
                    <div className="text-white">
                        <p className="font-semibold">{user?.name}</p>
                        <p className="text-sm text-blue-200">{user?.email}</p>
                    </div>
                </div>

                {/* Navigation Links */}
                <nav className="p-4 space-y-2">
                    {navLinks.map((link) => (
                        <Link
                            key={link.path}
                            to={link.path}
                            onClick={closeMenu}
                            className={`flex items-center gap-4 px-4 py-3 rounded-xl transition-all duration-200 ${isActive(link.path)
                                    ? 'bg-white text-blue-700 shadow-lg'
                                    : 'text-white hover:bg-white/20'
                                }`}
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={link.icon} />
                            </svg>
                            <span className="font-semibold">{link.label}</span>
                        </Link>
                    ))}
                </nav>

                {/* Logout Button */}
                <div className="absolute bottom-0 left-0 right-0 p-6 border-t border-white/20">
                    <button
                        onClick={() => {
                            onLogout();
                            closeMenu();
                        }}
                        className="w-full px-4 py-3 bg-white/20 text-white rounded-xl hover:bg-white/30 transition-all font-semibold flex items-center justify-center gap-2"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Logout
                    </button>
                </div>
            </div>
        </>
    );
}
