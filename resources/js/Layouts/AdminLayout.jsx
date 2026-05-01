import { Link, usePage, router } from '@inertiajs/react';
import { useState } from 'react';
import Dropdown from '@/Components/Dropdown';

export default function AdminLayout({ children, title }) {
    const user = usePage().props.auth.user;
    const [sidebarOpen, setSidebarOpen] = useState(true);

    const menuItems = [
        {
            label: 'Dashboard',
            href: route('admin.dashboard'),
            icon: '📊',
            active: route().current('admin.dashboard'),
        },
        {
            label: 'Users',
            href: route('admin.users.index'),
            icon: '👥',
            active: route().current('admin.users.*'),
        },
        {
            label: 'Bulk Import',
            href: route('admin.users.bulkImport'),
            icon: '📥',
            active: route().current('admin.users.bulkImport'),
        },
        {
            label: 'Settings',
            href: route('admin.settings.index'),
            icon: '⚙️',
            active: route().current('admin.settings.*'),
        },
    ];

    return (
        <div className="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-950 dark:to-slate-900 flex">
            {/* Sidebar */}
            <div
                className={`fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-white transition-transform duration-300 ${
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                } md:translate-x-0 md:static md:inset-auto shadow-2xl`}
            >
                {/* Logo */}
                <div className="flex items-center justify-between h-20 px-6 border-b border-cyan-500/20 bg-gradient-to-r from-cyan-600/10 to-blue-600/10">
                    <Link href={route('admin.dashboard')} className="flex items-center gap-3 group">
                        <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center font-bold text-white shadow-lg group-hover:shadow-cyan-500/50 transition-all">
                            S
                        </div>
                        <div className="flex flex-col">
                            <span className="font-bold text-sm leading-none">StataNex</span>
                            <span className="text-xs text-cyan-400 font-semibold">Admin</span>
                        </div>
                    </Link>
                    <button
                        onClick={() => setSidebarOpen(false)}
                        className="md:hidden text-gray-400 hover:text-white transition-colors"
                    >
                        ✕
                    </button>
                </div>

                {/* Navigation */}
                <nav className="flex-1 px-3 py-6 space-y-2 overflow-y-auto">
                    {menuItems.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={`flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 group ${
                                item.active
                                    ? 'bg-gradient-to-r from-cyan-500 to-blue-500 text-white shadow-lg shadow-cyan-500/30'
                                    : 'text-gray-300 hover:bg-slate-700/50 hover:text-cyan-300'
                            }`}
                        >
                            <span className={`text-xl transition-transform ${item.active ? 'scale-110' : 'group-hover:scale-110'}`}>
                                {item.icon}
                            </span>
                            <span className="font-medium text-sm">{item.label}</span>
                            {item.active && (
                                <div className="ml-auto w-2 h-2 rounded-full bg-white shadow-lg"></div>
                            )}
                        </Link>
                    ))}
                </nav>

                {/* Divider */}
                <div className="border-t border-cyan-500/20"></div>

                {/* User Info */}
                <div className="p-4 bg-gradient-to-r from-slate-800/50 to-slate-700/50">
                    <div className="flex items-center gap-3 mb-4">
                        <div className="w-10 h-10 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center font-bold text-white shadow-lg">
                            {user.name.charAt(0).toUpperCase()}
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-semibold text-white truncate">{user.name}</p>
                            <p className="text-xs text-gray-400 truncate">{user.email}</p>
                        </div>
                    </div>
                    
                    {/* Logout Button */}
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="w-full px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-lg font-medium transition-all duration-200 text-sm shadow-lg hover:shadow-red-500/30"
                    >
                        🚪 Logout
                    </Link>
                </div>
            </div>

            {/* Overlay for mobile */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 z-40 bg-black bg-opacity-50 md:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* Main Content */}
            <div className="flex-1 flex flex-col min-h-screen">
                {/* Mobile Header - Only visible on mobile */}
                <div className="md:hidden bg-white dark:bg-slate-800 shadow-md border-b border-gray-200 dark:border-slate-700">
                    <div className="flex items-center justify-between h-16 px-6">
                        <button
                            onClick={() => setSidebarOpen(!sidebarOpen)}
                            className="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                        >
                            <svg
                                className="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                            </svg>
                        </button>
                        <h1 className="text-lg font-bold text-gray-900 dark:text-white">{title || 'Admin'}</h1>
                        <div className="w-6" />
                    </div>
                </div>

                {/* Page Content */}
                <main className="flex-1 p-6 md:p-8">
                    <div className="max-w-7xl mx-auto">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
