import TextInput from '@/Components/TextInput';

export default function AdminFilters({
    filters,
    onFilterChange,
    children
}) {
    return (
        <div className="admin-filter-panel mb-5 rounded-2xl border border-[#dde8e0] bg-white p-4 shadow-[0_2px_12px_rgba(31,92,57,0.03)] sm:p-5">
            <div className="grid grid-cols-1 items-end gap-4 md:grid-cols-12">
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
                <label className="mb-2 block text-[10px] font-bold uppercase tracking-[0.14em] text-[#718078]">
                    {label}
                </label>
            )}
            <TextInput
                placeholder={placeholder}
                value={value}
                onChange={onChange}
                className="h-11 w-full rounded-xl border border-[#dfe9e2] bg-[#f8fbf9] px-3.5 text-sm text-[#26372d] placeholder:text-[#9aa9a0] transition-[border-color,background-color,box-shadow] duration-150 ease-out focus:border-primary focus:bg-white focus:ring-4 focus:ring-green-100"
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
                <label className="mb-2 block text-[10px] font-bold uppercase tracking-[0.14em] text-[#718078]">
                    {label}
                </label>
            )}
            <select
                value={value}
                onChange={onChange}
                disabled={disabled}
                className={`h-11 w-full rounded-xl border border-[#dfe9e2] bg-[#f8fbf9] px-3.5 text-sm text-[#4d5e53] transition-[border-color,background-color,box-shadow] duration-150 ease-out focus:border-primary focus:bg-white focus:ring-4 focus:ring-green-100 ${
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
