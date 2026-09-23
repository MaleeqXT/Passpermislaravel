import { useAlert } from '@shared/stores';

// Copy text to clipboard
export async function copy(text: string = ''): Promise<void> {
    try {
        if (!navigator.clipboard) throw new Error('Clipboard not supported');
        await navigator.clipboard.writeText(text);
    } catch (error) {
        throw new Error(`Failed to copy: ${error instanceof Error ? error.message : String(error)}`);
    }
}

/**
 * Copy text to the clipboard and show success/error alert.
 * @param value - The text to be copied.
 * @throws Error if copying fails.
 */
export const copyText = async (value: string): Promise<void> => {
    const alert = useAlert(); // Assuming useAlert is a function that shows alert notifications
    try {
        // Check if Clipboard API is available
        if (!navigator.clipboard) {
            throw new Error('Clipboard not supported');
        }

        // Attempt to write text to clipboard
        await navigator.clipboard.writeText(value);

        // Show success alert
        alert.show({ type: 'success', title: `${value} a copié` });
        // alert.show({ type: 'success', title: `${value} a copié` });
    } catch (error) {
        // Show error alert if an error occurs
        const errorMessage = error instanceof Error ? error.message : String(error);
        alert.show({ type: 'error', title: `Échec de la copie: ${errorMessage}` });

        // Rethrow the error
        throw new Error(`Échec de la copie: ${errorMessage}`);
    }
};
