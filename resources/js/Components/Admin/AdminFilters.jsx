import TextInput from '@/Components/TextInput';

export default function AdminFilters({
    filters,
    onFilterChange,
    children
}) {
    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
            <div className="grid grid-cols-1 md:grid-cols-12 gap-4">
                {children}
            </div>
        </div>
    );
}

export function AdminFilterInput({
    placeholder,
    value,
    onChange,
    label,
    colSpan = 'md:col-span-3'
}) {
    return (
        <div className={`${colSpan}`}>
            {label && (
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    {label}
                </label>
            )}
            <TextInput
                placeholder={placeholder}
                value={value}
                onChange={onChange}
                className="w-full border border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg px-4 py-3"
            />
        </div>
    );
}

export function AdminFilterSelect({
    options,
    value,
    onChange,
    label,
    colSpan = 'md:col-span-3',
    disabled = false
}) {
    return (
        <div className={`${colSpan}`}>
            {label && (
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    {label}
                </label>
            )}
            <select
                value={value}
                onChange={onChange}
                disabled={disabled}
                className={`w-full border border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg px-4 py-3 text-gray-700 bg-white ${
                    disabled ? 'opacity-50 bg-gray-100 cursor-not-allowed' : ''
                }`}
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </div>
    );
}
