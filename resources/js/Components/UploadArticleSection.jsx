import { useState } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import InputError from '@/Components/InputError';
import axios from 'axios';

export default function UploadArticleSection({ reviewId, onArticleAdded }) {
    const [isOpen, setIsOpen] = useState(false);
    const [formData, setFormData] = useState({
        title: '',
        authors: '',
        abstract: '',
        url: '',
        file: null,
    });
    const [errors, setErrors] = useState({});
    const [isLoading, setIsLoading] = useState(false);

    const handleChange = (e) => {
        const { name, value, files } = e.target;
        if (name === 'file') {
            setFormData(prev => ({
                ...prev,
                file: files[0],
            }));
        } else {
            setFormData(prev => ({
                ...prev,
                [name]: value,
            }));
        }
        if (errors[name]) {
            setErrors(prev => ({
                ...prev,
                [name]: '',
            }));
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setIsLoading(true);
        setErrors({});

        const data = new FormData();
        data.append('title', formData.title);
        data.append('authors', formData.authors);
        data.append('abstract', formData.abstract);
        data.append('url', formData.url);
        if (formData.file) {
            data.append('file', formData.file);
        }

        try {
            const response = await axios.post(`/api/reviews/${reviewId}/articles`, data, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            setFormData({ title: '', authors: '', abstract: '', url: '', file: null });
            setIsOpen(false);
            onArticleAdded(response.data.data);
        } catch (error) {
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            } else {
                setErrors({ submit: error.response?.data?.message || 'Failed to upload article' });
            }
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="bg-white rounded-lg shadow p-6">
            <div className="flex justify-between items-center mb-4">
                <h3 className="text-lg font-semibold text-gray-900">Upload Article</h3>
                {!isOpen && (
                    <PrimaryButton onClick={() => setIsOpen(true)}>
                        Add Article
                    </PrimaryButton>
                )}
            </div>

            {isOpen && (
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Title *</label>
                        <input
                            type="text"
                            name="title"
                            value={formData.title}
                            onChange={handleChange}
                            placeholder="Article title"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                        <InputError message={errors.title} className="mt-2" />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Authors</label>
                        <input
                            type="text"
                            name="authors"
                            value={formData.authors}
                            onChange={handleChange}
                            placeholder="Author names (comma separated)"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <InputError message={errors.authors} className="mt-2" />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Abstract</label>
                        <textarea
                            name="abstract"
                            value={formData.abstract}
                            onChange={handleChange}
                            placeholder="Article abstract"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows="3"
                        />
                        <InputError message={errors.abstract} className="mt-2" />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">URL</label>
                        <input
                            type="url"
                            name="url"
                            value={formData.url}
                            onChange={handleChange}
                            placeholder="https://example.com/article"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <InputError message={errors.url} className="mt-2" />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">File (PDF, DOC, DOCX)</label>
                        <input
                            type="file"
                            name="file"
                            onChange={handleChange}
                            accept=".pdf,.doc,.docx"
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.file} className="mt-2" />
                    </div>

                    {errors.submit && (
                        <InputError message={errors.submit} className="mt-2" />
                    )}

                    <div className="flex justify-end gap-3">
                        <button
                            type="button"
                            onClick={() => setIsOpen(false)}
                            className="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300"
                        >
                            Cancel
                        </button>
                        <PrimaryButton disabled={isLoading}>
                            {isLoading ? 'Uploading...' : 'Upload Article'}
                        </PrimaryButton>
                    </div>
                </form>
            )}
        </div>
    );
}
