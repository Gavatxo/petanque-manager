export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-full bg-sidebar-primary text-sidebar-primary-foreground">
                <svg width="18" height="18" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="10.5" cy="10.5" r="8" fill="oklch(0.96 0.025 40)" />
                    <circle cx="8" cy="9" r="1.5" fill="oklch(0.60 0.20 40)" opacity="0.65" />
                    <circle cx="13.5" cy="8" r="1.3" fill="oklch(0.60 0.20 40)" opacity="0.65" />
                    <circle cx="14" cy="13.5" r="1.5" fill="oklch(0.60 0.20 40)" opacity="0.65" />
                </svg>
            </div>
            <div className="ml-1 grid flex-1 text-left leading-tight">
                <span
                    className="truncate text-[15px] font-extrabold"
                    style={{ fontFamily: "'Barlow Condensed', sans-serif", letterSpacing: '0.03em' }}
                >
                    PÉTANQUE
                </span>
                <span className="truncate text-[11px] font-medium opacity-70">Manager</span>
            </div>
        </>
    );
}
