import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import { useState } from 'react';

export default function Index({ states, cities }) {
    const [activeTab, setActiveTab] = useState('states');

    const stateForm = useForm({
        name: '',
        code: '',
        status: true,
    });

    const cityForm = useForm({
        state_id: '',
        name: '',
        default_zone: '',
        status: true,
    });

    const handleStateSubmit = (e) => {
        e.preventDefault();
        stateForm.post(route('admin.locations.states.store'), {
            onSuccess: () => stateForm.reset(),
        });
    };

    const handleCitySubmit = (e) => {
        e.preventDefault();
        cityForm.post(route('admin.locations.cities.store'), {
            onSuccess: () => cityForm.reset(),
        });
    };

    return (
        <AdminLayout>
            <Head title="Locations" />

            <h1 className="text-3xl font-semibold text-gray-800 mb-6">Location Management</h1>

            {/* Tabs */}
            <div className="mb-6 border-b border-gray-200">
                <nav className="-mb-px flex space-x-8">
                    <button
                        onClick={() => setActiveTab('states')}
                        className={`${
                            activeTab === 'states'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                    >
                        States ({states.length})
                    </button>
                    <button
                        onClick={() => setActiveTab('cities')}
                        className={`${
                            activeTab === 'cities'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                    >
                        Cities ({cities.length})
                    </button>
                </nav>
            </div>

            {/* States Tab */}
            {activeTab === 'states' && (
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Add State Form */}
                    <div className="bg-white shadow-md rounded-lg p-6">
                        <h2 className="text-xl font-semibold mb-4">Add New State</h2>
                        <form onSubmit={handleStateSubmit} className="space-y-4">
                            <div>
                                <InputLabel htmlFor="state_name" value="State Name" />
                                <TextInput
                                    id="state_name"
                                    type="text"
                                    className="mt-1 block w-full"
                                    value={stateForm.data.name}
                                    onChange={(e) => stateForm.setData('name', e.target.value)}
                                    required
                                />
                            </div>
                            <div>
                                <InputLabel htmlFor="state_code" value="State Code" />
                                <TextInput
                                    id="state_code"
                                    type="text"
                                    className="mt-1 block w-full"
                                    value={stateForm.data.code}
                                    onChange={(e) => stateForm.setData('code', e.target.value)}
                                    placeholder="e.g., MH, DL, KA"
                                    required
                                />
                            </div>
                            <PrimaryButton disabled={stateForm.processing}>
                                Add State
                            </PrimaryButton>
                        </form>
                    </div>

                    {/* States List */}
                    <div className="bg-white shadow-md rounded-lg p-6">
                        <h2 className="text-xl font-semibold mb-4">States</h2>
                        <div className="space-y-2 max-h-96 overflow-y-auto">
                            {states.map((state) => (
                                <div key={state.id} className="flex justify-between items-center p-3 bg-gray-50 rounded">
                                    <div>
                                        <div className="font-medium">{state.name}</div>
                                        <div className="text-sm text-gray-500">Code: {state.code}</div>
                                    </div>
                                    <span className={`px-2 py-1 text-xs rounded ${
                                        state.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                    }`}>
                                        {state.status ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}

            {/* Cities Tab */}
            {activeTab === 'cities' && (
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Add City Form */}
                    <div className="bg-white shadow-md rounded-lg p-6">
                        <h2 className="text-xl font-semibold mb-4">Add New City</h2>
                        <form onSubmit={handleCitySubmit} className="space-y-4">
                            <div>
                                <InputLabel htmlFor="city_state" value="State" />
                                <select
                                    id="city_state"
                                    className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    value={cityForm.data.state_id}
                                    onChange={(e) => cityForm.setData('state_id', e.target.value)}
                                    required
                                >
                                    <option value="">Select State</option>
                                    {states.map((state) => (
                                        <option key={state.id} value={state.id}>
                                            {state.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <InputLabel htmlFor="city_name" value="City Name" />
                                <TextInput
                                    id="city_name"
                                    type="text"
                                    className="mt-1 block w-full"
                                    value={cityForm.data.name}
                                    onChange={(e) => cityForm.setData('name', e.target.value)}
                                    required
                                />
                            </div>
                            <div>
                                <InputLabel htmlFor="city_default_zone" value="Default Zone" />
                                <TextInput
                                    id="city_default_zone"
                                    type="text"
                                    className="mt-1 block w-full"
                                    value={cityForm.data.default_zone}
                                    onChange={(e) => cityForm.setData('default_zone', e.target.value)}
                                    placeholder="e.g. North Zone"
                                />
                                <p className="text-xs text-gray-500 mt-1">Used to auto-assign zone for warehouses in this city.</p>
                            </div>
                            <PrimaryButton disabled={cityForm.processing}>
                                Add City
                            </PrimaryButton>
                        </form>
                    </div>

                    {/* Cities List */}
                    <div className="bg-white shadow-md rounded-lg p-6">
                        <h2 className="text-xl font-semibold mb-4">Cities</h2>
                        <div className="space-y-2 max-h-96 overflow-y-auto">
                            {cities.map((city) => (
                                <div key={city.id} className="flex justify-between items-center p-3 bg-gray-50 rounded">
                                    <div>
                                        <div className="font-medium">{city.name}</div>
                                        <div className="text-sm text-gray-500">
                                            {city.state?.name}
                                            {city.default_zone && <span className="ml-2 text-gray-400">· {city.default_zone}</span>}
                                        </div>
                                    </div>
                                    <span className={`px-2 py-1 text-xs rounded ${
                                        city.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                    }`}>
                                        {city.status ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
