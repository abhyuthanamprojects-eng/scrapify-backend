import { forwardRef, useEffect, useRef } from 'react';

export default forwardRef(function TextInput({ type = 'text', className = '', isFocused = false, ...props }, ref) {
    const localRef = useRef(null);

    useEffect(() => {
        if (isFocused) {
            (ref || localRef).current?.focus();
        }
    }, [isFocused]);

    return (
        <input
            {...props}
            type={type}
            className={
                'border-gray-300 focus:border-primary focus:ring-primary/20 rounded-xl shadow-sm transition-[border-color,box-shadow,background-color] duration-150 ease-out ' +
                className
            }
            ref={ref ? ref : localRef}
        />
    );
});
