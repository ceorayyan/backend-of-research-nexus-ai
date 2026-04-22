import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import CreateReviewModal from '@/Components/CreateReviewModal';
import ReviewsList from '@/Components/ReviewsList';

export default function ReviewsIndex() {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [refreshKey, setRefreshKey] = useState(0);

    const handleReviewCreated = () => {
        setRefreshKey(prev => prev + 1);
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Reviews
                    </h2>
                    <PrimaryButton onClick={() => setIsModalOpen(true)}>
                        Create Review
                    </PrimaryButton>
                </div>
            }
        >
            <Head title="Reviews" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <ReviewsList key={refreshKey} />
                        </div>
                    </div>
                </div>
            </div>

            <CreateReviewModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                onSuccess={handleReviewCreated}
            />
        </AuthenticatedLayout>
    );
}
