import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';

export default function Dashboard() {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-2xl font-bold mb-6">Welcome to StataNexus.Ai</h3>
                            <p className="mb-6 text-gray-600">You're logged in! Start managing your reviews below.</p>
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <Link
                                    href={route('reviews.index')}
                                    className="p-6 bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 rounded-lg transition-all transform hover:scale-105 block"
                                >
                                    <div className="text-4xl mb-3">📋</div>
                                    <h4 className="text-xl font-bold text-gray-900">My Reviews</h4>
                                    <p className="text-sm text-gray-600 mt-2">View and manage your systematic reviews</p>
                                </Link>

                                <Link
                                    href={route('profile.edit')}
                                    className="p-6 bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 rounded-lg transition-all transform hover:scale-105 block"
                                >
                                    <div className="text-4xl mb-3">👤</div>
                                    <h4 className="text-xl font-bold text-gray-900">Profile</h4>
                                    <p className="text-sm text-gray-600 mt-2">Update your profile information</p>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
