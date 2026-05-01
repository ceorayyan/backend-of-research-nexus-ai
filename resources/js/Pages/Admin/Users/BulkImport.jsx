import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function BulkImportUsers() {
    const [emailsText, setEmailsText] = useState('');
    const [sendEmails, setSendEmails] = useState(true);
    const [loading, setLoading] = useState(false);
    const [results, setResults] = useState(null);
    const [error, setError] = useState('');

    const handleImport = async (e) => {
        e.preventDefault();
        setError('');
        setResults(null);

        if (!emailsText.trim()) {
            setError('Please enter at least one email address');
            return;
        }

        setLoading(true);

        try {
            const apiUrl = import.meta.env.VITE_API_URL || '';
            const response = await fetch(`${apiUrl}/admin/users/bulk-import`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('sanctum_token') || localStorage.getItem('auth_token')}`,
                },
                body: JSON.stringify({
                    emails: emailsText,
                    send_emails: sendEmails,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                setError(data.message || 'Failed to import users');
                return;
            }

            setResults(data.results);
            setEmailsText('');
        } catch (err) {
            setError('An error occurred during import. Please try again.');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const downloadResults = () => {
        if (!results) return;

        const csv = [
            ['Email', 'Name', 'Password', 'Status'],
            ...results.created.map(u => [u.email, u.name, u.password, 'Created']),
            ...results.failed.map(u => [u.email, '', '', `Failed: ${u.reason}`]),
            ...results.skipped.map(u => [u.email, '', '', `Skipped: ${u.reason}`]),
        ];

        const csvContent = csv.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `bulk-import-results-${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
    };

    return (
        <AdminLayout title="Bulk Import Users">
            <Head title="Bulk Import Users" />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Import Form */}
                        <div className="lg:col-span-2">
                            <form onSubmit={handleImport} className="bg-white rounded-lg shadow-md p-8">
                                <div className="mb-6">
                                    <label className="block text-sm font-semibold text-gray-900 mb-2">
                                        Email Addresses
                                    </label>
                                    <p className="text-xs text-gray-600 mb-3">
                                        Enter one email per line, or separate with commas or semicolons. Maximum 500 emails.
                                    </p>
                                    <textarea
                                        value={emailsText}
                                        onChange={(e) => setEmailsText(e.target.value)}
                                        placeholder="user1@example.com&#10;user2@example.com&#10;user3@example.com"
                                        className="w-full h-48 px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                                        disabled={loading}
                                    />
                                </div>

                                <div className="mb-6">
                                    <label className="flex items-center gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={sendEmails}
                                            onChange={(e) => setSendEmails(e.target.checked)}
                                            disabled={loading}
                                            className="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <span className="text-sm text-gray-700">
                                            Send welcome emails with credentials to new users
                                        </span>
                                    </label>
                                </div>

                                {error && (
                                    <div className="mb-6 p-4 rounded-lg bg-red-50 border border-red-200">
                                        <p className="text-sm text-red-700">{error}</p>
                                    </div>
                                )}

                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="w-full px-4 py-3 rounded-lg font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
                                >
                                    {loading ? (
                                        <span className="flex items-center justify-center gap-2">
                                            <svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                            </svg>
                                            Importing...
                                        </span>
                                    ) : (
                                        'Import Users'
                                    )}
                                </button>
                            </form>
                        </div>

                        {/* Info Panel */}
                        <div className="space-y-4">
                            <div className="bg-blue-50 rounded-lg border border-blue-200 p-4">
                                <h3 className="font-semibold text-blue-900 mb-2">Format Examples</h3>
                                <div className="text-xs text-blue-800 space-y-2 font-mono">
                                    <p>One per line:</p>
                                    <p className="bg-white p-2 rounded">
                                        user1@example.com<br />
                                        user2@example.com
                                    </p>
                                    <p className="mt-3">Comma-separated:</p>
                                    <p className="bg-white p-2 rounded">
                                        user1@example.com, user2@example.com
                                    </p>
                                </div>
                            </div>

                            <div className="bg-green-50 rounded-lg border border-green-200 p-4">
                                <h3 className="font-semibold text-green-900 mb-2">What Happens</h3>
                                <ul className="text-xs text-green-800 space-y-1">
                                    <li>✓ Random passwords generated</li>
                                    <li>✓ Users created with 'user' role</li>
                                    <li>✓ Welcome emails sent (optional)</li>
                                    <li>✓ Duplicates skipped</li>
                                    <li>✓ Invalid emails reported</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {/* Results */}
                    {results && (
                        <div className="mt-8 bg-white rounded-lg shadow-md p-8">
                            <div className="flex items-center justify-between mb-6">
                                <h2 className="text-2xl font-bold text-gray-900">Import Results</h2>
                                <button
                                    onClick={downloadResults}
                                    className="px-4 py-2 rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors"
                                >
                                    📥 Download CSV
                                </button>
                            </div>

                            <div className="grid grid-cols-3 gap-4 mb-6">
                                <div className="p-4 rounded-lg bg-green-50 border border-green-200">
                                    <p className="text-sm text-green-700">Created</p>
                                    <p className="text-3xl font-bold text-green-900">{results.created.length}</p>
                                </div>
                                <div className="p-4 rounded-lg bg-red-50 border border-red-200">
                                    <p className="text-sm text-red-700">Failed</p>
                                    <p className="text-3xl font-bold text-red-900">{results.failed.length}</p>
                                </div>
                                <div className="p-4 rounded-lg bg-yellow-50 border border-yellow-200">
                                    <p className="text-sm text-yellow-700">Skipped</p>
                                    <p className="text-3xl font-bold text-yellow-900">{results.skipped.length}</p>
                                </div>
                            </div>

                            {/* Created Users */}
                            {results.created.length > 0 && (
                                <div className="mb-6">
                                    <h3 className="font-semibold text-gray-900 mb-3">Successfully Created ({results.created.length})</h3>
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="border-b border-gray-200">
                                                    <th className="text-left py-2 px-3 text-gray-600 font-medium">Email</th>
                                                    <th className="text-left py-2 px-3 text-gray-600 font-medium">Name</th>
                                                    <th className="text-left py-2 px-3 text-gray-600 font-medium">Password</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {results.created.map((user, idx) => (
                                                    <tr key={idx} className="border-b border-gray-100 hover:bg-gray-50">
                                                        <td className="py-2 px-3 text-gray-900">{user.email}</td>
                                                        <td className="py-2 px-3 text-gray-600">{user.name}</td>
                                                        <td className="py-2 px-3 font-mono text-xs text-gray-600">{user.password}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            )}

                            {/* Failed Users */}
                            {results.failed.length > 0 && (
                                <div className="mb-6">
                                    <h3 className="font-semibold text-gray-900 mb-3">Failed ({results.failed.length})</h3>
                                    <div className="space-y-2">
                                        {results.failed.map((item, idx) => (
                                            <div key={idx} className="p-3 rounded-lg bg-red-50 border border-red-200">
                                                <p className="text-sm text-red-900">
                                                    <span className="font-mono">{item.email}</span> - {item.reason}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Skipped Users */}
                            {results.skipped.length > 0 && (
                                <div>
                                    <h3 className="font-semibold text-gray-900 mb-3">Skipped ({results.skipped.length})</h3>
                                    <div className="space-y-2">
                                        {results.skipped.map((item, idx) => (
                                            <div key={idx} className="p-3 rounded-lg bg-yellow-50 border border-yellow-200">
                                                <p className="text-sm text-yellow-900">
                                                    <span className="font-mono">{item.email}</span> - {item.reason}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            <button
                                onClick={() => {
                                    setResults(null);
                                    setEmailsText('');
                                }}
                                className="w-full mt-6 px-4 py-3 rounded-lg font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-colors"
                            >
                                Import More Users
                            </button>
                        </div>
                    )}
        </AdminLayout>
    );
}
