export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded-md border-gray-300 text-primary shadow-sm transition-[border-color,box-shadow,background-color] duration-150 ease-out focus:ring-primary/20 ' +
                className
            }
        />
    );
}
