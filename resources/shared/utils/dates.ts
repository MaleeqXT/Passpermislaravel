import moment from 'moment-timezone';

/**
 * Formats a date based on the specified type.
 * @param d - The date string or object to format.
 * @param type - The type of formatting to apply (e.g., 'fr', 'full', 'time').
 * @returns The formatted date string or `null` if no date is provided.
 */
type DateFormatType =
    | 'full'
    | 'fulltime'
    | 'letter'
    | 'month'
    | 'time'
    | 'shortTime'
    | 'iso'
    | 'fulliso'
    | 'fr'
    | 'fr-full'
    | 'fromNow'
    | 'monthly'
    | 'old';
export function dateFormat(d: string | Date = '', type: DateFormatType = 'fr'): string | undefined {
    if (!d && d !== undefined) return undefined;
    const m = moment(d);
    switch (type) {
        case 'full': // Full date in a detailed format
            return m.format('ddd DD MMMM YYYY');
        case 'fulltime': // Date with time
            return m.format('DD MMM YYYY HH:mm');
        case 'letter': // Date in letter format
            return m.format('DD MMM YYYY');
        case 'month': // Month format
            return m.format('MMMM');
        case 'monthly': // Month format
            return m.format('MMMM YYYY');
        case 'time': // Time only
        case 'shortTime': // Short time format
            // return m.format('HH:mm');
            return m.format('HH:mm');
        case 'iso': // ISO date format
            return m.format('yyyy-MM-DD');
        case 'fulliso': // Full ISO date with time
            return m.format('yyyy-MM-DD HH:mm:ss');
        case 'fr': // French date format
            return m.format('DD/MM/YYYY');
        case 'fr-full': // French date format
            return m.format('DD/MM/YYYY HH:mm');
        case 'fromNow': // Relative time (e.g., '5 minutes ago')
            return m.fromNow();
        case 'old': // Age in years from a given date
            return moment().diff(d, 'years', false).toString();
        default: // Default case returns an empty string
            return '';
    }
}

/**
 * Formats an hour string to 'HH:mm' format.
 * @param hour - The hour string to format.
 * @returns The formatted hour string.
 */
export const getHour = (hour: string | number): string => {
    return moment(hour, 'HH:mm').format('HH:mm');
};

/**
 * Formats a date string to 'yyyy-MM-DD' format.
 * @param w - The week offset.
 * @param d - The day offset.
 * @param format - The date format.
 * @returns The formatted date string.
 */
export const currentDate = moment().format('yyyy-MM-DD');
export const currentDateTime = moment().format('yyyy-MM-DD HH:mm');
export const getDay = (w: number = 0, d: number = 0, format = 'yyyy-MM-DD') =>
    moment().startOf('isoWeek').add(w, 'week').add(d, 'day').format(format);

export const getDate = (date: string = currentDate, inceaseBy: number = 0, format = 'yyyy-MM-DD') =>
    moment(date).startOf('isoWeek').add(inceaseBy, 'day').format(format);

/**
 * Checks if an item is outdated based on its date and end time.
 * @param item - The item containing a `date` and `end_at` property.
 * @returns `true` if the item is outdated, otherwise `false`.
 */
export const isOutdated = (item: { date: string; end_at: string }): boolean => {
    const date = dateFormat(item.date, 'iso');
    return date ? moment().isAfter(`${date} ${item.end_at}`) : false;
};

/**
 * Checks if a time range overlaps with another time range.
 * @param range1 - The first time range containing `start_at` and `end_at`.
 * @param range2 - The second time range containing `start_at` and `end_at`.
 * @returns `true` if there is an overlap, otherwise `false`.
 */
export const isTimeOverlapping = (range1: { start_at: string; end_at: string }, range2: { start_at: string; end_at: string }): boolean => {
    const start1 = moment(range1.start_at, 'HH:mm');
    const end1 = moment(range1.end_at, 'HH:mm');
    const start2 = moment(range2.start_at, 'HH:mm');
    const end2 = moment(range2.end_at, 'HH:mm');

    return (
        start1.isBetween(start2, end2, 'minute', '()') ||
        end1.isBetween(start2, end2, 'minute', '()') ||
        (start1.isSameOrBefore(start2) && end1.isSameOrAfter(end2))
    ); // Full overlap case
};
/**
 *  parsed dates
 * @param d - The date string to parse.
 * @param w - The week offset.
 * @returns The parsed date object.
 */
export const parsedDates = (d?: string) => {
    const date = moment(d); //.add(w, 'week');
    return {
        date_1: date.startOf('isoWeek').format('yyyy-MM-DD'),
        date_2: date.endOf('isoWeek').format('yyyy-MM-DD'),
        all: true,
    };
};
