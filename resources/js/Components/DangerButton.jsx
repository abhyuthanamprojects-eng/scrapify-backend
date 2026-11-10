export default function DangerButton({ className = '', disabled, children, ...props }) {
    return (
        <button
            {...props}
            className={
                `inline-flex select-none items-center justify-center px-4 py-2 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:ring-offset-2 transition-[transform,background-color,border-color,box-shadow,opacity] duration-150 ease-out ${
                    disabled && 'opacity-25 cursor-not-allowed'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
