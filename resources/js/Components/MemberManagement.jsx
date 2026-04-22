import { useState, useEffect } from 'react';
import InputError from '@/Components/InputError';
import axios from 'axios';

export default function MemberManagement({ reviewId, isCoordinator, creatorId, currentUserId }) {
    const [members, setMembers] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);
    const [editingMemberId, setEditingMemberId] = useState(null);
    const [editingRole, setEditingRole] = useState('');

    useEffect(() => {
        fetchMembers();
    }, []);

    const fetchMembers = async () => {
        setIsLoading(true);
        setError(null);
        try {
            const response = await axios.get(`/api/reviews/${reviewId}/members`);
            setMembers(response.data.data);
        } catch (err) {
            setError('Failed to load members');
        } finally {
            setIsLoading(false);
        }
    };

    const handleRemoveMember = async (memberId) => {
        if (!confirm('Are you sure you want to remove this member?')) {
            return;
        }

        try {
            await axios.delete(`/api/reviews/${reviewId}/members/${memberId}`);
            setMembers(members.filter(m => m.id !== memberId));
        } catch (err) {
            setError('Failed to remove member');
        }
    };

    const handleUpdateRole = async (memberId, newRole) => {
        try {
            const response = await axios.put(
                `/api/reviews/${reviewId}/members/${memberId}/role`,
                { role: newRole }
            );
            setMembers(members.map(m => m.id === memberId ? response.data.data : m));
            setEditingMemberId(null);
        } catch (err) {
            setError('Failed to update member role');
        }
    };

    const getRoleBadgeColor = (role) => {
        const colors = {
            reviewer: 'bg-blue-100 text-blue-800',
            coordinator: 'bg-purple-100 text-purple-800',
            observer: 'bg-gray-100 text-gray-800',
        };
        return colors[role] || 'bg-gray-100 text-gray-800';
    };

    if (isLoading) {
        return <div className="text-center py-8">Loading members...</div>;
    }

    return (
        <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Members</h3>

            {error && <InputError message={error} className="mb-4" />}

            {members.length === 0 ? (
                <p className="text-gray-500">No members invited yet.</p>
            ) : (
                <div className="space-y-3">
                    {members.map((member) => (
                        <div
                            key={member.id}
                            className="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                        >
                            <div className="flex-1">
                                <p className="font-medium text-gray-900">{member.user.name}</p>
                                <p className="text-sm text-gray-500">{member.user.email}</p>
                                {!member.accepted_at && (
                                    <p className="text-xs text-yellow-600 mt-1">Pending acceptance</p>
                                )}
                            </div>

                            <div className="flex items-center gap-3">
                                {editingMemberId === member.id && isCoordinator ? (
                                    <div className="flex gap-2">
                                        <select
                                            value={editingRole}
                                            onChange={(e) => setEditingRole(e.target.value)}
                                            className="rounded-md border-gray-300 text-sm"
                                        >
                                            <option value="reviewer">Reviewer</option>
                                            <option value="coordinator">Coordinator</option>
                                            <option value="observer">Observer</option>
                                        </select>
                                        <button
                                            onClick={() => handleUpdateRole(member.id, editingRole)}
                                            className="px-2 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600"
                                        >
                                            Save
                                        </button>
                                        <button
                                            onClick={() => setEditingMemberId(null)}
                                            className="px-2 py-1 bg-gray-300 text-gray-700 rounded text-sm hover:bg-gray-400"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                ) : (
                                    <>
                                        <span className={`px-3 py-1 rounded-full text-xs font-medium ${getRoleBadgeColor(member.role)}`}>
                                            {member.role}
                                        </span>
                                        {isCoordinator && member.user_id !== creatorId && (
                                            <>
                                                <button
                                                    onClick={() => {
                                                        setEditingMemberId(member.id);
                                                        setEditingRole(member.role);
                                                    }}
                                                    className="px-3 py-1 text-sm text-blue-600 hover:text-blue-800"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    onClick={() => handleRemoveMember(member.id)}
                                                    className="px-3 py-1 text-sm text-red-600 hover:text-red-800"
                                                >
                                                    Remove
                                                </button>
                                            </>
                                        )}
                                    </>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
