import type { UserType } from '@common/types';

// @deprecated
// Check if the URL is active (matches or starts with the current path)
export function isActive(url: string | null = null, directPath: string | null = null): boolean {
    if (!url || url === '#') return location.pathname.includes(directPath ?? '');
    const { pathname, hostname } = new URL(url);
    return location.pathname.startsWith(pathname) && location.hostname === hostname;
}

// Strip number to fixed precision
export function strip(number: number = 0, toPrecision: number = 2): number {
    return Number(number.toFixed(toPrecision));
}

// Scroll to an element matching type and text
export const scrollToView = (item: any): void => {
    const elements = document.querySelectorAll(`${item.type}`);
    if (elements.length > 1) elements[1].scrollIntoView({ behavior: 'smooth' });
    elements.forEach((el, i) => {
        if (el.textContent === item.text) elements[i]?.scrollIntoView({ behavior: 'smooth' });
    });
};

// Get initials from name (default to 2 initials)
export const getInitials = (name: string, number: number = 2): string => {
    if (!name) return '';
    const nameParts = name.split(' ');
    const initials = nameParts.map((name) => name.charAt(0).toUpperCase());
    if (number === -1) {
        return initials.join('');
    }
    return initials.slice(0, number).join('');
};

// Format user's name as "FirstName.LastInitial"
export const getName = (user: UserType | undefined): string => {
    if (!user) return '';
    if (user.name) {
        const [fn, ln] = user.name.split(' ');
        return `${ln?.slice(0, 1).toUpperCase()}. ${fn}`;
    }
    return `${user.first_name}.${user.last_name?.slice(0, 1).toUpperCase()}`;
};

// Get media URL (either media thumbnail or profile photo)
export const getFilePath = (item?: any, isMedia: boolean = false): string => {
    if (!item) return '';
    if (isMedia && typeof item?.media === 'object') return item?.media?.storage_media?.path || ''; // @todo:  item?.media?.storage_media?.thumb ||
    return typeof item.media === 'string' ? item.media : item.profile_photo_url || '';
};

interface FormPasswordCheck {
    password?: string; // Password field (optional)
    password_confirmation?: string; // Password confirmation (optional)
    errors: Record<string, string>; // Error messages (key-value pairs)
}
// Check if passwords match and set errors
export const checkPassword = (form: FormPasswordCheck): void => {
    if (form.password && form.password !== form.password_confirmation) {
        form.errors.password_confirmation = "Le mot de passe n'est pas identique";
    } else {
        form.errors.password_confirmation = '';
    }
};

// Utility function to extract URL parameters from the current or provided URL
export const getParams = (url: string | null = null): Record<string, string | number | boolean> =>
    Object.fromEntries(new URLSearchParams(url || window.location.search));
