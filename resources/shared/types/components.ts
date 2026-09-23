export type ButtonType = {
    submit?: boolean;
    loading?: boolean;
    icon?: ((...args: any[]) => any) | object | string;
    outline?: boolean;
    link?: boolean;
    full?: boolean;
    external?: boolean;
    self?: boolean;
    href?: string;
    label?: string;
    class?: string;
    // title?: string;
    disabled?: boolean;
    variant?: 'primary' | 'secondary' | 'dark' | 'info' | 'danger' | 'success' | 'warning' | 'header' | 'default' | 'orange' | 'indigo';
    onAction?: (item?: any) => void; // Function to execute on button click
};

export type GroupActionType = ButtonType & {
    classItem?: string; // Additional class for the list item
    isLink?: boolean; // Additional class for the list item
};

export type BulkActionType = Omit<ButtonType, 'onAction'> & {
    label?: string;
    title?: string;
    onAction?: (selectedItems: any | null, close?: () => void) => string | void;
    confirm?: any;
    isLink?: boolean;
    // [key: string]: any;
};

export type NavigationItemType = {
    name: string;
    href?: string;
    icon?: any; // Assuming you're using React
    drawer?: any; // Assuming you're using React
    filled?: any; // Assuming you're using React
    children?: NavigationItemType[]; // Only some items will have children
    divider?: boolean;
    mobile?: boolean;
    letter?: boolean;
    external?: boolean;
    grow?: boolean;
    sticky?: boolean;
    onAction?: (item?: any) => void; // Function to execute on button click
};
