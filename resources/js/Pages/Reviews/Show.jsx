import { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useParams } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import axios from 'axios';
import ArticlesList from '@/Components/ArticlesList';
import UploadArticleSection from '@/Components/UploadArticleSection';
import InviteMemberSection from '@/Components/InviteMemberSection';
import MemberManagement from '@/Components/MemberManagement';

export default function ReviewShow() {
    const { id } = useParams();
    const [review, setReview] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);
    const [activeTab, setActiveTab] = useState('overview');
    const [isEditing, setIsEditing] = useState(false);
    const [editData, setEditData] = useState({});
    const [currentUser, setCurrentUser] = useState(null);
    const [refreshKey, setRefreshKey] = useState(0);

    useEffect(() => {
        fetchReview();
        fetchCurrentUser();
    }, [refreshKey]);

    const fetchReview = async () => {
        setIsLoading(true);
        setError(null);
        try {
            const response = await axios.get(`/api/reviews/${id}`);
            setReview(response.data);
            setEditData({
                title: response.data.title,
                description: response.data.description,
                status: response.data.status,
            });
        } catch (err) {
            setError('Failed to load review');
        } finally {
            setIsLoading(false);
        }
    };

    const fetchCurrentUser = async () => {
        try {
            const response = await axios.get('/api/user');
            setCurrentUser(response.data);
        } catch (err) {
            console.error('Failed to fetch current user');
        }
    };

    const isCoordinator = currentUser && review && (
        review.user_id === currentUser.id ||
        review.members?.some(m => m.user_id === currentUser.id && m.role === 'coordinator')
    );

    const handleUpdateReview = async () => {
        try {
            const response = await axios.put(`/api/reviews/${id}`, editData);
            setReview(response.data.data);
            setIsEditing(false);
        } catch (err) {
            setError('Failed to update review');
        }
    };

    const handleDeleteReview = async () => {
        if (!confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
            return;
        }

        try {
            await axios.delete(`/api/reviews/${id}`);
            window.location.href = '/reviews';
        } catch (err) {
            setError('Failed to delete review');
        }
    };

    const handleArticleAdded = () => {
        setRefreshKey(prev => prev + 1);
    };

    const handleMemberInvited = () => {
        setRefreshKey(prev => prev + 1);
    };

    if (isLoading) {
        return (
            <AuthenticatedLayout>
                <Head title="Review" />
                <div className="py-12">
                    <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <div className="text-center">Loading...</div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    if (!review) {
        return (
            <AuthenticatedLayout>
                <Head title="Review" />
                <div className="py-12">
                    <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <div className="text-center text-red-600">Review not found</div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        {review.title}
                    </h2>
                    {isCoordinator && (
                        <div className="flex gap-2">
                            <PrimaryButton onClick={() => setIsEditing(!isEditing)}>
                                {isEditing ? 'Cancel' : 'Edit'}
                            </PrimaryButton>
                            <DangerButton onClick={handleDeleteReview}>
                                Delete
                            </DangerButton>
                        </div>
                    )}
                </div>
            }
        >
            <Head title={review.title} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {error && (
                        <div className="mb-4">
                            <InputError message={error} />
                        </div>
                    )}

                    {isEditing && isCoordinator && (
                        <div className="mb-6 bg-white shadow-sm rounded-lg p-6">
                            <h3 className="text-lg font-semibold mb-4">Edit Review</h3>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Title</label>
                                    <input
                                        type="text"
                                        value={editData.title}
                                        onChange={(e) => setEditData(prev => ({ ...prev, title: e.target.value }))}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea
                                        value={editData.description || ''}
                                        onChange={(e) => setEditData(prev => ({ ...prev, description: e.target.value }))}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        rows="3"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Status</label>
                                    <select
                                        value={editData.status}
                                        onChange={(e) => setEditData(prev => ({ ...prev, status: e.target.value }))}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="draft">Draft</option>
                                        <option value="active">Active</option>
                                        <option value="completed">Completed</option>
                                        <option value="archived">Archived</option>
                                    </select>
                                </div>
                                <div className="flex justify-end gap-2">
                                    <button
                                        onClick={() => setIsEditing(false)}
                                        className="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                                    >
                                        Cancel
                                    </button>
                                    <PrimaryButton onClick={handleUpdateReview}>
                                        Save Changes
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Tabs */}
                    <div className="mb-6 border-b border-gray-200">
                        <div className="flex gap-4">
                            {['overview', 'articles', 'members'].map((tab) => (
                                <button
                                    key={tab}
                                    onClick={() => setActiveTab(tab)}
                                    className={`px-4 py-2 font-medium border-b-2 transition-colors ${
                                        activeTab === tab
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-600 hover:text-gray-900'
                                    }`}
                                >
                                    {tab.charAt(0).toUpperCase() + tab.slice(1)}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Tab Content */}
                    <div className="space-y-6">
                        {activeTab === 'overview' && (
                            <div className="bg-white shadow-sm rounded-lg p-6">
                                <h3 className="text-lg font-semibold mb-4">Overview</h3>
                                <div className="space-y-4">
                                    <div>
                                        <p className="text-sm text-gray-600">Status</p>
                                        <p className="text-lg font-medium text-gray-900 capitalize">{review.status}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">Created by</p>
                                        <p className="text-lg font-medium text-gray-900">{review.user.name}</p>
                                    </div>
                                    {review.description && (
                                        <div>
                                            <p className="text-sm text-gray-600">Description</p>
                                            <p className="text-gray-900">{review.description}</p>
                                        </div>
                                    )}
                                    <div>
                                        <p className="text-sm text-gray-600">Created at</p>
                                        <p className="text-gray-900">{new Date(review.created_at).toLocaleDateString()}</p>
                                    </div>
                                </div>
                            </div>
                        )}

                        {activeTab === 'articles' && (
                            <>
                                <UploadArticleSection
                                    reviewId={id}
                                    onArticleAdded={handleArticleAdded}
                                />
                                <ArticlesList
                                    key={refreshKey}
                                    reviewId={id}
                                    isCoordinator={isCoordinator}
                                />
                            </>
                        )}

                        {activeTab === 'members' && (
                            <>
                                <InviteMemberSection
                                    reviewId={id}
                                    isCoordinator={isCoordinator}
                                    onMemberInvited={handleMemberInvited}
                                />
                                <MemberManagement
                                    key={refreshKey}
                                    reviewId={id}
                                    isCoordinator={isCoordinator}
                                    creatorId={review.user_id}
                                    currentUserId={currentUser?.id}
                                />
                            </>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
