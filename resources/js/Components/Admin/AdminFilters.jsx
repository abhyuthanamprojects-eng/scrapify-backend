import TextInput from '@/Components/TextInput';

export default function AdminFilters({
    filters,
    onFilterChange,
    children
}) {
    return (
        <div className="bg-white rounded-2xl shadow-sm border border-card-border p-4 sm:p-5 mb-5">
            <div className="grid grid-cols-1 md:grid-cols-12 gap-3">
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
                <label className="block text-sm font-medium text-gray-700 mb-1.5">
                    {label}
                </label>
            )}
            <TextInput
                placeholder={placeholder}
                value={value}
                onChange={onChange}
                className="w-full border border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg px-4 py-2.5"
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
                <label className="block text-sm font-medium text-gray-700 mb-1.5">
                    {label}
                </label>
            )}
            <select
                value={value}
                onChange={onChange}
                disabled={disabled}
                className={`w-full border border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg px-4 py-2.5 text-gray-700 bg-white ${
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
