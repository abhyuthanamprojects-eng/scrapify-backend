import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ settings, homeBanners = [] }) {
    const normalizeCsvValue = (value) => Array.isArray(value) ? value.join(', ') : (value ?? '');

    const { data, setData, post, processing, errors } = useForm({
        ...settings,
        donation_products: normalizeCsvValue(settings.donation_products),
        corporate_meeting_types: normalizeCsvValue(settings.corporate_meeting_types),
        scrap_proof_image_labels: normalizeCsvValue(settings.scrap_proof_image_labels),
    });

    const [activeTab, setActiveTab] = useState('features');

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.app-settings.update'));
    };

    const Toggle = ({ name, label, description }) => (
        <div className="flex items-center justify-between gap-4 p-4 bg-gray-50 rounded-lg border border-gray-100">
            <div className="flex-1">
                <label className="text-sm font-semibold text-gray-800 block">{label}</label>
                <p className="text-xs text-gray-500">{description}</p>
            </div>
            <button
                type="button"
                onClick={() => setData(name, !data[name])}
                className={`relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors focus:outline-none ${data[name] ? 'bg-primary' : 'bg-gray-300'}`}
            >
                <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${data[name] ? 'translate-x-6' : 'translate-x-1'}`} />
            </button>
        </div>
    );

    const Input = ({ name, label, type = "text", description, ...props }) => (
        <div className="flex flex-col gap-1">
            <label className="text-sm font-semibold text-gray-800">{label}</label>
            <input
                {...props}
                type={type}
                value={data[name] ?? ''}
                onChange={e => setData(name, e.target.value)}
                onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        e.currentTarget.blur();
                    }
                    props.onKeyDown?.(e);
                }}
                className="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none text-sm"
            />
            {description && <p className="text-xs text-gray-500">{description}</p>}
            {errors[name] && <p className="text-xs text-red-500 mt-1">{errors[name]}</p>}
        </div>
    );

    return (
        <AdminLayout>
            <Head title="App Settings" />

            <div className="max-w-4xl mx-auto">
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">App Settings</h1>
                        <p className="text-gray-500 text-sm">Manage mobile app feature flags and global configurations.</p>
                    </div>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="flex border-b border-gray-100">
                        <button
                            type="button"
                            onClick={() => setActiveTab('features')}
                            className={`px-6 py-4 text-sm font-medium transition-colors ${activeTab === 'features' ? 'text-primary border-b-2 border-primary' : 'text-gray-500 hover:text-gray-700'}`}
                        >
                            Feature Toggles
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('config')}
                            className={`px-6 py-4 text-sm font-medium transition-colors ${activeTab === 'config' ? 'text-primary border-b-2 border-primary' : 'text-gray-500 hover:text-gray-700'}`}
                        >
                            General Config
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('intervals')}
                            className={`px-6 py-4 text-sm font-medium transition-colors ${activeTab === 'intervals' ? 'text-primary border-b-2 border-primary' : 'text-gray-500 hover:text-gray-700'}`}
                        >
                            Performance & Intervals
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('msg91')}
                            className={`px-6 py-4 text-sm font-medium transition-colors ${activeTab === 'msg91' ? 'text-primary border-b-2 border-primary' : 'text-gray-500 hover:text-gray-700'}`}
                        >
                            SMS Gateway
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('banners')}
                            className={`px-6 py-4 text-sm font-medium transition-colors ${activeTab === 'banners' ? 'text-primary border-b-2 border-primary' : 'text-gray-500 hover:text-gray-700'}`}
                        >
                            Home Banners
                        </button>
                    </div>

                    {activeTab === 'banners' && (
                        <div className="p-6">
                            <HomeBanners banners={homeBanners} />
                        </div>
                    )}

                    {activeTab !== 'banners' && (
                    <form onSubmit={submit} className="p-6">
                        {activeTab === 'features' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <Toggle name="donation_enabled" label="Donations" description="Enable scrap donation feature in app" />
                                <Toggle name="scrap_pickup_enabled" label="Scrap Pickup" description="Enable scrap pickup booking feature" />
                                <Toggle name="reschedule_enabled" label="Rescheduling" description="Allow customers to reschedule bookings" />
                                <Toggle name="verification_required" label="KYC Verification" description="Require KYC for certain features" />
                                <Toggle name="manual_item_add_edit_enabled" label="Manual Item Edit" description="Allow users to edit items during pickup" />
                                <Toggle name="bill_generation_enabled" label="Bill Generation" description="Auto-generate digital bills after pickup" />
                                <Toggle name="scrap_proof_images_required" label="Mandatory Scrap Proof Images" description="Require all proof labels before scrap booking submit" />
                            </div>
                        )}

                        {activeTab === 'config' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <Input name="app_version" label="App Version" description="Current stable version (e.g. 1.0.5)" />
                                <Input name="customer_support_number" label="Customer Support Number" />
                                <Input name="support_phone" label="Secondary Support Phone" />
                                <Input name="feedback_url" label="Feedback Link (Sent via SMS)" description="The URL sent to customers for feedback after pickup" />
                                <Input name="default_city_id" label="Default City ID" type="number" />
                                <Input name="minimum_free_pickup_amount" label="Minimum Free Pickup Amount (₹)" type="number" description="If estimated amount is below this, shipping charge will be deducted." />
                                <Input name="low_value_shipping_charge" label="Low Value Shipping Charge (₹)" type="number" description="Shipping deduction applied when booking value is below minimum free pickup amount." />
                                <Input name="warehouse_service_pincodes_limit" label="Warehouse Service Pincode Limit" type="number" description="Maximum number of service pincodes allowed per warehouse." />
                                <Input name="donation_products" label="Donation Products (comma separated)" description="Example: Cloth, Shoes, Toys, Books" />

                                <div className="md:col-span-2 border-t border-gray-100 pt-4 mt-2">
                                    <h3 className="text-sm font-semibold text-gray-800 mb-1">App Update / Force Update</h3>
                                    <p className="text-xs text-gray-500 mb-4">Controls the old-version update popup shown to users.</p>
                                </div>
                                <Input name="latest_version" label="Latest Version" description="Newest version available on the stores (e.g. 2.1.0)" />
                                <Input name="min_version" label="Minimum Supported Version" description="Versions below this are forced to update" />
                                <Toggle name="force_update" label="Force Update" description="If enabled, users below Minimum Supported Version cannot use the app until they update" />
                                <Input name="android_url" label="Android Play Store URL" />
                                <Input name="ios_url" label="iOS App Store URL" />

                                <div className="md:col-span-2 rounded-lg border border-blue-100 bg-blue-50 p-4">
                                    <label className="text-sm font-semibold text-gray-800">Corporate Categories</label>
                                    <p className="mt-1 text-sm text-blue-900">{normalizeCsvValue(settings.corporate_categories) || 'No corporate categories enabled.'}</p>
                                    <p className="mt-1 text-xs text-blue-700">Use Category Types and enable “Show In Corporate Booking” for the corporate flow.</p>
                                </div>
                                <Input name="corporate_meeting_types" label="Corporate Meeting Types (comma separated)" description="Example: in_person, google_meet, skype" />
                                <Input name="scrap_proof_image_labels" label="Scrap Proof Image Labels (comma separated)" description="Example: front, back, left, right" />
                            </div>
                        )}

                        {activeTab === 'intervals' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <Input name="pickup_boy_location_interval_seconds" label="Location Update Interval" type="number" description="Seconds between pickup boy GPS updates" />
                                <Input name="tracking_refresh_interval_seconds" label="Tracking Refresh Interval" type="number" description="Seconds for customer tracking map refresh" />
                                <Input name="dashboard_refresh_interval_seconds" label="Dashboard Sync Interval" type="number" description="Seconds for data re-sync on mobile home" />
                                <Input name="max_reschedule_hours_before_slot" label="Reschedule Buffer (Hours)" type="number" description="Minimum hours before slot to allow reschedule" />
                            </div>
                        )}

                        {activeTab === 'msg91' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="md:col-span-2 p-4 bg-blue-50 border border-blue-100 rounded-lg">
                                    <p className="text-xs text-blue-700 font-medium">
                                        Configure your MSG91 (SMS Gateway) credentials here. These settings will override any values set in the environment files.
                                    </p>
                                </div>
                                <Input name="msg91_auth_key" label="Auth Key" description="Your MSG91 API Authentication Key" />
                                <Input name="msg91_sender_id" label="Sender ID" description="6-character approved Sender ID (e.g., SCRPI)" />
                                <Input name="msg91_otp_template_id" label="OTP Template ID" description="DLT approved template ID for OTPs" />
                                <Input name="msg91_sms_template_id" label="SMS Flow ID" description="Default Flow/Template ID for transactional SMS" />

                                <div className="md:col-span-2 border-t border-gray-100 pt-4 mt-2">
                                    <h3 className="text-sm font-bold text-gray-700 mb-4">Event Specific Templates (Flow IDs)</h3>
                                </div>

                                <Input name="msg91_pickup_booked_template_id" label="Pickup Booked Template ID" />
                                <Input name="msg91_pickup_completed_template_id" label="Pickup Completed Template ID" />
                                <Input name="msg91_payment_feedback_template_id" label="Payment & Feedback Template ID" />
                                <Input name="msg91_pickup_rescheduled_template_id" label="Pickup Rescheduled Template ID" />

                                <div className="md:col-span-2 border-t border-gray-100 pt-4">
                                    <Input name="msg91_country_code" label="Country Code" description="Default country code (e.g., 91 for India)" />
                                </div>
                            </div>
                        )}

                        <div className="mt-8 flex justify-end">
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-6 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition-all disabled:opacity-50"
                            >
                                {processing ? 'Saving...' : 'Save Changes'}
                            </button>
                        </div>
                    </form>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}

const MAX_BANNER_SIZE_MB = 8;
const MAX_BANNER_SIZE_BYTES = MAX_BANNER_SIZE_MB * 1024 * 1024;

function HomeBanners({ banners = [] }) {
    const [newFile, setNewFile] = useState(null);
    const [newPreview, setNewPreview] = useState(null);
    const [newText, setNewText] = useState('');
    const [newFileError, setNewFileError] = useState(null);
    const [processing, setProcessing] = useState(false);
    const [replaceErrors, setReplaceErrors] = useState({});

    const handleNewFileChange = (file) => {
        if (file && file.size > MAX_BANNER_SIZE_BYTES) {
            setNewFileError(`Image is too large. Maximum allowed size is ${MAX_BANNER_SIZE_MB} MB.`);
            setNewFile(null);
            setNewPreview(null);
            return;
        }

        setNewFileError(null);
        setNewFile(file);
        setNewPreview(file ? URL.createObjectURL(file) : null);
    };

    const handleAdd = (e) => {
        e.preventDefault();
        if (!newFile) return;

        const formData = new FormData();
        formData.append('image', newFile);
        formData.append('text', newText ?? '');

        setProcessing(true);
        router.post(route('admin.home-banners.store'), formData, {
            forceFormData: true,
            onSuccess: () => {
                setNewFile(null);
                setNewPreview(null);
                setNewText('');
            },
            onFinish: () => setProcessing(false),
        });
    };

    const handleTextUpdate = (banner, text) => {
        const formData = new FormData();
        formData.append('_method', 'put');
        formData.append('text', text ?? '');

        router.post(route('admin.home-banners.update', banner.id), formData, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const handleImageReplace = (banner, file) => {
        if (!file) return;

        if (file.size > MAX_BANNER_SIZE_BYTES) {
            setReplaceErrors((prev) => ({
                ...prev,
                [banner.id]: `Image is too large. Maximum allowed size is ${MAX_BANNER_SIZE_MB} MB.`,
            }));
            return;
        }

        setReplaceErrors((prev) => ({ ...prev, [banner.id]: null }));

        const formData = new FormData();
        formData.append('_method', 'put');
        formData.append('image', file);

        router.post(route('admin.home-banners.update', banner.id), formData, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const handleRemove = (banner) => {
        if (!window.confirm('Remove this banner?')) return;

        router.delete(route('admin.home-banners.destroy', banner.id), {
            preserveScroll: true,
        });
    };

    const handleMove = (index, direction) => {
        const newOrder = [...banners];
        const targetIndex = index + direction;
        if (targetIndex < 0 || targetIndex >= newOrder.length) return;

        [newOrder[index], newOrder[targetIndex]] = [newOrder[targetIndex], newOrder[index]];

        router.post(route('admin.home-banners.reorder'), {
            ids: newOrder.map((b) => b.id),
        }, {
            preserveScroll: true,
        });
    };

    return (
        <div>
            <div className="mb-4">
                <h3 className="text-sm font-semibold text-gray-800">Home Screen Banner Slider</h3>
                <p className="text-xs text-gray-500">Add any number of images shown in the rotating banner on the customer app home screen. Tapping any banner takes the user to the category selection screen.</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                {banners.map((banner, index) => (
                    <div key={banner.id} className="flex flex-col gap-2">
                        <div className="aspect-video w-full rounded-lg border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center">
                            <img src={banner.image_url} alt={`Banner ${index + 1}`} className="w-full h-full object-cover" />
                        </div>
                        <input
                            type="file"
                            accept="image/*"
                            onChange={(e) => handleImageReplace(banner, e.target.files?.[0] ?? null)}
                            className="text-xs"
                        />
                        {replaceErrors[banner.id] && (
                            <p className="text-xs text-red-600">{replaceErrors[banner.id]}</p>
                        )}
                        <input
                            type="text"
                            defaultValue={banner.text ?? ''}
                            onBlur={(e) => handleTextUpdate(banner, e.target.value)}
                            placeholder="Optional overlay text (e.g. Sell your scrap today!)"
                            maxLength={120}
                            className="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none text-xs"
                        />
                        <div className="flex items-center justify-between">
                            <div className="flex gap-2">
                                <button
                                    type="button"
                                    onClick={() => handleMove(index, -1)}
                                    disabled={index === 0}
                                    className="text-xs font-semibold text-gray-500 hover:text-gray-700 disabled:opacity-30"
                                >
                                    ↑ Move up
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleMove(index, 1)}
                                    disabled={index === banners.length - 1}
                                    className="text-xs font-semibold text-gray-500 hover:text-gray-700 disabled:opacity-30"
                                >
                                    ↓ Move down
                                </button>
                            </div>
                            <button
                                type="button"
                                onClick={() => handleRemove(banner)}
                                className="text-xs font-semibold text-red-600 hover:underline"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                ))}
            </div>

            <form onSubmit={handleAdd} className="border-t border-gray-100 pt-6">
                <h3 className="text-sm font-semibold text-gray-800 mb-2">Add New Banner</h3>
                <div className="flex flex-col gap-3 max-w-md">
                    <div className="aspect-video w-full rounded-lg border border-dashed border-gray-300 bg-gray-50 overflow-hidden flex items-center justify-center">
                        {newPreview ? (
                            <img src={newPreview} alt="New banner preview" className="w-full h-full object-cover" />
                        ) : (
                            <span className="text-xs text-gray-400">No image selected</span>
                        )}
                    </div>
                    <input
                        type="file"
                        accept="image/*"
                        onChange={(e) => handleNewFileChange(e.target.files?.[0] ?? null)}
                        className="text-xs"
                    />
                    {newFileError && (
                        <p className="text-xs text-red-600">{newFileError}</p>
                    )}
                    <input
                        type="text"
                        value={newText}
                        onChange={(e) => setNewText(e.target.value)}
                        placeholder="Optional overlay text (e.g. Sell your scrap today!)"
                        maxLength={120}
                        className="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none text-xs"
                    />
                    <button
                        type="submit"
                        disabled={processing || !newFile}
                        className="px-6 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition-all disabled:opacity-50 self-start"
                    >
                        {processing ? 'Adding...' : 'Add Banner'}
                    </button>
                </div>
            </form>
        </div>
    );
}
