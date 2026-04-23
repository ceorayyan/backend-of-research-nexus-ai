import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function AdminDashboard({ users }) {
    const totalUsers = users?.length || 0;
    const adminUsers = users?.filter(u => u.role === 'admin').length || 0;
    const regularUsers = totalUsers - adminUsers;

    return (
        <AuthenticatedLayout
            header={<h2 className="text-3xl font-bold text-gray-900">Admin Dashboard</h2>}
        >
            <Head title="Admin Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Stats Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        {/* Total Users Card */}
                        <div className="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-gray-500 text-sm font-medium">Total Users</p>
                                    <p className="text-4xl font-bold text-gray-900 mt-2">{totalUsers}</p>
                                </div>
                                <div className="text-5xl text-blue-500">👥</div>
                            </div>
                        </div>

                        {/* Admin Users Card */}
                        <div className="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-gray-500 text-sm font-medium">Admin Users</p>
                                    <p className="text-4xl font-bold text-gray-900 mt-2">{adminUsers}</p>
                                </div>
                                <div className="text-5xl text-red-500">🔐</div>
                            </div>
                        </div>

                        {/* Regular Users Card */}
                        <div className="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-gray-500 text-sm font-medium">Regular Users</p>
                                    <p className="text-4xl font-bold text-gray-900 mt-2">{regularUsers}</p>
                                </div>
                                <div className="text-5xl text-green-500">👤</div>
                            </div>
                        </div>
                    </div>

                    {/* Quick Actions */}
                    <div className="bg-white rounded-lg shadow-md p-8">
                        <h3 className="text-2xl font-bold text-gray-900 mb-6">Quick Actions</h3>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <Link
                                href={route('admin.users.index')}
                                className="flex items-center gap-4 p-6 bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 rounded-lg transition-all transform hover:scale-105"
                            >
                                <span className="text-4xl">👥</span>
                                <div>
                                    <p className="font-bold text-gray-900 text-lg">Manage Users</p>
                                    <p className="text-sm text-gray-600">View and edit users</p>
                                </div>
                            </Link>

                            <Link
                                href={route('admin.settings.index')}
                                className="flex items-center gap-4 p-6 bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 rounded-lg transition-all transform hover:scale-105"
                            >
                                <span className="text-4xl">⚙️</span>
                                <div>
                                    <p className="font-bold text-gray-900 text-lg">Website Settings</p>
                                    <p className="text-sm text-gray-600">Manage branding</p>
                                </div>
                            </Link>

                            <Link
                                href={route('admin.users.create')}
                                className="flex items-center gap-4 p-6 bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 rounded-lg transition-all transform hover:scale-105"
                            >
                                <span className="text-4xl">➕</span>
                                <div>
                                    <p className="font-bold text-gray-900 text-lg">Add New User</p>
                                    <p className="text-sm text-gray-600">Create new account</p>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
