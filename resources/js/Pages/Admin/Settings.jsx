import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { useRef, useState } from 'react';

export default function AdminSettings({ settings: initialSettings }) {
    const fileInputRef = useRef(null);
    const [previewUrl, setPreviewUrl] = useState(null);
    const { data, setData, post, errors, processing } = useForm({
        website_name: initialSettings?.website_name || 'StataNexus.Ai',
        logo: null,
    });

    const handleLogoChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('logo', file);
            // Create preview URL
            const reader = new FileReader();
            reader.onloadend = () => {
                setPreviewUrl(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleRemoveLogo = () => {
        if (confirm('Are you sure you want to remove the logo?')) {
            router.post(route('admin.settings.removeLogo'));
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('website_name', data.website_name);
        if (data.logo) {
            formData.append('logo', data.logo);
        }

        post(route('admin.settings.update'), {
            data: formData,
            forceFormData: true,
            onSuccess: () => {
                setPreviewUrl(null);
            },
        });
    };

    const currentLogoUrl = previewUrl || initialSettings?.logo_url;

    return (
        <AuthenticatedLayout
            header={<h2 className="text-3xl font-bold text-gray-900">Website Settings</h2>}
        >
            <Head title="Website Settings" />

            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white rounded-lg shadow-md overflow-hidden">
                        <div className="bg-gradient-to-r from-green-600 to-green-700 px-8 py-6">
                            <h3 className="text-2xl font-bold text-white">Branding Configuration</h3>
                        </div>

                        <form onSubmit={handleSubmit} className="p-8 space-y-6">
                            {/* Website Name */}
                            <div>
                                <label className="block text-sm font-semibold text-gray-900 mb-2">
                                    Website Name
                                </label>
                                <input
                                    type="text"
                                    value={data.website_name}
                                    onChange={(e) => setData('website_name', e.target.value)}
                                    placeholder="Enter website name"
                                    className={`w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 ${
                                        errors.website_name ? 'border-red-500' : 'border-gray-300'
                                    }`}
                                    required
                                />
                                {errors.website_name && <p className="text-red-600 text-sm mt-1">{errors.website_name}</p>}
                                <p className="text-xs text-gray-500 mt-1">
                                    This name will appear in the header and throughout the application
                                </p>
                            </div>

                            {/* Logo Section */}
                            <div>
                                <label className="block text-sm font-semibold text-gray-900 mb-2">
                                    Website Logo
                                </label>

                                {/* Current Logo Preview */}
                                <div className="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                    <p className="text-xs text-gray-600 mb-3 font-medium">Logo Preview:</p>
                                    <div className="flex items-center gap-4">
                                        {currentLogoUrl ? (
                                            <>
                                                <img
                                                    src={currentLogoUrl}
                                                    alt="Logo preview"
                                                    className="h-16 w-16 object-contain rounded border border-gray-300 bg-white"
                                                    onError={(e) => {
                                                        e.target.style.display = 'none';
                                                        e.target.nextElementSibling.style.display = 'flex';
                                                    }}
                                                />
                                                <div className="w-16 h-16 bg-gray-900 text-white rounded flex items-center justify-center font-bold text-lg" style={{ display: 'none' }}>
                                                    {data.website_name.charAt(0).toUpperCase()}
                                                </div>
                                                <div className="flex-1">
                                                    <p className="text-sm font-medium text-gray-900">Image Logo</p>
                                                    <p className="text-xs text-gray-500">{previewUrl ? 'New logo (not saved yet)' : 'Current logo'}</p>
                                                </div>
                                                {initialSettings?.logo_url && !previewUrl && (
                                                    <button
                                                        type="button"
                                                        onClick={handleRemoveLogo}
                                                        className="px-3 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors"
                                                    >
                                                        Remove Logo
                                                    </button>
                                                )}
                                            </>
                                        ) : (
                                            <div className="flex items-center gap-3">
                                                <div className="w-16 h-16 bg-gray-900 text-white rounded flex items-center justify-center font-bold text-lg">
                                                    {data.website_name.charAt(0).toUpperCase()}
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900">Text Logo</p>
                                                    <p className="text-xs text-gray-500">Initial: {data.website_name.charAt(0).toUpperCase()}</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* File Upload */}
                                <div className="mb-4">
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept="image/png,image/jpeg,image/jpg,image/gif,image/webp"
                                        onChange={handleLogoChange}
                                        className="block w-full text-sm text-gray-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-lg file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-green-600 file:text-white
                                            hover:file:bg-green-700
                                            cursor-pointer"
                                    />
                                    <p className="text-xs text-gray-500 mt-2">
                                        Supported formats: PNG, JPEG, JPG, GIF, WebP (Max 5MB)
                                    </p>
                                    {errors.logo && <p className="text-red-600 text-sm mt-1">{errors.logo}</p>}
                                </div>
                            </div>

                            {/* Header Preview */}
                            <div>
                                <label className="block text-sm font-semibold text-gray-900 mb-2">
                                    Header Preview
                                </label>
                                <div className="p-4 bg-gray-50 border border-gray-200 rounded-lg flex items-center gap-3">
                                    {currentLogoUrl ? (
                                        <img
                                            src={currentLogoUrl}
                                            alt="Logo preview"
                                            className="h-8 w-8 object-contain rounded"
                                            onError={(e) => {
                                                e.target.style.display = 'none';
                                                e.target.nextElementSibling.style.display = 'flex';
                                            }}
                                        />
                                    ) : null}
                                    <div className="w-8 h-8 bg-gray-900 text-white rounded flex items-center justify-center font-bold text-xs" style={{ display: currentLogoUrl ? 'none' : 'flex' }}>
                                        {data.website_name.charAt(0).toUpperCase()}
                                    </div>
                                    <span className="font-semibold text-gray-900">{data.website_name}</span>
                                </div>
                            </div>

                            {/* Action Buttons */}
                            <div className="flex gap-3 pt-6 border-t border-gray-200">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors disabled:opacity-50"
                                >
                                    {processing ? '💾 Saving...' : '💾 Save Changes'}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => window.history.back()}
                                    className="bg-gray-300 hover:bg-gray-400 text-gray-900 px-6 py-2 rounded-lg font-medium transition-colors"
                                >
                                    ← Back
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
