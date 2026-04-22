import { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import axios from 'axios';

export default function ReviewsList() {
    const [reviews, setReviews] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);
    const [page, setPage] = useState(1);
    const [pagination, setPagination] = useState(null);

    useEffect(() => {
        fetchReviews();
    }, [page]);

    const fetchReviews = async () => {
        setIsLoading(true);
        setError(null);
        try {
            const response = await axios.get(`/api/reviews?page=${page}`);
            setReviews(response.data.data);
            setPagination({
                current_page: response.data.current_page,
                last_page: response.data.last_page,
                total: response.data.total,
            });
        } catch (err) {
            setError('Failed to load reviews');
        } finally {
            setIsLoading(false);
        }
    };

    const getStatusColor = (status) => {
        const colors = {
            draft: 'bg-gray-100 text-gray-800',
            active: 'bg-blue-100 text-blue-800',
            completed: 'bg-green-100 text-green-800',
            archived: 'bg-red-100 text-red-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    if (isLoading) {
        return <div className="text-center py-8">Loading reviews...</div>;
    }

    if (error) {
        return <div className="text-center py-8 text-red-600">{error}</div>;
    }

    if (reviews.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500">
                No reviews yet. Create one to get started!
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {reviews.map((review) => (
                <Link
                    key={review.id}
                    href={`/reviews/${review.id}`}
                    className="block p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow"
                >
                    <div className="flex justify-between items-start">
                        <div className="flex-1">
                            <h3 className="text-lg font-semibold text-gray-900">
                                {review.title}
                            </h3>
                            {review.description && (
                                <p className="text-gray-600 text-sm mt-1 line-clamp-2">
                                    {review.description}
                                </p>
                            )}
                            <div className="flex gap-2 mt-3 text-sm text-gray-500">
                                <span>By {review.user.name}</span>
                                <span>•</span>
                                <span>{review.articles?.length || 0} articles</span>
                                <span>•</span>
                                <span>{review.members?.length || 0} members</span>
                            </div>
                        </div>
                        <span className={`px-3 py-1 rounded-full text-xs font-medium ${getStatusColor(review.status)}`}>
                            {review.status}
                        </span>
                    </div>
                </Link>
            ))}

            {pagination && pagination.last_page > 1 && (
                <div className="flex justify-center gap-2 mt-6">
                    <button
                        onClick={() => setPage(Math.max(1, page - 1))}
                        disabled={page === 1}
                        className="px-4 py-2 bg-gray-200 rounded disabled:opacity-50"
                    >
                        Previous
                    </button>
                    <span className="px-4 py-2">
                        Page {pagination.current_page} of {pagination.last_page}
                    </span>
                    <button
                        onClick={() => setPage(Math.min(pagination.last_page, page + 1))}
                        disabled={page === pagination.last_page}
                        className="px-4 py-2 bg-gray-200 rounded disabled:opacity-50"
                    >
                        Next
                    </button>
                </div>
            )}
        </div>
    );
}
