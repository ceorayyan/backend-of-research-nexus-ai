import { useState, useEffect } from 'react';
import InputError from '@/Components/InputError';
import axios from 'axios';

export default function ArticlesList({ reviewId, isCoordinator }) {
    const [articles, setArticles] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchArticles();
    }, []);

    const fetchArticles = async () => {
        setIsLoading(true);
        setError(null);
        try {
            const response = await axios.get(`/api/reviews/${reviewId}/articles`);
            setArticles(response.data.data);
        } catch (err) {
            setError('Failed to load articles');
        } finally {
            setIsLoading(false);
        }
    };

    const handleDeleteArticle = async (articleId) => {
        if (!confirm('Are you sure you want to delete this article?')) {
            return;
        }

        try {
            await axios.delete(`/api/articles/${articleId}`);
            setArticles(articles.filter(a => a.id !== articleId));
        } catch (err) {
            setError('Failed to delete article');
        }
    };

    if (isLoading) {
        return <div className="text-center py-8">Loading articles...</div>;
    }

    return (
        <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Articles</h3>

            {error && <InputError message={error} className="mb-4" />}

            {articles.length === 0 ? (
                <p className="text-gray-500">No articles uploaded yet.</p>
            ) : (
                <div className="space-y-4">
                    {articles.map((article) => (
                        <div
                            key={article.id}
                            className="p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
                        >
                            <div className="flex justify-between items-start">
                                <div className="flex-1">
                                    <h4 className="font-semibold text-gray-900">{article.title}</h4>
                                    {article.authors && (
                                        <p className="text-sm text-gray-600 mt-1">
                                            <span className="font-medium">Authors:</span> {article.authors}
                                        </p>
                                    )}
                                    {article.abstract && (
                                        <p className="text-sm text-gray-600 mt-2 line-clamp-3">
                                            {article.abstract}
                                        </p>
                                    )}
                                    <div className="flex gap-3 mt-3">
                                        {article.url && (
                                            <a
                                                href={article.url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-sm text-blue-600 hover:text-blue-800"
                                            >
                                                View URL
                                            </a>
                                        )}
                                        {article.file_path && (
                                            <a
                                                href={`/storage/${article.file_path}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-sm text-blue-600 hover:text-blue-800"
                                            >
                                                Download File
                                            </a>
                                        )}
                                    </div>
                                </div>
                                {isCoordinator && (
                                    <button
                                        onClick={() => handleDeleteArticle(article.id)}
                                        className="ml-4 px-3 py-1 text-sm text-red-600 hover:text-red-800"
                                    >
                                        Delete
                                    </button>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
