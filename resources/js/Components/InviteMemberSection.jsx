import { useState } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import InputError from '@/Components/InputError';
import axios from 'axios';

export default function InviteMemberSection({ reviewId, isCoordinator, onMemberInvited }) {
    const [isOpen, setIsOpen] = useState(false);
    const [formData, setFormData] = useState({
        email: '',
        role: 'reviewer',
    });
    const [errors, setErrors] = useState({});
    const [isLoading, setIsLoading] = useState(false);

    if (!isCoordinator) {
        return null;
    }

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value,
        }));
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

        try {
            const response = await axios.post(`/api/reviews/${reviewId}/invite`, formData);
            setFormData({ email: '', role: 'reviewer' });
            setIsOpen(false);
            onMemberInvited(response.data.data);
        } catch (error) {
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            } else {
                setErrors({ submit: error.response?.data?.message || 'Failed to invite member' });
            }
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="bg-white rounded-lg shadow p-6">
            <div className="flex justify-between items-center mb-4">
                <h3 className="text-lg font-semibold text-gray-900">Invite Member</h3>
                {!isOpen && (
                    <PrimaryButton onClick={() => setIsOpen(true)}>
                        Invite Member
                    </PrimaryButton>
                )}
            </div>

            {isOpen && (
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Email *</label>
                        <input
                            type="email"
                            name="email"
                            value={formData.email}
                            onChange={handleChange}
                            placeholder="user@example.com"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                        <InputError message={errors.email} className="mt-2" />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Role *</label>
                        <select
                            name="role"
                            value={formData.role}
                            onChange={handleChange}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="reviewer">Reviewer</option>
                            <option value="coordinator">Coordinator</option>
                            <option value="observer">Observer</option>
                        </select>
                        <InputError message={errors.role} className="mt-2" />
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
                            {isLoading ? 'Inviting...' : 'Send Invite'}
                        </PrimaryButton>
                    </div>
                </form>
            )}
        </div>
    );
}
